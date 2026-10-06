<?php

namespace App\Services\Workflows\Contracts;

use App\Models\BookingWorkflow;

interface WorkflowProcessor
{
    public function process(BookingWorkflow $workflow): void;
}
