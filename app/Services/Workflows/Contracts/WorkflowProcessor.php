<?php

namespace App\Services\Workflows\Contracts;

use App\Models\BookingActivity;
use App\Models\BookingWorkflow;

interface WorkflowProcessor
{
    public function process(BookingWorkflow $workflow, ?BookingActivity $activity = null): void;
}
