<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('bookings')
            ->select('id')
            ->whereNull('public_id')
            ->chunkById(100, function (Collection $bookings): void {
                foreach ($bookings as $booking) {
                    DB::table('bookings')
                        ->where('id', $booking->id)
                        ->update(['public_id' => (string) Str::uuid()]);
                }
            });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->change();
        });
    }
};
