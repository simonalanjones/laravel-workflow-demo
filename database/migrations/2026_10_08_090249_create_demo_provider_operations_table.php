<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demo_provider_operations', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('operation');
            $table->string('idempotency_key');
            $table->json('request_payload');
            $table->string('status')->default('processing');
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'idempotency_key']);
        });

        DB::table('provider_requests')->orderBy('id')->chunkById(100, function ($requests): void {
            DB::table('demo_provider_operations')->insert(
                $requests->map(fn ($request): array => (array) $request)->all()
            );
        });

        DB::table('provider_requests')->update([
            'status' => 'unknown',
            'response_payload' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_provider_operations');
    }
};
