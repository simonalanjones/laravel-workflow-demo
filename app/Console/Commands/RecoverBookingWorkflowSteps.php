<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingWorkflowStep;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bookings:recover')]
#[Description('Queue ready pending workflow steps and reconcile unfinished booking statuses')]
class RecoverBookingWorkflowSteps extends Command
{
    /**
     * Repair progress missed by creation or completion event handling.
     * Events normally advance bookings immediately; this scheduled command
     * provides another opportunity after a crash or failed queue insertion.
     */
    public function handle(
        WorkflowCoordinator $coordinator,
        BookingStateService $bookingStateService,
    ): int {

        /**
         * Recheck pending steps through the coordinator's dependency rules.
         * Failed steps are not reset for retry, and awaiting-approval steps
         * are not approved automatically.
         *
         * lazyById() fetches batches of 100 but yields one step per iteration.
         * Advancing by ID avoids skipping rows as queued steps stop matching
         * the pending filter. It limits memory use, not total execution time.
         */
        $workflowSteps = BookingWorkflowStep::query()
            ->where('status', 'pending')
            ->with('booking')
            ->lazyById(100);

        foreach ($workflowSteps as $workflowStep) {
            $coordinator->queueIfReady($workflowStep);
        }

        /**
         * A final step may have completed before its event updated the booking.
         * Such a booking has no pending steps for the first pass to discover,
         * so recalculate unfinished booking statuses separately.
         *
         * This also uses ID-based batches because marking bookings processed
         * removes them from the matching results as we iterate.
         */
        $bookings = Booking::query()
            ->where('status', '!=', BookingStatus::Processed->value)
            ->lazyById(100);

        foreach ($bookings as $booking) {
            $bookingStateService->recalculate($booking);
        }

        return self::SUCCESS;
    }
}
