<?php

namespace App\Services\Workflows;

use App\Attributes\HandlesWorkflow;
use App\Events\WorkflowItemCompleted;
use App\Models\BookingWorkflow;
use App\Services\Providers\FlightProviderClient;
use App\Services\Workflows\Contracts\WorkflowProcessor;
use Illuminate\Support\Facades\DB;

#[HandlesWorkflow('flight_confirmation')]
class FlightConfirmationProcessor implements WorkflowProcessor
{
    public function __construct(
        private FlightProviderClient $provider
    ) {}

    public function process(BookingWorkflow $workflow): void
    {
        $workflow->update([
            'status' => 'processing',
            'message' => 'Confirming flight with provider',
        ]);

        $workflow->activities()->create([
            'booking_id' => $workflow->booking_id,
            'type' => 'workflow_processing',
            'message' => 'Flight confirmation started',
        ]);

        $result = $this->provider->confirm(
            $workflow->booking
        );

        DB::transaction(function () use ($workflow, $result) {
            $workflow->update([
                'status' => 'completed',
                'message' => 'Flight confirmed with provider'
                    .' ('.$result['provider_reference'].')',
            ]);

            $workflow->activities()->create([
                'booking_id' => $workflow->booking_id,
                'type' => 'workflow_completed',
                'message' => 'Flight confirmation completed',
            ]);
        });

        event(new WorkflowItemCompleted($workflow));
    }
}
