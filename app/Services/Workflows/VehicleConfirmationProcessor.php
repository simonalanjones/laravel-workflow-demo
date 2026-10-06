<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Events\WorkflowItemCompleted;
use App\Models\BookingWorkflow;
use App\Services\Providers\VehicleProviderClient;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use Illuminate\Support\Facades\DB;

#[HandlesWorkflow('vehicle_confirmation')]
class VehicleConfirmationProcessor implements WorkflowProcessor
{
    public function __construct(
        private VehicleProviderClient $provider
    ) {}

    public function process(BookingWorkflow $workflow): void
    {
        $workflow->update([
            'status' => 'processing',
            'message' => 'Confirming vehicle with provider',
        ]);

        $workflow->activities()->create([
            'booking_id' => $workflow->booking_id,
            'type' => 'workflow_processing',
            'message' => 'Vehicle confirmation started',
        ]);

        $result = $this->provider->confirm(
            $workflow->booking
        );

        DB::transaction(function () use ($workflow, $result) {
            $workflow->update([
                'status' => 'completed',
                'message' => 'Vehicle confirmed with provider'
                    .' ('.$result['provider_reference'].')',
            ]);

            $workflow->activities()->create([
                'booking_id' => $workflow->booking_id,
                'type' => 'workflow_completed',
                'message' => 'Vehicle confirmation completed',
            ]);
        });

        event(new WorkflowItemCompleted($workflow));
    }
}