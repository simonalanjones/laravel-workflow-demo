<?php

namespace Database\Factories;

use App\Models\ProviderRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProviderRequest> */
class ProviderRequestFactory extends Factory
{
    /** @return array{provider: string, operation: string, idempotency_key: string, request_payload: array{booking_reference: string}, status: string, response_payload: null} */
    public function definition(): array
    {
        return [
            'provider' => 'Property',
            'operation' => 'confirm_booking',
            'idempotency_key' => fake()->uuid(),
            'request_payload' => ['booking_reference' => 'DEMO-'.fake()->uuid()],
            'status' => 'processing',
            'response_payload' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'succeeded',
            'response_payload' => [
                'status' => 'confirmed',
                'provider_reference' => strtoupper($attributes['provider']).'-'.fake()->uuid(),
            ],
        ]);
    }
}
