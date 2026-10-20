<?php

namespace App\Services\Workflows;

use App\Attributes\DependsOn;
use App\Attributes\HandlesWorkflow;
use App\Models\BookingActivity;
use App\Models\BookingWorkflowStep;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use Illuminate\Support\Facades\DB;

#[HandlesWorkflow('operations_signoff')]
#[DependsOn([
    'property_confirmation',
    'flight_confirmation',
    'vehicle_confirmation',
])]
class OperationsSignoffProcessor implements WorkflowProcessor
{
    public function process(BookingWorkflowStep $workflowStep, ?BookingActivity $activity = null): void
    {
        DB::transaction(function () use ($workflowStep) {
            $workflowStep->update([
                'status' => 'processing',
                'message' => 'Preparing booking for operations review',
            ]);

            $workflowStep->update([
                'status' => 'awaiting_approval',
                'message' => 'Waiting for operations approval',
            ]);

            $workflowStep->activities()->create([
                'booking_id' => $workflowStep->booking_id,
                'type' => 'workflow_step_awaiting_approval',
                'message' => 'Operations signoff awaiting approval',
            ]);
        });
    }
}
