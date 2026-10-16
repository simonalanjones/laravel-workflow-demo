<?php

use App\Models\Booking;
use App\Models\BookingWorkflow;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('failed job insertion leaves item pending and a later dispatch can succeed', function (): void {
    config(['queue.default' => 'database', 'queue.connections.database.table' => 'missing_test_queue_table']);
    $workflow = createWorkflowForWorkflowDispatchFailure();
    $coordinator = app(WorkflowCoordinator::class);

    try {
        $coordinator->startIfReady($workflow);
        $this->fail('Queue insertion should fail.');
    } catch (QueryException) {
        $this->assertDatabaseCount('jobs', 0);
    }

    $this->assertSame('pending', $workflow->fresh()->status);
    $this->assertSame('Waiting to start', $workflow->fresh()->message);

    config([
        'queue.connections.recovered' => array_replace(config('queue.connections.database'), ['table' => 'jobs']),
        'queue.default' => 'recovered',
    ]);
    $coordinator->startIfReady($workflow->fresh());

    $this->assertSame('queued', $workflow->fresh()->status);
    $this->assertDatabaseCount('jobs', 1);
});

test('status and job insertion roll back together even when after commit is enabled', function (): void {
    config(['queue.default' => 'database', 'queue.connections.database.after_commit' => true]);
    $workflow = createWorkflowForWorkflowDispatchFailure();
    DB::beginTransaction();
    try {
        app(WorkflowCoordinator::class)->startIfReady($workflow);
        $this->assertSame('queued', $workflow->fresh()->status);
        $this->assertDatabaseCount('jobs', 1);
    } finally {
        DB::rollBack();
    }

    $this->assertSame('pending', $workflow->fresh()->status);
    $this->assertDatabaseCount('jobs', 0);
});

function createWorkflowForWorkflowDispatchFailure(): BookingWorkflow
{
    $booking = Booking::create([
        'reference' => 'DISPATCH-TEST',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'pending',
    ]);

    return $booking->workflows()->create([
        'type' => 'property_confirmation',
        'status' => 'pending',
        'message' => 'Waiting to start',
    ]);
}
