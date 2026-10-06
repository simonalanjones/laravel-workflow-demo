<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Models\BookingWorkflow;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use ReflectionClass;
use RuntimeException;

class WorkflowProcessorResolver
{
    private array $processors = [
        PropertyConfirmationProcessor::class,
        FlightConfirmationProcessor::class,
        VehicleConfirmationProcessor::class,
        OperationsSignoffProcessor::class,
    ];

    public function resolve(BookingWorkflow $workflow): WorkflowProcessor
    {
        foreach ($this->processors as $processorClass) {
            $reflection = new ReflectionClass($processorClass);

            $attributes = $reflection->getAttributes(
                HandlesWorkflow::class
            );

            if (empty($attributes)) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();

            if ($attribute->type === $workflow->type) {
                return app($processorClass);
            }
        }

        throw new RuntimeException(
            "No processor found for workflow type: {$workflow->type}"
        );
    }
}
