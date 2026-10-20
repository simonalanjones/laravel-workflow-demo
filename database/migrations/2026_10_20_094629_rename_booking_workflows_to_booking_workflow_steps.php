<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const ACTIVITY_SUFFIXES = [
        'attempt_started', 'attempt_failed', 'completed', 'failed',
        'retry_requested', 'awaiting_approval', 'approved', 'processing',
    ];

    public function up(): void
    {
        Schema::rename('booking_workflows', 'booking_workflow_steps');

        foreach (['booking_activities', 'provider_requests'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('booking_workflow_id', 'booking_workflow_step_id');
            });
        }

        foreach (self::ACTIVITY_SUFFIXES as $suffix) {
            DB::table('booking_activities')->where('type', 'workflow_item_'.$suffix)
                ->update(['type' => 'workflow_step_'.$suffix]);
        }
    }

    public function down(): void
    {
        foreach (self::ACTIVITY_SUFFIXES as $suffix) {
            DB::table('booking_activities')->where('type', 'workflow_step_'.$suffix)
                ->update(['type' => 'workflow_item_'.$suffix]);
        }

        foreach (['booking_activities', 'provider_requests'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('booking_workflow_step_id', 'booking_workflow_id');
            });
        }

        Schema::rename('booking_workflow_steps', 'booking_workflows');
    }
};
