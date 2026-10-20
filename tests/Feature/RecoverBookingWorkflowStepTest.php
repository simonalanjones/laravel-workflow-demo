<?php

use App\Enums\BookingStatus;
use App\Jobs\ProcessBookingWorkflowStepJob;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

test('recovery queues a pending workflow without a creation event', function (): void {
    Queue::fake();

    $booking = Booking::create([
        'reference' => 'RECOVERY-001',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'pending',
    ]);

    $workflowStep = $booking->workflowSteps()->create([
        'type' => 'property_confirmation',
        'status' => 'pending',
        'message' => 'Waiting to start',
    ]);

    artisan('bookings:recover')->assertSuccessful();

    expect($workflowStep->fresh()->status)->toBe('queued');

    Queue::assertPushed(
        ProcessBookingWorkflowStepJob::class,
        fn (ProcessBookingWorkflowStepJob $job): bool => $job->workflowStep->id === $workflowStep->id,
    );
});

test('recovery completes a booking whose completion event was missed', function (): void {
    Queue::fake();

    $booking = Booking::create([
        'reference' => 'RECOVERY-002',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'processing',
    ]);

    $booking->workflowSteps()->create([
        'type' => 'property_confirmation',
        'status' => 'completed',
        'message' => 'Property confirmed',
    ]);

    artisan('bookings:recover')->assertSuccessful();

    expect($booking->fresh()->status)->toBe(BookingStatus::Processed);

    Queue::assertNothingPushed();
});

test('recovery leaves signoff pending while a dependency is incomplete', function (): void {
    Queue::fake();

    $booking = Booking::create([
        'reference' => 'RECOVERY-003',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'processing',
    ]);

    $booking->workflowSteps()->create([
        'type' => 'property_confirmation',
        'status' => 'processing',
        'message' => 'Property confirmation in progress',
    ]);

    $signoff = $booking->workflowSteps()->create([
        'type' => 'operations_signoff',
        'status' => 'pending',
        'message' => 'Waiting for operations signoff',
    ]);

    artisan('bookings:recover')->assertSuccessful();

    expect($signoff->fresh()->status)->toBe('pending');

    Queue::assertNothingPushed();
});
