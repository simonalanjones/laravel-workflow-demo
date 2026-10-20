<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Models\BookingWorkflowStep;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use ReflectionClass;
use RuntimeException;

class WorkflowProcessorResolver
{
    /**
     * The resolver searches only these processor classes. Add new processors
     * here as well as declaring the step type they handle with HandlesWorkflow.
     *
     * @var list<class-string<WorkflowProcessor>>
     */
    private array $processors = [
        PropertyConfirmationProcessor::class,
        FlightConfirmationProcessor::class,
        VehicleConfirmationProcessor::class,
        OperationsSignoffProcessor::class,
    ];

    /**
     * Find the processor responsible for this step's type.
     *
     * A processor declares its supported type above its class definition:
     * #[HandlesWorkflow('property_confirmation')]
     *
     * HandlesWorkflow is a custom PHP attribute: a label attached to the class.
     * Reflection lets us inspect that label without running the processor.
     * newInstance() creates the attribute object so we can read its type.
     *
     * For a step whose database type is 'property_confirmation', that label
     * matches and we return a PropertyConfirmationProcessor object.
     */
    public function resolve(BookingWorkflowStep $workflowStep): WorkflowProcessor
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

            if ($attribute->type === $workflowStep->type) {
                /**
                 * Resolve the processor through Laravel's container so its
                 * constructor dependencies are supplied automatically.
                 * This returns the object without calling process().
                 */
                return app($processorClass);
            }
        }

        throw new RuntimeException(
            "No processor found for workflow step type: {$workflowStep->type}"
        );
    }
}
