<?php

namespace App\Attributes;

use Attribute;

/**
 * Declares which workflow step type a processor handles.
 *
 * For example, #[HandlesWorkflow('property_confirmation')] identifies
 * the processor for steps whose type is 'property_confirmation'.
 *
 * WorkflowProcessorResolver reads this metadata when selecting a processor.
 * The attribute does not register the processor or execute it; the class
 * must also be listed in the resolver.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HandlesWorkflow
{
    public function __construct(
        public string $type
    ) {}
}
