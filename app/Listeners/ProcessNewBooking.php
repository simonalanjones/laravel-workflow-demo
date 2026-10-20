<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Services\Workflows\WorkflowCoordinator;

class ProcessNewBooking
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private WorkflowCoordinator $workflowCoordinator
    ) {}

    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $event->booking->loadMissing('workflowSteps');

        foreach ($event->booking->workflowSteps as $workflowStep) {
            $this->workflowCoordinator->queueIfReady($workflowStep);
        }
    }
}
