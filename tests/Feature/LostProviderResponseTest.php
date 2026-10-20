<?php

use App\Jobs\ProcessBookingWorkflowStepJob;
use App\Models\Booking;
use App\Services\Providers\FlightProviderClient;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Providers\VehicleProviderClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('demo saves lost response setting and all clients pass it to the api', function (string $scenario): void {
    Queue::fake([ProcessBookingWorkflowStepJob::class]);
    Http::preventStrayRequests();
    Http::fake([
        url('/api/demo/providers/property/confirm') => Http::response(['provider_reference' => 'PROPERTY-1']),
        url('/api/demo/providers/flights/confirm') => Http::response(['provider_reference' => 'FLIGHT-1']),
        url('/api/demo/providers/vehicles/confirm') => Http::response(['provider_reference' => 'VEHICLE-1']),
    ]);

    $this->post('/demo/bookings/'.$scenario, [
        'item_types' => ['accommodation', 'flight', 'car_hire'],
        'simulate_lost_response' => '1',
    ])->assertRedirect();

    $booking = Booking::sole();
    $this->assertTrue($booking->simulate_lost_response);
    foreach ([PropertyProviderClient::class, FlightProviderClient::class, VehicleProviderClient::class] as $client) {
        app($client)->confirm($booking);
    }
    foreach (['property', 'flights', 'vehicles'] as $endpoint) {
        Http::assertSent(fn (Request $request): bool => $request->url() === url('/api/demo/providers/'.$endpoint.'/confirm')
            && $request['simulate_lost_response'] === true
            && $request['booking_reference'] === $booking->reference);
    }
    Http::assertSentCount(3);
    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, 3);
})->with([
    ['successful'],
    ['failing'],
    ['random-failure'],
]);

test('demo defaults to no lost response', function (): void {
    Queue::fake([ProcessBookingWorkflowStepJob::class]);

    $this->post('/demo/bookings/successful', ['item_types' => ['car_hire']])->assertRedirect();

    $this->assertFalse(Booking::sole()->simulate_lost_response);
    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, 1);
});

test('invalid lost response setting is rejected', function (): void {
    Queue::fake();

    $this->postJson('/demo/bookings/successful', [
        'item_types' => ['car_hire'],
        'simulate_lost_response' => 'invalid',
    ])->assertUnprocessable()->assertJsonValidationErrors('simulate_lost_response');

    $this->assertDatabaseCount('bookings', 0);
    Queue::assertNothingPushed();
});

test('booking form offers lost response option', function (): void {
    $this->withoutVite();

    $this->get('/bookings/create')->assertSee('Simulate lost provider response')->assertSee('name="simulate_lost_response"', false);
});
