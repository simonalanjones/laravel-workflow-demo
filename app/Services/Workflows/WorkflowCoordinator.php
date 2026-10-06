<?php

namespace App\Services\Workflows;

use App\Attributes\DependsOn;
use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\BookingWorkflow;


class WorkflowCoordinator
{
    public function __construct(
        private WorkflowProcessorResolver $resolver
    ) {}

    public function startIfReady(BookingWorkflow $workflow): void
    {
        if ($workflow->status !== 'pending') {
            return;
        }

        $processor = $this->resolver->resolve($workflow);

        $reflection = new \ReflectionClass($processor);

        $attributes = $reflection->getAttributes(DependsOn::class);

        if ($attributes !== []) {
            /** @var DependsOn $dependsOn */
            $dependsOn = $attributes[0]->newInstance();

            $dependencies = $workflow->booking
                ->workflows()
                ->whereIn('type', $dependsOn->workflowTypes)
                ->get();

            $hasIncompleteDependency = $dependencies->contains(
                fn (BookingWorkflow $dependency) =>
                    $dependency->status !== 'completed'
            );

            if ($hasIncompleteDependency) {
                return;
            }
        }

        $claimed = BookingWorkflow::query()
            ->whereKey($workflow->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'queued',
                'message' => 'Queued for processing',
            ]);

        if ($claimed === 0) {
            return;
        }

        dispatch(new ProcessBookingWorkflowJob($workflow));
    }
}
