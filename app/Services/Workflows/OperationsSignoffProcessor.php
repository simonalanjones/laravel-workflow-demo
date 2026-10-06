<?php

namespace App\Services\Workflows;

use App\Attributes\DependsOn;
use App\Attributes\HandlesWorkflow;
use App\Models\BookingWorkflow;
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
    public function process(BookingWorkflow $workflow): void
    {
        DB::transaction(function () use ($workflow) {
            $workflow->update([
                'status' => 'processing',
                'message' => 'Preparing booking for operations review',
            ]);

            $workflow->activities()->create([
                'booking_id' => $workflow->booking_id,
                'type' => 'workflow_processing',
                'message' => 'Operations signoff started',
            ]);

            $workflow->update([
                'status' => 'awaiting_approval',
                'message' => 'Waiting for operations approval',
            ]);

            $workflow->activities()->create([
                'booking_id' => $workflow->booking_id,
                'type' => 'workflow_awaiting_approval',
                'message' => 'Operations signoff awaiting approval',
            ]);
        });
    }
}
