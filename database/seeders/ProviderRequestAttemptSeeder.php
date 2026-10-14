<?php

namespace Database\Seeders;

use App\Models\ProviderRequestAttempt;
use Illuminate\Database\Seeder;

class ProviderRequestAttemptSeeder extends Seeder
{
    public function run(): void
    {
        ProviderRequestAttempt::factory()->create([
            'status' => 'unknown',
            'finished_at' => now(),
            'duration_ms' => 15000,
            'error_message' => 'Connection timed out before a response was received.',
        ]);
    }
}
