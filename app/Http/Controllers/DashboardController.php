<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $bookings = Booking::with('items')
            ->latest()
            ->get();

        $counts = [
            'pending' => $bookings->where('status', BookingStatus::Pending)->count(),
            'processing' => $bookings->where('status', BookingStatus::Processing)->count(),
            'processed' => $bookings->where('status', BookingStatus::Processed)->count(),
            'failed' => $bookings->where('status', BookingStatus::Failed)->count(),
        ];

        return view('bookings.index', [
            'bookings' => $bookings,
            'counts' => $counts,
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load([
            'items',
            'workflows',
            'activities' => fn ($query) => $query->oldest(),
        ]);

        return view('bookings.show', [
            'booking' => $booking,
        ]);
    }

    public function create(): View
    {
        return view('bookings.create');
    }
}
