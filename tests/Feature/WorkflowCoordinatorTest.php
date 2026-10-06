<?php

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('an eligible workflow is only queued once', function () {
    Queue::fake();

    $booking = Booking::create([
        'reference' => 'TEST-001',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'pending',
    ]);

    $workflow = $booking->workflows()->create([
        'type' => 'property_confirmation',
        'status' => 'pending',
        'message' => 'Waiting to start property confirmation',
    ]);

    $coordinator = app(WorkflowCoordinator::class);

    $coordinator->startIfReady($workflow);
    $coordinator->startIfReady($workflow);

    Queue::assertPushed(
        ProcessBookingWorkflowJob::class,
        1
    );

    expect($workflow->fresh()->status)
        ->toBe('queued');
});

test('a dependent workflow waits for incomplete dependencies', function () {
    Queue::fake();

    $booking = Booking::create([
        'reference' => 'TEST-002',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'pending',
    ]);

    $booking->workflows()->create([
        'type' => 'property_confirmation',
        'status' => 'completed',
        'message' => 'Property confirmed',
    ]);

    $booking->workflows()->create([
        'type' => 'flight_confirmation',
        'status' => 'processing',
        'message' => 'Flight confirmation in progress',
    ]);

    $signoff = $booking->workflows()->create([
        'type' => 'operations_signoff',
        'status' => 'pending',
        'message' => 'Waiting for operations signoff',
    ]);

    $coordinator = app(WorkflowCoordinator::class);

    $coordinator->startIfReady($signoff);

    Queue::assertNotPushed(ProcessBookingWorkflowJob::class);

    expect($signoff->fresh()->status)
        ->toBe('pending');

    $flight = $booking->workflows()
        ->where('type', 'flight_confirmation')
        ->firstOrFail();

    $flight->update([
        'status' => 'completed',
        'message' => 'Flight confirmed',
    ]);

    $coordinator->startIfReady($signoff->fresh());

    Queue::assertPushed(
        ProcessBookingWorkflowJob::class,
        1
    );

    expect($signoff->fresh()->status)
        ->toBe('queued');
});
