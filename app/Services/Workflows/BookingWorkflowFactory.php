<?php

namespace App\Services\Workflows;

use App\Models\Booking;

class BookingWorkflowFactory
{
    public function createFor(Booking $booking, bool $requiresSignoff = true): void
    {
        foreach ($booking->items as $item) {

            $workflowType = match ($item->type) {
                'accommodation' => 'property_confirmation',
                'flight' => 'flight_confirmation',
                'car_hire' => 'vehicle_confirmation',
                default => null,
            };

            if (! $workflowType) {
                continue;
            }

            $booking->workflows()->firstOrCreate(
                [
                    'type' => $workflowType,
                ],
                [
                    'status' => 'pending',
                    'message' => 'Waiting to start '.
                        str_replace('_', ' ', $workflowType),
                ]
            );
        }

        if (! $requiresSignoff) {
            return;
        }

        $booking->workflows()->firstOrCreate(
            [
                'type' => 'operations_signoff',
            ],
            [
                'status' => 'pending',
                'message' => 'Waiting for operations signoff',
            ]
        );

    }
}
