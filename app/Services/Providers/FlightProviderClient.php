<?php

namespace App\Services\Providers;

use App\Models\Booking;
use App\Models\BookingActivity;

class FlightProviderClient
{
    public function __construct(private ProviderRequestClient $requests) {}

    public function confirm(Booking $booking, ?BookingActivity $activity = null): array
    {
        return $this->requests->confirm($booking, 'Flight', 'flights', $activity);
    }
}
