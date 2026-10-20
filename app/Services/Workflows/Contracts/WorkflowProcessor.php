<?php

namespace App\Services\Workflows\Contracts;

use App\Models\BookingActivity;
use App\Models\BookingWorkflowStep;

interface WorkflowProcessor
{
    public function process(BookingWorkflowStep $workflowStep, ?BookingActivity $activity = null): void;
}
