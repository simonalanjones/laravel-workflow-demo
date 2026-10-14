<?php

namespace App\Models;

use Database\Factories\ProviderRequestAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderRequestAttempt extends Model
{
    /** @use HasFactory<ProviderRequestAttemptFactory> */
    use HasFactory;

    protected $fillable = ['provider_request_id', 'booking_activity_id', 'started_at', 'finished_at', 'duration_ms', 'status', 'http_status', 'response_body', 'error_message'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime', 'duration_ms' => 'integer', 'http_status' => 'integer'];
    }

    public function providerRequest(): BelongsTo
    {
        return $this->belongsTo(ProviderRequest::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(BookingActivity::class, 'booking_activity_id');
    }
}
