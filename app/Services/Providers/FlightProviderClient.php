<?php

namespace App\Services\Providers;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FlightProviderClient
{
    public function confirm(Booking $booking): array
    {
        $response = Http::post(
            url('/api/demo/providers/flights/confirm'),
            [
                'booking_reference' => $booking->reference,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                'Flight provider request failed'
            );
        }

        return $response->json();
    }
}
