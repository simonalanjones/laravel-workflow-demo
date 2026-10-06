<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;

class BookingStateService
{
    public function recalculate(Booking $booking): void
    {
        $hasFailedWorkflow = $booking->workflows()
            ->where('status', 'failed')
            ->exists();

        if ($hasFailedWorkflow) {
            $booking->update([
                'status' => BookingStatus::Failed,
            ]);

            return;
        }

        $hasIncompleteWorkflow = $booking->workflows()
            ->where('status', '!=', 'completed')
            ->exists();

        if ($hasIncompleteWorkflow) {
            $booking->update([
                'status' => BookingStatus::Processing,
            ]);

            return;
        }

        if ($booking->status === BookingStatus::Processed) {
            return;
        }

        $booking->update([
            'status' => BookingStatus::Processed,
        ]);

        $booking->activities()->create([
            'type' => 'booking_processed',
            'message' => 'All booking workflows completed',
        ]);
    }
}
