<?php

namespace App\Livewire\Bookings;

use App\Events\WorkflowItemCompleted;
use App\Models\Booking;
use App\Models\BookingWorkflow;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Detail extends Component
{
    public Booking $booking;

    public ?int $selectedWorkflowId = null;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function render(): View
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

        $activities->load('providerAttempt.providerRequest');

        $activities = $activities->whereNotIn('type', [
            'workflow_processing', 'workflow_item_processing',
            'processing_started', 'booking_processing_complete', 'booking_processing_failed',
        ]);

        return view('livewire.bookings.detail', [
            'selectedWorkflow' => $selectedWorkflow,
            'activities' => $activities,
            'workflowProgress' => $this->workflowProgress(),
        ]);
    }

    /**
     * @return array{isComplete: bool, taskCount: int, completedCount: int, failedTasks: Collection, approvalTask: ?BookingWorkflow, summaryTitle: string, summaryDescription: string, summaryLabel: string}
     */
    private function workflowProgress(): array
    {
        $taskCount = $this->booking->workflows->count();
        $completedCount = $this->booking->workflows->where('status', 'completed')->count();
        $failedTasks = $this->booking->workflows->where('status', 'failed');
        $approvalTask = $this->booking->workflows->first(fn ($task) => $task->type === 'operations_signoff' && $task->status === 'awaiting_approval');

        $isComplete = $this->booking->status->value === 'processed' && $failedTasks->isEmpty() && ! $approvalTask;

        if ($failedTasks->isNotEmpty()) {
            $summaryTitle = $failedTasks->count() === 1 ? 'A workflow item needs attention' : 'Workflows need attention';
            $summaryDescription = $failedTasks->count() === 1
                ? ucfirst(str_replace('_', ' ', $failedTasks->first()->type)).' could not be completed. Review the details and retry when ready.'
                : $failedTasks->count().' workflows could not be completed. Review each failed step below.';
            $summaryLabel = 'Action needed';
        } elseif ($approvalTask) {
            $summaryTitle = 'Ready for your approval';
            $summaryDescription = 'Review the booking details, then approve operations signoff to complete this step.';
            $summaryLabel = 'Next step';
        } elseif ($isComplete) {
            $summaryTitle = 'Booking complete';
            $summaryDescription = 'All workflow steps are complete. No further action is needed.';
            $summaryLabel = '';
        } elseif ($taskCount > 0 && $completedCount === $taskCount) {
            $summaryTitle = 'Finalising booking';
            $summaryDescription = 'All workflow steps are complete. Waiting for the booking status to update.';
            $summaryLabel = 'Current state';
        } elseif ($this->booking->workflows->contains('status', 'processing')) {
            $summaryTitle = 'Work is in progress';
            $summaryDescription = 'Confirmation steps are running. Progress updates automatically here.';
            $summaryLabel = 'Current state';
        } elseif ($this->booking->workflows->contains('status', 'queued')) {
            $summaryTitle = 'Queued for processing';
            $summaryDescription = 'The next steps are queued and will begin when a worker picks them up.';
            $summaryLabel = 'Current state';
        } else {
            $summaryTitle = 'Waiting for the next step';
            $summaryDescription = 'Workflow steps will begin when they are ready. This page updates automatically.';
            $summaryLabel = 'Current state';
        }

        return compact(
            'isComplete', 'taskCount', 'completedCount', 'failedTasks', 'approvalTask',
            'summaryTitle', 'summaryDescription', 'summaryLabel',
        );
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
            'type' => 'workflow_item_retry_requested',
            'message' => ucfirst(
                str_replace('_', ' ', $workflow->type)
            ).' manually retried',
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
                'type' => 'workflow_item_approved',
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
