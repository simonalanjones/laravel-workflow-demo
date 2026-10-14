<?php

namespace App\Livewire\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Overview extends Component
{
    use WithPagination;

    public function render(): View
    {
        $bookings = Booking::with('items')
            ->latest()
            ->orderByDesc('id')
            ->paginate(5);

        $counts = [
            'pending' => Booking::where('status', BookingStatus::Pending)->count(),
            'processing' => Booking::where('status', BookingStatus::Processing)->count(),
            'processed' => Booking::where('status', BookingStatus::Processed)->count(),
            'failed' => Booking::where('status', BookingStatus::Failed)->count(),
        ];

        return view('livewire.bookings.overview', [
            'bookings' => $bookings,
            'counts' => $counts,
        ]);
    }
}
