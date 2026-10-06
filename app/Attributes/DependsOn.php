<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DependsOn
{
    public function __construct(
        public array $workflowTypes
    ) {}
}
