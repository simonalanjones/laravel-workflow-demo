<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class HandlesWorkflow
{
    public function __construct(
        public string $type
    ) {}
}
