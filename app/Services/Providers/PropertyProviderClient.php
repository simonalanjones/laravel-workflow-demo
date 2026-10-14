<?php

namespace App\Services\Providers;

use App\Models\Booking;
use App\Models\BookingActivity;

class PropertyProviderClient
{
    public function __construct(private ProviderRequestClient $requests) {}

    public function confirm(Booking $booking, ?BookingActivity $activity = null): array
    {
        return $this->requests->confirm($booking, 'Property', 'property', $activity);
    }
}
