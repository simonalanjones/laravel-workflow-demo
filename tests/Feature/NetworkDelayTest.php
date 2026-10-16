<?php

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Providers\FlightProviderClient;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Providers\VehicleProviderClient;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

test('demo saves delay setting and all clients pass it to the api', function (string $scenario): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    Http::preventStrayRequests();
    Http::fake([
        url('/api/demo/providers/property/confirm') => Http::response(['provider_reference' => 'PROPERTY-1']),
        url('/api/demo/providers/flights/confirm') => Http::response(['provider_reference' => 'FLIGHT-1']),
        url('/api/demo/providers/vehicles/confirm') => Http::response(['provider_reference' => 'VEHICLE-1']),
    ]);

    $this->post('/demo/bookings/'.$scenario, [
        'item_types' => ['accommodation', 'flight', 'car_hire'],
        'simulate_network_delay' => '1',
    ])->assertRedirect();

    $booking = Booking::sole();
    $this->assertTrue($booking->simulate_network_delay);
    foreach ([PropertyProviderClient::class, FlightProviderClient::class, VehicleProviderClient::class] as $client) {
        app($client)->confirm($booking);
    }
    foreach (['property', 'flights', 'vehicles'] as $endpoint) {
        Http::assertSent(fn (Request $request): bool => $request->url() === url('/api/demo/providers/'.$endpoint.'/confirm')
            && $request['simulate_network_delay'] === true
            && $request['booking_reference'] === $booking->reference);
    }
    Http::assertSentCount(3);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 3);
})->with([
    ['successful'],
    ['failing'],
    ['random-failure'],
]);

test('provider delays each attempt when enabled', function (string $endpoint): void {
    Sleep::fake();
    $payload = ['booking_reference' => 'DEMO-001', 'simulate_network_delay' => true];

    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertOk();
    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertOk();

    Sleep::assertSlept(fn (CarbonInterval $duration): bool => $duration->totalMilliseconds >= 1000 && $duration->totalMilliseconds <= 5000, times: 2);
    Sleep::assertSleptTimes(2);
})->with([
    ['property'],
    ['flights'],
    ['vehicles'],
]);

test('failed response is also delayed', function (): void {
    Sleep::fake();

    $this->postJson('/api/demo/providers/flights/confirm', [
        'booking_reference' => 'DEMO-FAIL-001',
        'simulate_network_delay' => true,
    ])->assertServiceUnavailable();

    Sleep::assertSleptTimes(1);
});

test('provider does not delay when disabled or omitted', function (string $endpoint): void {
    Sleep::fake();

    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', ['booking_reference' => 'DEMO-001'])->assertOk();
    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', [
        'booking_reference' => 'DEMO-002',
        'simulate_network_delay' => false,
    ])->assertOk();

    Sleep::assertNeverSlept();
})->with([
    ['property'],
    ['flights'],
    ['vehicles'],
]);

test('demo defaults to no delay', function (): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);

    $this->post('/demo/bookings/successful', ['item_types' => ['car_hire']])->assertRedirect();

    $this->assertFalse(Booking::sole()->simulate_network_delay);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
});

test('invalid delay setting is rejected', function (): void {
    Queue::fake();

    $this->postJson('/demo/bookings/successful', [
        'item_types' => ['car_hire'],
        'simulate_network_delay' => 'invalid',
    ])->assertUnprocessable()->assertJsonValidationErrors('simulate_network_delay');

    $this->assertDatabaseCount('bookings', 0);
    Queue::assertNothingPushed();
});

test('booking form offers network delay option', function (): void {
    $this->withoutVite();

    $this->get('/bookings/create')->assertSee('Simulate network delay')->assertSee('name="simulate_network_delay"', false);
});
