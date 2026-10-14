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

class ProviderRequestClient
{
    public function confirm(Booking $booking, string $provider, string $endpoint, ?BookingActivity $activity = null): array
    {
        $type = match ($provider) {
            'Property' => 'property_confirmation',
            'Flight' => 'flight_confirmation',
            'Vehicle' => 'vehicle_confirmation',
        };
        $workflow = $booking->workflows()->where('type', $type)->sole();

        if ($activity !== null && ($activity->booking_id !== $booking->id || $activity->booking_workflow_id !== $workflow->id)) {
            throw new RuntimeException('Provider activity does not belong to this workflow item.');
        }

        $request = ProviderRequest::query()->firstOrCreate([
            'booking_workflow_id' => $workflow->id,
            'provider' => $provider,
            'operation' => 'confirm_booking',
        ], [
            'idempotency_key' => (string) Str::uuid(),
            'request_payload' => ['booking_reference' => $booking->reference],
            'status' => 'processing',
        ]);

        if ($request->booking_workflow_id !== null && $request->booking_workflow_id !== $workflow->id) {
            throw new RuntimeException('Provider request belongs to a different workflow item.');
        }

        $request->update(['booking_workflow_id' => $workflow->id, 'status' => 'processing', 'response_payload' => null]);

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
            $request->update(['status' => 'unknown']);
            $attempt->update([
                'status' => 'unknown',
                'finished_at' => now(),
                'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

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
