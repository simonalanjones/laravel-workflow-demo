<?php

use App\Exceptions\ProviderResponseLostException;
use App\Http\Controllers\DemoProviderController;
use App\Jobs\ProcessBookingWorkflowStepJob;
use App\Models\Booking;
use App\Models\DemoProviderOperation;
use App\Models\ProviderRequest;
use App\Services\Providers\FlightProviderClient;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Providers\VehicleProviderClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as IncomingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('client saves key before sending and recovers only through provider response', function (string $item, string $endpoint, string $method, string $clientClass): void {
    $booking = createBookingForClientProviderRequest($item);
    Http::preventStrayRequests();
    $keys = [];
    Http::fake([url('/api/demo/providers/'.$endpoint.'/confirm') => function (Request $outgoing) use ($method, &$keys) {
        $record = ProviderRequest::sole();
        $this->assertSame('processing', $record->status);
        $this->assertSame($record->idempotency_key, $outgoing['idempotency_key']);
        $keys[] = $outgoing['idempotency_key'];
        $response = app(DemoProviderController::class)->$method(
            IncomingRequest::create('/demo', 'POST', $outgoing->data())
        );

        return Http::response($response->getData(true), $response->getStatusCode());
    }]);

    try {
        app($clientClass)->confirm($booking);
        $this->fail('The first response should be lost.');
    } catch (ProviderResponseLostException) {
        $this->assertSame('unknown', ProviderRequest::sole()->status);
        $this->assertNull(ProviderRequest::sole()->response_payload);
        $this->assertSame('succeeded', DemoProviderOperation::sole()->status);
    }
    $saved = DemoProviderOperation::sole()->response_payload;

    $result = app($clientClass)->confirm($booking->fresh());

    $this->assertSame($saved, $result);
    $this->assertSame($keys[0], $keys[1]);
    $this->assertSame('succeeded', ProviderRequest::sole()->status);
    $this->assertSame($saved, ProviderRequest::sole()->response_payload);
    $this->assertDatabaseCount('provider_requests', 1);
    $this->assertDatabaseCount('demo_provider_operations', 1);
    Http::assertSentCount(2);
})->with([
    ['accommodation', 'property', 'confirmProperty', PropertyProviderClient::class],
    ['flight', 'flights', 'confirmFlight', FlightProviderClient::class],
    ['car_hire', 'vehicles', 'confirmVehicle', VehicleProviderClient::class],
]);

test('connection failure keeps key without inventing a provider response', function (): void {
    $booking = createBookingForClientProviderRequest('flight');
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/flights/confirm') => Http::failedConnection()]);

    try {
        app(FlightProviderClient::class)->confirm($booking);
        $this->fail('The connection should fail.');
    } catch (ConnectionException) {
        $this->assertSame('unknown', ProviderRequest::sole()->status);
    }

    $record = ProviderRequest::sole();
    $this->assertNotEmpty($record->idempotency_key);
    $this->assertNull($record->response_payload);
    $this->assertDatabaseCount('demo_provider_operations', 0);
    Http::assertSentCount(1);
});

test('received failure is saved only on client side', function (): void {
    $booking = createBookingForClientProviderRequest('flight');
    Http::preventStrayRequests();
    $payload = ['status' => 'unavailable', 'message' => 'Try later'];
    Http::fake([url('/api/demo/providers/flights/confirm') => Http::response($payload, 503)]);

    try {
        app(FlightProviderClient::class)->confirm($booking);
        $this->fail('The provider error should fail the attempt.');
    } catch (RuntimeException $exception) {
        $this->assertSame('Provider returned HTTP 503', $exception->getMessage());
    }

    $this->assertSame('failed', ProviderRequest::sole()->status);
    $this->assertSame($payload, ProviderRequest::sole()->response_payload);
    $this->assertDatabaseCount('demo_provider_operations', 0);
    Http::assertSentCount(1);
});

function createBookingForClientProviderRequest(string $item): Booking
{
    Queue::fake([ProcessBookingWorkflowStepJob::class]);
    test()->post('/demo/bookings/successful', [
        'item_types' => [$item],
        'requires_signoff' => false,
        'simulate_lost_response' => true,
    ])->assertRedirect();
    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, 1);

    return Booking::sole();
}
