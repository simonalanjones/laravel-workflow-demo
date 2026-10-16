<?php

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Models\ProviderRequest;
use App\Services\Providers\FlightProviderClient;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Providers\VehicleProviderClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('request is linked before http and retries keep the same relationship', function (string $item, string $endpoint, string $client): void {
    $booking = createBookingForProviderRequestWorkflow($item);
    $workflow = $booking->workflows()->sole();
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/'.$endpoint.'/confirm') => function () use ($workflow) {
        $this->assertSame($workflow->id, ProviderRequest::sole()->booking_workflow_id);

        return Http::response(['provider_reference' => 'CONFIRMED-1']);
    }]);

    app($client)->confirm($booking);
    $key = ProviderRequest::sole()->idempotency_key;
    app($client)->confirm($booking->fresh());

    $request = ProviderRequest::sole();
    $this->assertTrue($request->workflow->is($workflow));
    $this->assertTrue($workflow->providerRequest->is($request));
    $this->assertSame($key, $request->idempotency_key);
    $this->assertSame(2, $request->attempts()->count());
    Http::assertSentCount(2);
})->with([
    ['accommodation', 'property', PropertyProviderClient::class],
    ['flight', 'flights', FlightProviderClient::class],
    ['car_hire', 'vehicles', VehicleProviderClient::class],
]);

test('migration backfills matching requests and preserves unmatched records', function (): void {
    $booking = createBookingForProviderRequestWorkflow('flight');
    $workflow = $booking->workflows()->sole();
    $migration = require database_path('migrations/2026_10_08_103919_add_booking_workflow_id_to_provider_requests_table.php');
    $migration->down();
    $request = ProviderRequest::factory()->succeeded()->create([
        'provider' => 'Flight',
        'idempotency_key' => hash('sha256', 'confirm_booking:'.$booking->reference),
        'request_payload' => ['booking_reference' => $booking->reference],
    ]);
    $unmatched = ProviderRequest::factory()->create();

    $migration->up();

    $this->assertSame($workflow->id, $request->fresh()->booking_workflow_id);
    $this->assertSame($request->response_payload, $request->fresh()->response_payload);
    $this->assertSame($request->idempotency_key, $request->fresh()->idempotency_key);
    $this->assertNull($unmatched->fresh()->booking_workflow_id);
    $this->assertDatabaseCount('provider_requests', 2);
});

test('each provider gets a different uuid and existing keys are preserved', function (): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/*/confirm') => Http::response(['provider_reference' => 'CONFIRMED'])]);
    $this->post('/demo/bookings/successful', [
        'item_types' => ['accommodation', 'flight', 'car_hire'],
        'requires_signoff' => false,
    ])->assertRedirect();
    $booking = Booking::sole();

    foreach ([PropertyProviderClient::class, FlightProviderClient::class, VehicleProviderClient::class] as $client) {
        app($client)->confirm($booking);
    }

    $keys = ProviderRequest::pluck('idempotency_key');
    $this->assertCount(3, $keys->unique());
    foreach ($keys as $key) {
        $this->assertTrue(Str::isUuid($key));
    }

    $flight = $booking->workflows()->where('type', 'flight_confirmation')->sole()->providerRequest;
    $legacyKey = hash('sha256', 'confirm_booking:'.$booking->reference);
    $flight->update(['idempotency_key' => $legacyKey]);
    app(FlightProviderClient::class)->confirm($booking);

    $this->assertSame($legacyKey, $flight->fresh()->idempotency_key);
    $this->assertDatabaseCount('provider_requests', 3);
    Http::assertSent(fn ($request): bool => $request['idempotency_key'] === $legacyKey);
    Http::assertSentCount(4);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 3);
});

function createBookingForProviderRequestWorkflow(string $item): Booking
{
    Queue::fake([ProcessBookingWorkflowJob::class]);
    test()->post('/demo/bookings/successful', ['item_types' => [$item], 'requires_signoff' => false])->assertRedirect();
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);

    return Booking::sole();
}
