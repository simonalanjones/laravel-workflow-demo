<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Livewire\Component;

class DashboardBookings extends Component
{
    public function render()
    {
        $bookings = Booking::with('items')
            ->latest()
            ->get();

        $counts = [
            'pending' => $bookings
                ->where('status', BookingStatus::Pending)
                ->count(),

            'processing' => $bookings
                ->where('status', BookingStatus::Processing)
                ->count(),

            'processed' => $bookings
                ->where('status', BookingStatus::Processed)
                ->count(),

            'failed' => $bookings
                ->where('status', BookingStatus::Failed)
                ->count(),
        ];

        return view('livewire.dashboard-bookings', [
            'bookings' => $bookings,
            'counts' => $counts,
        ]);
    }
}