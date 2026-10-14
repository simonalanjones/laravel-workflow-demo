<?php

namespace App\Http\Controllers;

use App\Models\DemoProviderOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Lottery;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

class DemoProviderController extends Controller
{
    public function confirmProperty(Request $request): JsonResponse
    {
        return $this->confirm($request, 'Property');
    }

    public function confirmVehicle(Request $request): JsonResponse
    {
        return $this->confirm($request, 'Vehicle');
    }

    public function confirmFlight(Request $request): JsonResponse
    {
        return $this->confirm($request, 'Flight');
    }

    private function confirm(Request $request, string $provider): JsonResponse
    {
        $data = $request->validate([
            'booking_reference' => 'required|string|max:255',
            'idempotency_key' => 'sometimes|required|string|max:255',
            'simulate_network_delay' => 'sometimes|boolean',
            'simulate_lost_response' => 'sometimes|boolean',
        ]);

        if ($request->boolean('simulate_network_delay')) {
            Sleep::for(random_int(1000, 5000))->milliseconds();
        }

        $bookingReference = $data['booking_reference'];
        $payload = ['booking_reference' => $bookingReference];
        $providerOperation = DemoProviderOperation::query()->firstOrCreate([
            'provider' => $provider,
            'idempotency_key' => $data['idempotency_key'] ?? hash('sha256', 'confirm_booking:'.$bookingReference),
        ], [
            'operation' => 'confirm_booking',
            'request_payload' => $payload,
            'status' => 'processing',
        ]);

        [$response, $status, $newlyConfirmed] = DB::transaction(function () use ($providerOperation, $provider, $payload, $bookingReference): array {
            $providerOperation = DemoProviderOperation::query()->lockForUpdate()->findOrFail($providerOperation->id);

            if ($providerOperation->operation !== 'confirm_booking' || $providerOperation->request_payload !== $payload) {
                return [['message' => 'Idempotency key has already been used for a different request.'], 409, false];
            }

            if ($providerOperation->status === 'succeeded') {
                return [$providerOperation->response_payload, 200, false];
            }

            $isRandomFailure = str_contains($bookingReference, 'FAILR');
            $failureMessage = null;

            if ($isRandomFailure && Lottery::odds(1, 2)->choose()) {
                $failureMessage = $provider.' provider randomly failed';
            } elseif (! $isRandomFailure && $provider === 'Flight' && str_contains($bookingReference, 'FAIL')) {
                $failureMessage = 'Flight provider is temporarily unavailable';
            }

            if ($failureMessage !== null) {
                $response = ['status' => 'unavailable', 'message' => $failureMessage];
                $providerOperation->update(['status' => 'failed', 'response_payload' => $response]);

                return [$response, 503, false];
            }

            $response = [
                'status' => 'confirmed',
                'provider_reference' => strtoupper($provider).'-'.Str::uuid(),
            ];
            $providerOperation->update(['status' => 'succeeded', 'response_payload' => $response]);

            return [$response, 200, true];
        }, attempts: 3);

        if ($newlyConfirmed && $request->boolean('simulate_lost_response')) {
            return response()->json([
                'status' => 'unavailable',
                'code' => 'simulated_response_lost',
                'message' => 'Provider confirmed the request, but the response was lost. Retry with the same idempotency key.',
            ], 503);
        }

        return response()->json($response, $status);
    }
}
