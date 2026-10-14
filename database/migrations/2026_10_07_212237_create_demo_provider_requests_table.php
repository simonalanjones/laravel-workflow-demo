<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_requests', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_requests');
    }
};
