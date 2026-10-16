<?php

use App\Models\DemoProviderOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Lottery;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Lottery::determineResultsNormally();
});

test('repeated requests return the same saved confirmation', function (string $endpoint, string $provider): void {
    $payload = ['booking_reference' => 'DEMO-001', 'idempotency_key' => 'request-001'];

    $first = $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertOk();
    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)
        ->assertOk()->assertExactJson($first->json());

    $this->assertDatabaseCount('demo_provider_operations', 1);
    $record = DemoProviderOperation::sole();
    $this->assertSame($provider, $record->provider);
    $this->assertSame('confirm_booking', $record->operation);
    $this->assertSame('request-001', $record->idempotency_key);
    $this->assertSame(['booking_reference' => 'DEMO-001'], $record->request_payload);
    $this->assertSame('succeeded', $record->status);
    $this->assertSame($first->json(), $record->response_payload);
})->with([
    ['property', 'Property'],
    ['flights', 'Flight'],
    ['vehicles', 'Vehicle'],
]);

test('retry recovers a confirmation saved before a lost response', function (string $endpoint): void {
    $payload = ['booking_reference' => 'DEMO-001', 'idempotency_key' => 'lost-001', 'simulate_lost_response' => true];

    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertServiceUnavailable();
    $saved = DemoProviderOperation::sole();
    $this->assertSame('succeeded', $saved->status);
    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)
        ->assertOk()->assertExactJson($saved->response_payload);

    $this->assertDatabaseCount('demo_provider_operations', 1);
})->with([
    ['property'],
    ['flights'],
    ['vehicles'],
]);

test('transient failure is saved and retry updates the same record', function (): void {
    Lottery::fix([true, false, true]);
    $payload = ['booking_reference' => 'DEMO-FAILR-001', 'idempotency_key' => 'retry-001'];

    $failed = $this->postJson('/api/demo/providers/property/confirm', $payload)->assertServiceUnavailable();
    $record = DemoProviderOperation::sole();
    $this->assertSame('failed', $record->status);
    $this->assertSame($failed->json(), $record->response_payload);

    $success = $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk();
    $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk()->assertExactJson($success->json());

    $this->assertDatabaseCount('demo_provider_operations', 1);
    $this->assertSame('succeeded', $record->fresh()->status);
    $this->assertSame($success->json(), $record->fresh()->response_payload);
});

test('reusing a key for different input returns 409 without overwriting', function (): void {
    $record = DemoProviderOperation::factory()->succeeded()->create([
        'idempotency_key' => 'existing-key',
        'request_payload' => ['booking_reference' => 'ORIGINAL'],
    ]);

    $this->postJson('/api/demo/providers/property/confirm', [
        'booking_reference' => 'DIFFERENT',
        'idempotency_key' => 'existing-key',
    ])->assertConflict()->assertJsonPath('message', 'Idempotency key has already been used for a different request.');

    $this->assertSame($record->request_payload, $record->fresh()->request_payload);
    $this->assertSame($record->response_payload, $record->fresh()->response_payload);
    $this->assertDatabaseCount('demo_provider_operations', 1);
});

test('providers have independent idempotency keys', function (): void {
    $payload = ['booking_reference' => 'DEMO-001', 'idempotency_key' => 'shared-key'];

    foreach (['property', 'flights', 'vehicles'] as $endpoint) {
        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertOk();
    }

    $this->assertDatabaseCount('demo_provider_operations', 3);
});

test('requests without explicit keys are deduplicated by booking reference', function (): void {
    $payload = ['booking_reference' => 'DEMO-001'];

    $first = $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk();
    $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk()->assertExactJson($first->json());

    $this->assertDatabaseCount('demo_provider_operations', 1);
});

test('invalid requests do not create records', function (): void {
    $this->postJson('/api/demo/providers/property/confirm', [
        'idempotency_key' => [],
    ])->assertUnprocessable()->assertJsonValidationErrors(['booking_reference', 'idempotency_key']);

    $this->assertDatabaseCount('demo_provider_operations', 0);
});
