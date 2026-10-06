<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessBookingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(public Booking $booking)
    {
        //
    }

    public function handle(): void
    {
        Log::info('Processing booking', [
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
        ]);

        $this->booking->activities()->create([
            'type' => 'processing_started',
            'message' => 'Processing attempt '.$this->attempts(),
        ]);

        $this->booking->activities()->create([
            'type' => 'booking_processing_complete',
            'message' => 'Initial booking processing completed',
        ]);

        Log::info('Booking processed successfully', [
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        $this->booking->activities()->create([
            'type' => 'booking_processing_failed',
            'message' => $exception?->getMessage() ?? 'Booking processing failed',
        ]);

        Log::error('Booking processing failed', [
            'booking_id' => $this->booking->id,
            'reference' => $this->booking->reference,
            'error' => $exception?->getMessage(),
        ]);
    }
}
