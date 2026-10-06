<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Services\BookingCreator;

class BookingController extends Controller
{
    public function store(
        StoreBookingRequest $request,
        BookingCreator $bookingCreator
    ) {
        $booking = $bookingCreator->create(
            $request->validated()
        );

        return response()->json(
            $booking->load(['items', 'workflows']),
            201
        );
    }

    public function statuses()
    {
        return Booking::latest()
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'status' => $booking->status->value,
                ];
            });
    }
}
