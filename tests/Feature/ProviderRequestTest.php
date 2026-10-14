<?php

namespace Tests\Feature;

use App\Models\DemoProviderOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Lottery;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProviderRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Lottery::determineResultsNormally();

        parent::tearDown();
    }

    #[TestWith(['property', 'Property'])]
    #[TestWith(['flights', 'Flight'])]
    #[TestWith(['vehicles', 'Vehicle'])]
    public function test_repeated_requests_return_the_same_saved_confirmation(string $endpoint, string $provider): void
    {
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
    }

    #[TestWith(['property'])]
    #[TestWith(['flights'])]
    #[TestWith(['vehicles'])]
    public function test_retry_recovers_a_confirmation_saved_before_a_lost_response(string $endpoint): void
    {
        $payload = ['booking_reference' => 'DEMO-001', 'idempotency_key' => 'lost-001', 'simulate_lost_response' => true];

        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertServiceUnavailable();
        $saved = DemoProviderOperation::sole();
        $this->assertSame('succeeded', $saved->status);
        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)
            ->assertOk()->assertExactJson($saved->response_payload);

        $this->assertDatabaseCount('demo_provider_operations', 1);
    }

    public function test_transient_failure_is_saved_and_retry_updates_the_same_record(): void
    {
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
    }

    public function test_reusing_a_key_for_different_input_returns_409_without_overwriting(): void
    {
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
    }

    public function test_providers_have_independent_idempotency_keys(): void
    {
        $payload = ['booking_reference' => 'DEMO-001', 'idempotency_key' => 'shared-key'];

        foreach (['property', 'flights', 'vehicles'] as $endpoint) {
            $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertOk();
        }

        $this->assertDatabaseCount('demo_provider_operations', 3);
    }

    public function test_requests_without_explicit_keys_are_deduplicated_by_booking_reference(): void
    {
        $payload = ['booking_reference' => 'DEMO-001'];

        $first = $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk();
        $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk()->assertExactJson($first->json());

        $this->assertDatabaseCount('demo_provider_operations', 1);
    }

    public function test_invalid_requests_do_not_create_records(): void
    {
        $this->postJson('/api/demo/providers/property/confirm', [
            'idempotency_key' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['booking_reference', 'idempotency_key']);

        $this->assertDatabaseCount('demo_provider_operations', 0);
    }
}
