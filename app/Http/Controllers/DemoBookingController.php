<?php

namespace App\Http\Controllers;

use App\Services\BookingCreator;

class DemoBookingController extends Controller
{
    public function successful(BookingCreator $bookingCreator)
    {
        $booking = $bookingCreator->create([
            'reference' => 'DEMO-'.now()->format('His'),
            'customer_name' => 'Successful Demo',
            'customer_email' => 'success@example.com',
            'items' => [
                [
                    'type' => 'accommodation',
                    'description' => 'Villa accommodation',
                    'quantity' => 1,
                    'unit_price' => 1200,
                ],
                [
                    'type' => 'flight',
                    'description' => 'Return flights',
                    'quantity' => 2,
                    'unit_price' => 350,
                ],
            ],
        ]);

        return redirect('/bookings/'.$booking->id);
    }

    public function failing(BookingCreator $bookingCreator)
    {
        $booking = $bookingCreator->create([
            'reference' => 'DEMO-FAIL-'.now()->format('His'),
            'customer_name' => 'Failure Demo',
            'customer_email' => 'fail@example.com',
            'items' => [
                [
                    'type' => 'accommodation',
                    'description' => 'Villa accommodation',
                    'quantity' => 1,
                    'unit_price' => 1200,
                ],
                [
                    'type' => 'flight',
                    'description' => 'Return flights',
                    'quantity' => 2,
                    'unit_price' => 350,
                ],
            ],
        ]);

        return redirect('/bookings/'.$booking->id);
    }

    public function randomFailure(BookingCreator $bookingCreator)
    {
        $booking = $bookingCreator->create([
            'reference' => 'DEMO-FAILR-'.now()->format('His'),
            'customer_name' => 'Random Failure Demo',
            'customer_email' => 'random@example.com',
            'items' => [
                [
                    'type' => 'accommodation',
                    'description' => 'Villa accommodation',
                    'quantity' => 1,
                    'unit_price' => 1200,
                ],
                [
                    'type' => 'flight',
                    'description' => 'Return flights',
                    'quantity' => 2,
                    'unit_price' => 350,
                ],
            ],
        ]);

        return redirect('/bookings/'.$booking->id);
    }
}
