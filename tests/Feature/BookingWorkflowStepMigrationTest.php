<?php

use App\Models\Booking;
use App\Models\ProviderRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('step rename preserves records relationships and rollback', function (): void {
    $booking = Booking::create([
        'reference' => 'RENAME-001', 'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com', 'status' => 'processing',
    ]);
    $step = $booking->workflowSteps()->create(['type' => 'property_confirmation', 'status' => 'completed']);
    $activity = $step->activities()->create([
        'booking_id' => $booking->id, 'type' => 'workflow_step_completed', 'message' => 'Confirmed',
    ]);
    $request = ProviderRequest::factory()->succeeded()->create(['booking_workflow_step_id' => $step->id]);
    $migration = require database_path('migrations/2026_10_20_094629_rename_booking_workflows_to_booking_workflow_steps.php');

    $migration->down();

    $this->assertDatabaseHas('booking_workflows', ['id' => $step->id, 'status' => 'completed']);
    $this->assertDatabaseHas('booking_activities', ['id' => $activity->id, 'booking_workflow_id' => $step->id, 'type' => 'workflow_item_completed']);
    $this->assertDatabaseHas('provider_requests', ['id' => $request->id, 'booking_workflow_id' => $step->id]);

    $migration->up();

    expect($booking->workflowSteps()->sole()->id)->toBe($step->id)
        ->and($activity->fresh()->workflowStep->id)->toBe($step->id)
        ->and($activity->fresh()->type)->toBe('workflow_step_completed')
        ->and($request->fresh()->workflowStep->id)->toBe($step->id)
        ->and($request->fresh()->idempotency_key)->toBe($request->idempotency_key)
        ->and($request->fresh()->response_payload)->toBe($request->response_payload);

    $step->delete();

    expect($request->fresh()->booking_workflow_step_id)->toBeNull()
        ->and($activity->fresh()->booking_workflow_step_id)->toBeNull();
});
