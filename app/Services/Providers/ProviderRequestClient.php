<?php

namespace App\Services\Providers;

use App\Exceptions\ProviderRequestFailedException;
use App\Exceptions\ProviderResponseLostException;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\ProviderRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Track outgoing confirmations and the responses received by this application.
 * The demo provider keeps its own separate operation records; this client
 * must not read those records to infer an outcome it has not received.
 */
class ProviderRequestClient
{
    public function confirm(Booking $booking, string $provider, string $endpoint, ?BookingActivity $activity = null): array
    {
        $type = match ($provider) {
            'Property' => 'property_confirmation',
            'Flight' => 'flight_confirmation',
            'Vehicle' => 'vehicle_confirmation',
        };
        $workflowStep = $booking->workflowSteps()->where('type', $type)->sole();

        if ($activity !== null && ($activity->booking_id !== $booking->id || $activity->booking_workflow_step_id !== $workflowStep->id)) {
            throw new RuntimeException('Provider activity does not belong to this workflow step.');
        }

        /**
         * Persist one logical confirmation request before making the HTTP call.
         * Retries find this same record and send its saved key and business
         * payload, even if the booking reference has since changed.
         *
         * The provider must honour the key to prevent duplicate operations.
         * A different operation, such as cancellation, would need its own key.
         */
        $request = ProviderRequest::query()->firstOrCreate([
            'booking_workflow_step_id' => $workflowStep->id,
            'provider' => $provider,
            'operation' => 'confirm_booking',
        ], [
            'idempotency_key' => (string) Str::uuid(),
            'request_payload' => ['booking_reference' => $booking->reference],
            'status' => 'processing',
        ]);

        if ($request->booking_workflow_step_id !== null && $request->booking_workflow_step_id !== $workflowStep->id) {
            throw new RuntimeException('Provider request belongs to a different workflow step.');
        }

        $request->update(['booking_workflow_step_id' => $workflowStep->id, 'status' => 'processing', 'response_payload' => null]);

        /**
         * Keep each HTTP attempt separately so retry history survives changes
         * to the logical request's latest status and response. When invoked by
         * a workflow job, link the attempt to that job's attempt-start activity.
         */
        $attempt = $request->attempts()->create([
            'booking_activity_id' => $activity?->id,
            'started_at' => now(),
            'status' => 'processing',
        ]);
        $started = hrtime(true);

        try {
            $response = Http::connectTimeout(5)->timeout(15)->post(
                url('/api/demo/providers/'.$endpoint.'/confirm'),
                [
                    ...$request->request_payload,
                    'idempotency_key' => $request->idempotency_key,
                    'simulate_network_delay' => $booking->simulate_network_delay,
                    'simulate_lost_response' => $booking->simulate_lost_response,
                ],
            );
        } catch (ConnectionException $exception) {
            /**
             * A transport error does not prove the provider rejected the work:
             * it may have completed the operation before the response was lost.
             * Record an unknown outcome and let a retry reuse the saved key.
             */
            $request->update(['status' => 'unknown']);
            $attempt->update([
                'status' => 'unknown',
                'finished_at' => now(),
                'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        /**
         * This special 503 simulates a response lost after provider success;
         * it is not an actual dropped connection. Deliberately treat it as
         * missing evidence rather than storing the simulation response as
         * the operation's result. A retry retrieves the provider's saved result.
         */
        if ($response->status() === 503 && $response->json('code') === 'simulated_response_lost') {
            $request->update(['status' => 'unknown']);

            $exception = new ProviderResponseLostException;
            $attempt->update([
                'status' => 'unknown',
                'finished_at' => now(),
                'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $attempt->update([
            'status' => $response->successful() ? 'succeeded' : 'failed',
            'http_status' => $response->status(),
            'response_body' => $response->body(),
            'finished_at' => now(),
            'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
        ]);

        $request->update([
            'status' => $response->successful() ? 'succeeded' : 'failed',
            'response_payload' => $response->json(),
        ]);

        if (! $response->successful()) {
            throw new ProviderRequestFailedException($response->status());
        }

        return $response->json();
    }
}
