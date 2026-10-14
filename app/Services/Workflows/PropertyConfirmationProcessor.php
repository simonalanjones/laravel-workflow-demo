<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Events\WorkflowItemCompleted;
use App\Models\BookingActivity;
use App\Models\BookingWorkflow;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use Illuminate\Support\Facades\DB;

#[HandlesWorkflow('property_confirmation')]
class PropertyConfirmationProcessor implements WorkflowProcessor
{
    public function __construct(
        private PropertyProviderClient $provider
    ) {}

    public function process(BookingWorkflow $workflow, ?BookingActivity $activity = null): void
    {
        $workflow->update([
            'status' => 'processing',
            'message' => 'Checking property availability',
        ]);

        $result = $this->provider->confirm(
            $workflow->booking,
            $activity,
        );

        DB::transaction(function () use ($workflow, $result) {
            $workflow->update([
                'status' => 'completed',
                'message' => 'Property availability confirmed'
                    .' ('.$result['provider_reference'].')',
            ]);

            $workflow->activities()->create([
                'booking_id' => $workflow->booking_id,
                'type' => 'workflow_item_completed',
                'message' => 'Property confirmation completed',
            ]);
        });

        event(new WorkflowItemCompleted($workflow));
    }
}
