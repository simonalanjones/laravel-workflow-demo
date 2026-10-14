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
        Schema::create('provider_request_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_activity_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamp('started_at', 6);
            $table->timestamp('finished_at', 6)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status')->default('processing');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_request_attempts');
    }
};
