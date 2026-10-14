<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_requests', function (Blueprint $table) {
            $table->foreignId('booking_workflow_id')->nullable()->unique()->constrained()->nullOnDelete();
        });

        DB::table('provider_requests')->orderBy('id')->chunkById(100, function ($requests): void {
            foreach ($requests as $request) {
                $type = match ($request->provider) {
                    'Property' => 'property_confirmation',
                    'Flight' => 'flight_confirmation',
                    'Vehicle' => 'vehicle_confirmation',
                    default => null,
                };
                $reference = json_decode($request->request_payload, true)['booking_reference'] ?? null;
                if ($type === null || ! is_string($reference) || $request->operation !== 'confirm_booking'
                    || $request->idempotency_key !== hash('sha256', 'confirm_booking:'.$reference)) {
                    continue;
                }

                $workflows = DB::table('booking_workflows')
                    ->join('bookings', 'bookings.id', '=', 'booking_workflows.booking_id')
                    ->where('bookings.reference', $reference)
                    ->where('booking_workflows.type', $type)
                    ->pluck('booking_workflows.id');

                if ($workflows->count() === 1) {
                    DB::table('provider_requests')->where('id', $request->id)
                        ->update(['booking_workflow_id' => $workflows->first()]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('provider_requests', function (Blueprint $table) {
            $table->dropForeign(['booking_workflow_id']);
            $table->dropUnique(['booking_workflow_id']);
            $table->dropColumn('booking_workflow_id');
        });
    }
};
