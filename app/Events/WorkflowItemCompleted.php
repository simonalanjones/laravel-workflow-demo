<?php

namespace App\Events;

use App\Models\BookingWorkflow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowItemCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BookingWorkflow $workflow
    ) {}
}
