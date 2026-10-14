<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingActivity extends Model
{
    protected $fillable = [
        'booking_id',
        'booking_workflow_id',
        'type',
        'message',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(
            BookingWorkflow::class,
            'booking_workflow_id'
        );
    }

    public function providerAttempt(): HasOne
    {
        return $this->hasOne(ProviderRequestAttempt::class);
    }
}
