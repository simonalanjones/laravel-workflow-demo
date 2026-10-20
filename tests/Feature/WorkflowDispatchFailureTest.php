<?php

use App\Models\Booking;
use App\Models\BookingWorkflowStep;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('failed job insertion leaves item pending and a later dispatch can succeed', function (): void {
    config(['queue.default' => 'database', 'queue.connections.database.table' => 'missing_test_queue_table']);
    $workflowStep = createWorkflowForWorkflowDispatchFailure();
    $coordinator = app(WorkflowCoordinator::class);

    try {
        $coordinator->queueIfReady($workflowStep);
        $this->fail('Queue insertion should fail.');
    } catch (QueryException) {
        $this->assertDatabaseCount('jobs', 0);
    }

    $this->assertSame('pending', $workflowStep->fresh()->status);
    $this->assertSame('Waiting to start', $workflowStep->fresh()->message);

    config([
        'queue.connections.recovered' => array_replace(config('queue.connections.database'), ['table' => 'jobs']),
        'queue.default' => 'recovered',
    ]);
    $coordinator->queueIfReady($workflowStep->fresh());

    $this->assertSame('queued', $workflowStep->fresh()->status);
    $this->assertDatabaseCount('jobs', 1);
});

test('status and job insertion roll back together even when after commit is enabled', function (): void {
    config(['queue.default' => 'database', 'queue.connections.database.after_commit' => true]);
    $workflowStep = createWorkflowForWorkflowDispatchFailure();
    DB::beginTransaction();
    try {
        app(WorkflowCoordinator::class)->queueIfReady($workflowStep);
        $this->assertSame('queued', $workflowStep->fresh()->status);
        $this->assertDatabaseCount('jobs', 1);
    } finally {
        DB::rollBack();
    }

    $this->assertSame('pending', $workflowStep->fresh()->status);
    $this->assertDatabaseCount('jobs', 0);
});

function createWorkflowForWorkflowDispatchFailure(): BookingWorkflowStep
{
    $booking = Booking::create([
        'reference' => 'DISPATCH-TEST',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'pending',
    ]);

    return $booking->workflowSteps()->create([
        'type' => 'property_confirmation',
        'status' => 'pending',
        'message' => 'Waiting to start',
    ]);
}
