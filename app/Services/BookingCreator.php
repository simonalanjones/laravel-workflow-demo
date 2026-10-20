<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Services\Workflows\BookingWorkflowStepFactory;
use Illuminate\Support\Facades\DB;

class BookingCreator
{
    public function __construct(
        private BookingWorkflowStepFactory $workflowFactory
    ) {}

    /**
     * Save the booking, items, initial activity and workflow steps together.
     * If any write fails, the transaction rolls them all back so we do not
     * leave a partially created booking.
     */
    public function create(array $data): Booking
    {
        $booking = DB::transaction(function () use ($data) {
            $booking = Booking::create([
                'reference' => $data['reference'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'status' => BookingStatus::Pending,
                'simulate_network_delay' => $data['simulate_network_delay'] ?? false,
                'simulate_lost_response' => $data['simulate_lost_response'] ?? false,
            ]);

            foreach ($data['items'] as $item) {
                $booking->items()->create([
                    'type' => $item['type'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            $booking->activities()->create([
                'type' => 'booking_created',
                'message' => 'Booking created',
            ]);

            $booking->load('items');

            $this->workflowFactory->createFor(
                $booking,
                (bool) ($data['requires_signoff'] ?? true)
            );

            return $booking;
        });

        /**
         * Notify listeners after the creation transaction completes so they
         * can queue work for the saved steps. A crash before event delivery
         * can leave steps pending; the scheduled bookings:recover command
         * checks those steps again when the scheduler is running.
         */
        event(new BookingCreated($booking));

        return $booking;
    }
}
