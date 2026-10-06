<?php

namespace App\Listeners;

use App\Events\WorkflowItemCompleted;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowCoordinator;

class UpdateBookingState
{
    public function __construct(
        private BookingStateService $bookingStateService,
        private WorkflowCoordinator $workflowCoordinator
    ) {}

    public function handle(WorkflowItemCompleted $event): void
    {
        $booking = $event->workflow->booking;

        $booking->load('workflows');

        foreach ($booking->workflows as $workflow) {
            $this->workflowCoordinator->startIfReady($workflow);
        }

        $this->bookingStateService->recalculate($booking);
    }
}
