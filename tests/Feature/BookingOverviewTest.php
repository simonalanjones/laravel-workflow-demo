<?php

namespace Tests\Feature;

use App\Livewire\Bookings\Overview;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BookingOverviewTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['processing', 'operations_signoff', 'awaiting_approval', 'Waiting for sign-off'])]
    #[TestWith(['processing', 'flight_confirmation', 'queued', 'Flight confirmation — queued'])]
    #[TestWith(['processing', 'property_confirmation', 'processing', 'Property confirmation — processing'])]
    #[TestWith(['failed', 'vehicle_confirmation', 'failed', 'Vehicle confirmation — failed'])]
    #[TestWith(['processed', 'operations_signoff', 'completed', 'Complete'])]
    #[TestWith(['processing', 'flight_confirmation', 'completed', 'Finalising booking'])]
    #[TestWith(['pending', 'flight_confirmation', 'pending', 'Waiting for the next step'])]
    public function test_overview_displays_the_current_workflow_step(string $bookingStatus, string $type, string $workflowStatus, string $label): void
    {
        $booking = $this->createBooking($bookingStatus);
        $booking->workflows()->create(['type' => $type, 'status' => $workflowStatus]);

        Livewire::test(Overview::class)
            ->assertSee('Current step')
            ->assertSee($label);
    }

    public function test_overview_shows_parallel_steps_without_pending_signoff_or_completed_steps(): void
    {
        $booking = $this->createBooking('processing');
        $booking->workflows()->createMany([
            ['type' => 'property_confirmation', 'status' => 'processing'],
            ['type' => 'flight_confirmation', 'status' => 'queued'],
            ['type' => 'vehicle_confirmation', 'status' => 'completed'],
            ['type' => 'operations_signoff', 'status' => 'pending'],
        ]);

        Livewire::test(Overview::class)
            ->assertSee('Property confirmation — processing; Flight confirmation — queued')
            ->assertDontSee('Vehicle confirmation')
            ->assertDontSee('Waiting for sign-off');
    }

    public function test_overview_refreshes_after_signoff_is_completed(): void
    {
        $booking = $this->createBooking('processing');
        $workflow = $booking->workflows()->create(['type' => 'operations_signoff', 'status' => 'awaiting_approval']);
        $component = Livewire::test(Overview::class)->assertSee('Waiting for sign-off');

        $workflow->update(['status' => 'completed']);
        $booking->update(['status' => 'processed']);

        $component->call('$refresh')->assertSee('Complete')->assertDontSee('Waiting for sign-off');
    }

    public function test_booking_without_workflows_shows_that_it_is_waiting(): void
    {
        $this->createBooking('pending');

        Livewire::test(Overview::class)->assertSee('Waiting for the next step');
    }

    private function createBooking(string $status): Booking
    {
        return Booking::create([
            'reference' => 'OVERVIEW-001',
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'status' => $status,
        ]);
    }
}
