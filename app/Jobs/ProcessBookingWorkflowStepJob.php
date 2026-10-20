<?php

namespace App\Jobs;

use App\Events\WorkflowStepCompleted;
use App\Exceptions\ProviderRequestFailedException;
use App\Exceptions\ProviderResponseLostException;
use App\Models\BookingWorkflowStep;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowProcessorResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Execute one persisted workflow step through its matching processor.
 * The queue manages delivery and retries; the step records business progress.
 */
class ProcessBookingWorkflowStepJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public BookingWorkflowStep $workflowStep
    ) {}

    public function handle(
        WorkflowProcessorResolver $resolver,
        BookingStateService $bookingStateService
    ): void {

        /**
         * A previous attempt may have saved completion before crashing during
         * event delivery or before the queue acknowledged the job. Re-emit
         * completion so listeners can check dependent steps and booking status,
         * without calling the processor or provider again.
         */
        if ($this->workflowStep->status === 'completed') {
            event(new WorkflowStepCompleted($this->workflowStep));

            return;
        }

        /**
         * Automatic retries can resume a step left processing by an earlier
         * attempt. Failed steps need an explicit retry through the coordinator;
         * awaiting-approval steps must remain under human control.
         */
        if (! in_array($this->workflowStep->status, ['queued', 'processing'], true)) {
            return;
        }

        $this->workflowStep->update([
            'status' => 'processing',
            'message' => 'Workflow processing started',
        ]);

        $bookingStateService->recalculate(
            $this->workflowStep->booking
        );

        $activity = $this->workflowStep->activities()->create([
            'booking_id' => $this->workflowStep->booking_id,
            'type' => 'workflow_step_attempt_started',
            'message' => sprintf(
                '%s — attempt %d started',
                ucfirst(str_replace('_', ' ', $this->workflowStep->type)),
                $this->attempts()
            ),
        ]);

        try {
            $processor = $resolver->resolve($this->workflowStep);

            $processor->process($this->workflowStep, $activity);
        } catch (\Throwable $exception) {
            if ($exception instanceof ProviderResponseLostException) {
                $this->workflowStep->update([
                    'message' => $this->attempts() < $this->tries
                        ? 'Response lost — retrying to retrieve confirmation'
                        : 'Response lost — automatic retries exhausted. Retry to retrieve confirmation.',
                ]);
            }

            $this->workflowStep->activities()->create([
                'booking_id' => $this->workflowStep->booking_id,
                'type' => 'workflow_step_attempt_failed',
                'message' => $exception instanceof ProviderRequestFailedException
                    ? sprintf('%s — attempt %d failed (HTTP %d)',
                        ucfirst(str_replace('_', ' ', $this->workflowStep->type)),
                        $this->attempts(),
                        $exception->httpStatus,
                    )
                    : sprintf(
                        '%s — attempt %d failed: %s',
                        ucfirst(str_replace('_', ' ', $this->workflowStep->type)),
                        $this->attempts(),
                        $exception->getMessage()
                    ),
            ]);

            /**
             * Recording an unsuccessful attempt must not make the queue think
             * the job succeeded. Rethrow so Laravel applies its retry policy
             * and invokes failed() if the job is ultimately marked failed.
             */
            throw $exception;
        }
    }

    /**
     * Record terminal job failure, rather than marking the step failed after
     * every unsuccessful attempt while automatic retries are still available.
     */
    public function failed(?\Throwable $exception): void
    {
        $this->workflowStep->update([
            'status' => 'failed',
            'message' => $exception?->getMessage() ?? 'Workflow processing failed',
        ]);

        $this->workflowStep->activities()->create([
            'booking_id' => $this->workflowStep->booking_id,
            'type' => 'workflow_step_failed',
            'message' => sprintf(
                '%s failed: %s',
                str_replace('_', ' ', ucfirst($this->workflowStep->type)),
                $exception?->getMessage() ?? 'Unknown error'
            ),
        ]);

        app(BookingStateService::class)->recalculate(
            $this->workflowStep->booking
        );
    }

    /**
     * Prevent simultaneous executions for the same step while the lock is held.
     * This is separate from the coordinator's dispatch claim and does not
     * replace provider idempotency when an operation is retried after a crash.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('booking-workflow-'.$this->workflowStep->id))
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }
}
