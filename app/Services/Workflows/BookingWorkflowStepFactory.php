<?php

namespace App\Services\Workflows;

use App\Models\Booking;

class BookingWorkflowStepFactory
{
    /**
     * Define the steps this booking needs; the coordinator queues them later.
     * Operations signoff is optional and is represented by its own step.
     */
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

            /**
             * The current design groups confirmation work by type per booking,
             * not by individual booking item. Repeated items of the same type
             * reuse the existing step without resetting its status.
             *
             * Confirming each item independently would require steps linked
             * to individual items, rather than matching only on type.
             */
            $booking->workflowSteps()->firstOrCreate([
                'type' => $workflowType,
            ], [
                'status' => 'pending',
                'message' => 'Waiting to start '.str_replace('_', ' ', $workflowType),
            ]);
        }

        if (! $requiresSignoff) {
            return;
        }

        $booking->workflowSteps()->firstOrCreate([
            'type' => 'operations_signoff',
        ], [
            'status' => 'pending',
            'message' => 'Waiting for operations signoff',
        ]);
    }
}
