<?php

namespace Database\Seeders;

use App\Models\DemoProviderOperation;
use Illuminate\Database\Seeder;

class DemoProviderOperationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Property', 'Flight', 'Vehicle'] as $provider) {
            DemoProviderOperation::factory()->succeeded()->create(['provider' => $provider]);
        }
    }
}
