<?php

namespace App\Livewire\Bookings;

use App\Events\WorkflowStepCompleted;
use App\Models\Booking;
use App\Models\BookingWorkflowStep;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Detail extends Component
{
    public Booking $booking;

    public ?int $selectedWorkflowStepId = null;

    public function mount(Booking $booking): void
    {
        $this->booking = $booking;
    }

    public function render(): View
    {
        $this->booking->refresh();

        $this->booking->load([
            'items',
            'workflowSteps.activities' => fn ($query) => $query->oldest(),
            'activities' => fn ($query) => $query->oldest(),
        ]);

        $selectedWorkflowStep = $this->selectedWorkflowStepId
            ? $this->booking->workflowSteps->firstWhere(
                'id',
                $this->selectedWorkflowStepId
            )
            : null;

        $activities = $selectedWorkflowStep
            ? $selectedWorkflowStep->activities
            : $this->booking->activities;

        $activities->load('providerAttempt.providerRequest');

        $activities = $activities->whereNotIn('type', [
            'workflow_processing', 'workflow_step_processing',
            'processing_started', 'booking_processing_complete', 'booking_processing_failed',
        ]);

        return view('livewire.bookings.detail', [
            'selectedWorkflowStep' => $selectedWorkflowStep,
            'activities' => $activities,
            'workflowProgress' => $this->workflowProgress(),
        ]);
    }

    /**
     * @return array{isComplete: bool, taskCount: int, completedCount: int, failedTasks: Collection, approvalTask: ?BookingWorkflowStep, summaryTitle: string, summaryDescription: string, summaryLabel: string}
     */
    private function workflowProgress(): array
    {
        $taskCount = $this->booking->workflowSteps->count();
        $completedCount = $this->booking->workflowSteps->where('status', 'completed')->count();
        $failedTasks = $this->booking->workflowSteps->where('status', 'failed');
        $approvalTask = $this->booking->workflowSteps->first(fn ($task) => $task->type === 'operations_signoff' && $task->status === 'awaiting_approval');

        $isComplete = $this->booking->status->value === 'processed' && $failedTasks->isEmpty() && ! $approvalTask;

        if ($failedTasks->isNotEmpty()) {
            $summaryTitle = $failedTasks->count() === 1 ? 'A workflow step needs attention' : 'Workflow steps need attention';
            $summaryDescription = $failedTasks->count() === 1
                ? ucfirst(str_replace('_', ' ', $failedTasks->first()->type)).' could not be completed. Review the details and retry when ready.'
                : $failedTasks->count().' workflow steps could not be completed. Review each failed step below.';
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
        } elseif ($this->booking->workflowSteps->contains('status', 'processing')) {
            $summaryTitle = 'Work is in progress';
            $summaryDescription = 'Confirmation steps are running. Progress updates automatically here.';
            $summaryLabel = 'Current state';
        } elseif ($this->booking->workflowSteps->contains('status', 'queued')) {
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

    public function retryWorkflowStep(
        int $workflowStepId,
        BookingStateService $bookingStateService,
        WorkflowCoordinator $workflowCoordinator
    ): void {
        $workflowStep = $this->booking
            ->workflowSteps()
            ->findOrFail($workflowStepId);

        if ($workflowStep->status !== 'failed') {
            return;
        }

        $workflowStep->update([
            'status' => 'pending',
            'message' => 'Waiting to retry workflow step',
        ]);

        $workflowStep->activities()->create([
            'booking_id' => $workflowStep->booking_id,
            'type' => 'workflow_step_retry_requested',
            'message' => ucfirst(
                str_replace('_', ' ', $workflowStep->type)
            ).' manually retried',
        ]);

        $bookingStateService->recalculate(
            $workflowStep->booking
        );

        $workflowCoordinator->queueIfReady($workflowStep);
    }

    public function approveWorkflowStep(int $workflowStepId): void
    {
        $workflowStep = $this->booking
            ->workflowSteps()
            ->findOrFail($workflowStepId);

        // The workflow may already have been completed but the completion
        // event may not have successfully reconciled the parent booking.
        if ($workflowStep->status === 'completed') {
            event(new WorkflowStepCompleted($workflowStep));

            return;
        }

        if ($workflowStep->status !== 'awaiting_approval') {
            return;
        }

        DB::transaction(function () use ($workflowStep) {
            $workflowStep->update([
                'status' => 'completed',
                'message' => 'Operations signoff approved',
            ]);

            $workflowStep->activities()->create([
                'booking_id' => $workflowStep->booking_id,
                'type' => 'workflow_step_approved',
                'message' => 'Operations signoff approved',
            ]);
        });

        event(new WorkflowStepCompleted($workflowStep));
    }

    public function selectWorkflowStep(int $workflowStepId): void
    {
        $exists = $this->booking
            ->workflowSteps()
            ->whereKey($workflowStepId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->selectedWorkflowStepId = $workflowStepId;
    }

    public function showAllActivity(): void
    {
        $this->selectedWorkflowStepId = null;
    }
}
