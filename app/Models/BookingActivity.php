<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingActivity extends Model
{
    protected $fillable = [
        'booking_id',
        'booking_workflow_step_id',
        'type',
        'message',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(
            BookingWorkflowStep::class,
            'booking_workflow_step_id'
        );
    }

    public function providerAttempt(): HasOne
    {
        return $this->hasOne(ProviderRequestAttempt::class);
    }
}
