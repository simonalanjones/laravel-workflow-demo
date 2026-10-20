<?php

namespace App\Events;

use App\Models\BookingWorkflowStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStepCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BookingWorkflowStep $workflowStep
    ) {}
}
