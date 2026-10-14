<?php

namespace Database\Factories;

use App\Models\ProviderRequest;
use App\Models\ProviderRequestAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProviderRequestAttempt> */
class ProviderRequestAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider_request_id' => ProviderRequest::factory(),
            'started_at' => now(),
            'status' => 'processing',
        ];
    }
}
