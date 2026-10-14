<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingWorkflow;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowDispatchFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_job_insertion_leaves_item_pending_and_a_later_dispatch_can_succeed(): void
    {
        config(['queue.default' => 'database', 'queue.connections.database.table' => 'missing_test_queue_table']);
        $workflow = $this->createWorkflow();
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
    }

    public function test_status_and_job_insertion_roll_back_together_even_when_after_commit_is_enabled(): void
    {
        config(['queue.default' => 'database', 'queue.connections.database.after_commit' => true]);
        $workflow = $this->createWorkflow();
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
    }

    private function createWorkflow(): BookingWorkflow
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
}
