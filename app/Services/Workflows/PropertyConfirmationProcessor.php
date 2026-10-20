<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Events\WorkflowStepCompleted;
use App\Models\BookingActivity;
use App\Models\BookingWorkflowStep;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use Illuminate\Support\Facades\DB;

#[HandlesWorkflow('property_confirmation')]
class PropertyConfirmationProcessor implements WorkflowProcessor
{
    public function __construct(
        private PropertyProviderClient $provider
    ) {}

    public function process(BookingWorkflowStep $workflowStep, ?BookingActivity $activity = null): void
    {
        $workflowStep->update([
            'status' => 'processing',
            'message' => 'Checking property availability',
        ]);

        $result = $this->provider->confirm(
            $workflowStep->booking,
            $activity,
        );

        DB::transaction(function () use ($workflowStep, $result) {
            $workflowStep->update([
                'status' => 'completed',
                'message' => 'Property availability confirmed'
                    .' ('.$result['provider_reference'].')',
            ]);

            $workflowStep->activities()->create([
                'booking_id' => $workflowStep->booking_id,
                'type' => 'workflow_step_completed',
                'message' => 'Property confirmation completed',
            ]);
        });

        event(new WorkflowStepCompleted($workflowStep));
    }
}
