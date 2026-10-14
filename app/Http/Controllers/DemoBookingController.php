<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDemoBookingRequest;
use App\Services\BookingCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DemoBookingController extends Controller
{
    public function successful(StoreDemoBookingRequest $request, BookingCreator $bookingCreator): RedirectResponse
    {
        return $this->create($request, $bookingCreator, 'DEMO-', 'Successful Demo', 'success@example.com');
    }

    public function failing(StoreDemoBookingRequest $request, BookingCreator $bookingCreator): RedirectResponse
    {
        return $this->create($request, $bookingCreator, 'DEMO-FAIL-', 'Failure Demo', 'fail@example.com');
    }

    public function randomFailure(StoreDemoBookingRequest $request, BookingCreator $bookingCreator): RedirectResponse
    {
        return $this->create($request, $bookingCreator, 'DEMO-FAILR-', 'Random Failure Demo', 'random@example.com');
    }

    private function create(
        StoreDemoBookingRequest $request,
        BookingCreator $bookingCreator,
        string $referencePrefix,
        string $customerName,
        string $customerEmail,
    ): RedirectResponse {
        $items = [
            ['type' => 'accommodation', 'description' => 'Villa accommodation', 'quantity' => 1, 'unit_price' => 1200],
            ['type' => 'flight', 'description' => 'Return flights', 'quantity' => 2, 'unit_price' => 350],
            ['type' => 'car_hire', 'description' => 'Car hire', 'quantity' => 1, 'unit_price' => 250],
        ];

        $booking = $bookingCreator->create([
            'reference' => $referencePrefix.Str::uuid(),
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'requires_signoff' => (bool) $request->validated('requires_signoff', true),
            'simulate_network_delay' => (bool) $request->validated('simulate_network_delay', false),
            'simulate_lost_response' => (bool) $request->validated('simulate_lost_response', false),
            'items' => array_values(array_filter(
                $items,
                fn (array $item): bool => in_array($item['type'], $request->validated('item_types'), true),
            )),
        ]);

        return redirect('/bookings/'.$booking->id);
    }
}
