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