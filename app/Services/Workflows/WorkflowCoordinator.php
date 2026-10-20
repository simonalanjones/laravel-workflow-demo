<?php

namespace App\Services\Workflows;

use App\Attributes\DependsOn;
use App\Jobs\ProcessBookingWorkflowStepJob;
use App\Models\BookingWorkflowStep;
use Illuminate\Support\Facades\Bus;

class WorkflowCoordinator
{
    public function __construct(
        private WorkflowProcessorResolver $resolver
    ) {}

    /**
     * Queue a pending step once its existing prerequisites are completed.
     * The queue worker executes the processor later; this method only
     * checks readiness and dispatches the job.
     */
    public function queueIfReady(BookingWorkflowStep $workflowStep): void
    {
        if ($workflowStep->status !== 'pending') {
            return;
        }

        $processor = $this->resolver->resolve($workflowStep);

        $reflection = new \ReflectionClass($processor);

        /**
         * DependsOn is a custom PHP attribute: a label attached to a processor
         * class that lists the workflow step types it must wait for.
         *
         * For example, #[DependsOn(['property_confirmation'])] means the
         * processor must wait for that booking's property confirmation step
         * to complete, if the step exists.
         *
         * Reflection reads the attribute; the coordinator below enforces
         * the dependency check. A processor without this attribute has no
         * prerequisites to check here.
         */
        $attributes = $reflection->getAttributes(DependsOn::class);

        if ($attributes !== []) {
            /** @var DependsOn $dependsOn */
            $dependsOn = $attributes[0]->newInstance();

            /**
             * Check only prerequisite steps present on this booking.
             * An accommodation-only booking has no flight or vehicle step,
             * so their absence must not prevent operations signoff.
             */
            $dependencies = $workflowStep->booking
                ->workflowSteps()
                ->whereIn('type', $dependsOn->workflowTypes)
                ->get();

            $hasIncompleteDependency = $dependencies->contains(
                fn (BookingWorkflowStep $dependency) => $dependency->status !== 'completed'
            );

            if ($hasIncompleteDependency) {
                return;
            }
        }

        /**
         * Save the queued status and queue entry together. If job insertion
         * fails, the status change rolls back, leaving the step pending
         * for a later recovery attempt.
         *
         * This relies on the database queue using the same database
         * connection as the step; it does not extend to Redis or SQS.
         */
        $workflowStep->getConnection()->transaction(function () use ($workflowStep): void {
            /**
             * Recheck pending status in the database as part of the update.
             * Competing callers may both pass the earlier readiness checks,
             * but only one can change this row from pending to queued.
             * Zero updated rows means this caller did not claim the step.
             */
            $claimed = $workflowStep->newQuery()
                ->whereKey($workflowStep->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'queued',
                    'message' => 'Queued for processing',
                ]);

            if ($claimed === 0) {
                return;
            }

            /**
             * Insert the database queue job inside this transaction, even
             * when the queue connection normally defers dispatch until commit.
             * This does not execute the processor before commit.
             */
            Bus::dispatch((new ProcessBookingWorkflowStepJob($workflowStep))->beforeCommit());
        });
    }
}
