<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private const TYPES = [
        'workflow_attempt' => 'workflow_item_attempt_started',
        'workflow_attempt_failed' => 'workflow_item_attempt_failed',
        'workflow_completed' => 'workflow_item_completed',
        'workflow_failed' => 'workflow_item_failed',
        'workflow_retried' => 'workflow_item_retry_requested',
        'workflow_awaiting_approval' => 'workflow_item_awaiting_approval',
        'workflow_approved' => 'workflow_item_approved',
        'workflow_processing' => 'workflow_item_processing',
    ];

    public function up(): void
    {
        foreach (self::TYPES as $old => $new) {
            DB::table('booking_activities')->where('type', $old)->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::TYPES as $old => $new) {
            DB::table('booking_activities')->where('type', $new)->update(['type' => $old]);
        }
    }
};
