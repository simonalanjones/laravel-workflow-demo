<?php

use App\Jobs\ProcessBookingWorkflowStepJob;
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

    $workflowStep = $booking->workflowSteps()->create([
        'type' => 'property_confirmation',
        'status' => 'pending',
        'message' => 'Waiting to start property confirmation',
    ]);

    $coordinator = app(WorkflowCoordinator::class);

    $coordinator->queueIfReady($workflowStep);
    $coordinator->queueIfReady($workflowStep);

    Queue::assertPushed(
        ProcessBookingWorkflowStepJob::class,
        1
    );

    expect($workflowStep->fresh()->status)
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

    $booking->workflowSteps()->create([
        'type' => 'property_confirmation',
        'status' => 'completed',
        'message' => 'Property confirmed',
    ]);

    $booking->workflowSteps()->create([
        'type' => 'flight_confirmation',
        'status' => 'processing',
        'message' => 'Flight confirmation in progress',
    ]);

    $signoff = $booking->workflowSteps()->create([
        'type' => 'operations_signoff',
        'status' => 'pending',
        'message' => 'Waiting for operations signoff',
    ]);

    $coordinator = app(WorkflowCoordinator::class);

    $coordinator->queueIfReady($signoff);

    Queue::assertNotPushed(ProcessBookingWorkflowStepJob::class);

    expect($signoff->fresh()->status)
        ->toBe('pending');

    $flight = $booking->workflowSteps()
        ->where('type', 'flight_confirmation')
        ->firstOrFail();

    $flight->update([
        'status' => 'completed',
        'message' => 'Flight confirmed',
    ]);

    $coordinator->queueIfReady($signoff->fresh());

    Queue::assertPushed(
        ProcessBookingWorkflowStepJob::class,
        1
    );

    expect($signoff->fresh()->status)
        ->toBe('queued');
});
