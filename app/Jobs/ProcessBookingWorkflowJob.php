<?php

namespace App\Jobs;

use App\Events\WorkflowItemCompleted;
use App\Exceptions\ProviderRequestFailedException;
use App\Exceptions\ProviderResponseLostException;
use App\Models\BookingWorkflow;
use App\Services\BookingStateService;
use App\Services\Workflows\WorkflowProcessorResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessBookingWorkflowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public BookingWorkflow $workflow
    ) {}

    public function handle(
        WorkflowProcessorResolver $resolver,
        BookingStateService $bookingStateService
    ): void {

        if ($this->workflow->status === 'completed') {
            event(new WorkflowItemCompleted($this->workflow));

            return;
        }

        if (! in_array($this->workflow->status, ['queued', 'processing'], true)) {
            return;
        }

        $this->workflow->update([
            'status' => 'processing',
            'message' => 'Workflow processing started',
        ]);

        $bookingStateService->recalculate(
            $this->workflow->booking
        );

        $activity = $this->workflow->activities()->create([
            'booking_id' => $this->workflow->booking_id,
            'type' => 'workflow_item_attempt_started',
            'message' => sprintf(
                '%s — attempt %d started',
                ucfirst(str_replace('_', ' ', $this->workflow->type)),
                $this->attempts()
            ),
        ]);

        try {
            $processor = $resolver->resolve($this->workflow);

            $processor->process($this->workflow, $activity);
        } catch (\Throwable $exception) {
            if ($exception instanceof ProviderResponseLostException) {
                $this->workflow->update([
                    'message' => $this->attempts() < $this->tries
                        ? 'Response lost — retrying to retrieve confirmation'
                        : 'Response lost — automatic retries exhausted. Retry to retrieve confirmation.',
                ]);
            }

            $this->workflow->activities()->create([
                'booking_id' => $this->workflow->booking_id,
                'type' => 'workflow_item_attempt_failed',
                'message' => $exception instanceof ProviderRequestFailedException
                    ? sprintf('%s — attempt %d failed (HTTP %d)',
                        ucfirst(str_replace('_', ' ', $this->workflow->type)),
                        $this->attempts(),
                        $exception->httpStatus,
                    )
                    : sprintf(
                        '%s — attempt %d failed: %s',
                        ucfirst(str_replace('_', ' ', $this->workflow->type)),
                        $this->attempts(),
                        $exception->getMessage()
                    ),
            ]);

            throw $exception;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $this->workflow->update([
            'status' => 'failed',
            'message' => $exception?->getMessage() ?? 'Workflow processing failed',
        ]);

        $this->workflow->activities()->create([
            'booking_id' => $this->workflow->booking_id,
            'type' => 'workflow_item_failed',
            'message' => sprintf(
                '%s failed: %s',
                str_replace('_', ' ', ucfirst($this->workflow->type)),
                $exception?->getMessage() ?? 'Unknown error'
            ),
        ]);

        app(BookingStateService::class)->recalculate(
            $this->workflow->booking
        );
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('booking-workflow-'.$this->workflow->id))
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }
}
