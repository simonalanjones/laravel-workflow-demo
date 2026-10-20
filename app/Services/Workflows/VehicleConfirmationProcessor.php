<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Events\WorkflowStepCompleted;
use App\Models\BookingActivity;
use App\Models\BookingWorkflowStep;
use App\Services\Providers\VehicleProviderClient;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use Illuminate\Support\Facades\DB;

#[HandlesWorkflow('vehicle_confirmation')]
class VehicleConfirmationProcessor implements WorkflowProcessor
{
    public function __construct(
        private VehicleProviderClient $provider
    ) {}

    public function process(BookingWorkflowStep $workflowStep, ?BookingActivity $activity = null): void
    {
        $workflowStep->update([
            'status' => 'processing',
            'message' => 'Confirming vehicle with provider',
        ]);

        $result = $this->provider->confirm(
            $workflowStep->booking,
            $activity,
        );

        DB::transaction(function () use ($workflowStep, $result) {
            $workflowStep->update([
                'status' => 'completed',
                'message' => 'Vehicle confirmed with provider'
                    .' ('.$result['provider_reference'].')',
            ]);

            $workflowStep->activities()->create([
                'booking_id' => $workflowStep->booking_id,
                'type' => 'workflow_step_completed',
                'message' => 'Vehicle confirmation completed',
            ]);
        });

        event(new WorkflowStepCompleted($workflowStep));
    }
}
