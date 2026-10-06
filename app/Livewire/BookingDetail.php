<?php

namespace App\Livewire;

use App\Events\WorkflowItemCompleted;
use App\Models\Booking;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;



class BookingDetail extends Component
{
    public Booking $booking;
    public ?int $selectedWorkflowId = null;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function render()
    {
        $this->booking->refresh();

        $this->booking->load([
            'items',
            'workflows.activities' => fn ($query) => $query->oldest(),
            'activities' => fn ($query) => $query->oldest(),
        ]);

        $selectedWorkflow = $this->selectedWorkflowId
            ? $this->booking->workflows->firstWhere(
                'id',
                $this->selectedWorkflowId
            )
            : null;

        $activities = $selectedWorkflow
            ? $selectedWorkflow->activities
            : $this->booking->activities;

        return view('livewire.booking-detail', [
            'selectedWorkflow' => $selectedWorkflow,
            'activities' => $activities,
        ]);
    }

    public function retryWorkflow(
        int $workflowId,
        BookingStateService $bookingStateService,
        WorkflowCoordinator $workflowCoordinator
    ): void {
        $workflow = $this->booking
            ->workflows()
            ->findOrFail($workflowId);

        if ($workflow->status !== 'failed') {
            return;
        }

        $workflow->update([
            'status' => 'pending',
            'message' => 'Waiting to retry workflow',
        ]);

        $workflow->activities()->create([
            'booking_id' => $workflow->booking_id,
            'type' => 'workflow_retried',
            'message' => ucfirst(
                str_replace('_', ' ', $workflow->type)
            ) . ' manually retried',
        ]);

        $bookingStateService->recalculate(
            $workflow->booking
        );

        $workflowCoordinator->startIfReady($workflow);
    }

    public function approveWorkflow(int $workflowId): void
    {
        $workflow = $this->booking
            ->workflows()
            ->findOrFail($workflowId);

        // The workflow may already have been completed but the completion
        // event may not have successfully reconciled the parent booking.
        if ($workflow->status === 'completed') {
            event(new WorkflowItemCompleted($workflow));

            return;
        }

        if ($workflow->status !== 'awaiting_approval') {
            return;
        }

        DB::transaction(function () use ($workflow) {
            $workflow->update([
                'status' => 'completed',
                'message' => 'Operations signoff approved',
            ]);

            $workflow->activities()->create([
                'booking_id' => $workflow->booking_id,
                'type' => 'workflow_approved',
                'message' => 'Operations signoff approved',
            ]);
        });

        event(new WorkflowItemCompleted($workflow));
    }

    public function selectWorkflow(int $workflowId): void
    {
        $exists = $this->booking
            ->workflows()
            ->whereKey($workflowId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->selectedWorkflowId = $workflowId;
    }

    public function showAllActivity(): void
    {
        $this->selectedWorkflowId = null;
    }
}
