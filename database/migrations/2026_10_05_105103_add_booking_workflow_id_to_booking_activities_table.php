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
        Schema::table('booking_activities', function (Blueprint $table) {
            $table->foreignId('booking_workflow_id')
                ->nullable()
                ->constrained('booking_workflows')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_workflow_id');
        });
    }
};
