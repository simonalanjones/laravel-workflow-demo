<?php

namespace App\Listeners;

use App\Events\WorkflowStepCompleted;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowCoordinator;

class UpdateBookingState
{
    public function __construct(
        private BookingStateService $bookingStateService,
        private WorkflowCoordinator $workflowCoordinator
    ) {}

    public function handle(WorkflowStepCompleted $event): void
    {
        $booking = $event->workflowStep->booking;

        $booking->load('workflowSteps');

        foreach ($booking->workflowSteps as $workflowStep) {
            $this->workflowCoordinator->queueIfReady($workflowStep);
        }

        $this->bookingStateService->recalculate($booking);
    }
}
