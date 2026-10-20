<?php

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('overview displays the current workflow step', function (string $bookingStatus, string $type, string $workflowStatus, string $label): void {
    $booking = createBookingForBookingOverview($bookingStatus);
    $booking->workflowSteps()->create(['type' => $type, 'status' => $workflowStatus]);

    Livewire::test('bookings.overview')
        ->assertSee('Current step')
        ->assertSee($label);
})->with([
    ['processing', 'operations_signoff', 'awaiting_approval', 'Waiting for sign-off'],
    ['processing', 'flight_confirmation', 'queued', 'Flight confirmation — queued'],
    ['processing', 'property_confirmation', 'processing', 'Property confirmation — processing'],
    ['failed', 'vehicle_confirmation', 'failed', 'Vehicle confirmation — failed'],
    ['processed', 'operations_signoff', 'completed', 'Complete'],
    ['processing', 'flight_confirmation', 'completed', 'Finalising booking'],
    ['pending', 'flight_confirmation', 'pending', 'Waiting for the next step'],
]);

test('overview shows parallel steps without pending signoff or completed steps', function (): void {
    $booking = createBookingForBookingOverview('processing');
    $booking->workflowSteps()->createMany([
        ['type' => 'property_confirmation', 'status' => 'processing'],
        ['type' => 'flight_confirmation', 'status' => 'queued'],
        ['type' => 'vehicle_confirmation', 'status' => 'completed'],
        ['type' => 'operations_signoff', 'status' => 'pending'],
    ]);

    Livewire::test('bookings.overview')
        ->assertSee('Property confirmation — processing; Flight confirmation — queued')
        ->assertDontSee('Vehicle confirmation')
        ->assertDontSee('Waiting for sign-off');
});

test('overview refreshes after signoff is completed', function (): void {
    $booking = createBookingForBookingOverview('processing');
    $workflowStep = $booking->workflowSteps()->create(['type' => 'operations_signoff', 'status' => 'awaiting_approval']);
    $component = Livewire::test('bookings.overview')->assertSee('Waiting for sign-off')
        ->assertSet('counts', ['pending' => 0, 'processing' => 1, 'processed' => 0, 'failed' => 0]);

    $workflowStep->update(['status' => 'completed']);
    $booking->update(['status' => 'processed']);

    $component->call('$refresh')->assertSee('Complete')->assertDontSee('Waiting for sign-off')
        ->assertSet('counts', ['pending' => 0, 'processing' => 0, 'processed' => 1, 'failed' => 0]);
});

test('booking without workflows shows that it is waiting', function (): void {
    createBookingForBookingOverview('pending');

    Livewire::test('bookings.overview')->assertSee('Waiting for the next step');
});

test('overview paginates bookings newest first with a stable order', function (): void {
    $this->freezeTime();

    foreach (range(1, 11) as $number) {
        createBookingForBookingOverview('pending', 'PAGINATION-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT));
    }

    Livewire::test('bookings.overview')
        ->assertSeeInOrder(['PAGINATION-11', 'PAGINATION-10', 'PAGINATION-02'])
        ->assertDontSee('PAGINATION-01')
        ->call('setPage', 2)
        ->assertSee('PAGINATION-01')
        ->assertDontSee('PAGINATION-02')
        ->assertDontSee('PAGINATION-11');
});

function createBookingForBookingOverview(string $status, string $reference = 'OVERVIEW-001'): Booking
{
    return Booking::create([
        'reference' => $reference,
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => $status,
    ]);
}
