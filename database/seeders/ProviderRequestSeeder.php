<?php

namespace Database\Seeders;

use App\Models\ProviderRequest;
use Illuminate\Database\Seeder;

class ProviderRequestSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Property', 'Flight', 'Vehicle'] as $provider) {
            ProviderRequest::factory()->succeeded()->create(['provider' => $provider]);
        }
    }
}
