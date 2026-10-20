<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Events\WorkflowStepCompleted;
use App\Models\BookingActivity;
use App\Models\BookingWorkflowStep;
use App\Services\Providers\FlightProviderClient;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use Illuminate\Support\Facades\DB;

#[HandlesWorkflow('flight_confirmation')]
class FlightConfirmationProcessor implements WorkflowProcessor
{
    public function __construct(
        private FlightProviderClient $provider
    ) {}

    public function process(BookingWorkflowStep $workflowStep, ?BookingActivity $activity = null): void
    {
        $workflowStep->update([
            'status' => 'processing',
            'message' => 'Confirming flight with provider',
        ]);

        $result = $this->provider->confirm(
            $workflowStep->booking,
            $activity,
        );

        DB::transaction(function () use ($workflowStep, $result) {
            $workflowStep->update([
                'status' => 'completed',
                'message' => 'Flight confirmed with provider'
                    .' ('.$result['provider_reference'].')',
            ]);

            $workflowStep->activities()->create([
                'booking_id' => $workflowStep->booking_id,
                'type' => 'workflow_step_completed',
                'message' => 'Flight confirmation completed',
            ]);
        });

        event(new WorkflowStepCompleted($workflowStep));
    }
}
