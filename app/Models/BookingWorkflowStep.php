<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingWorkflowStep extends Model
{
    protected $fillable = [
        'type',
        'status',
        'message',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BookingActivity::class);
    }

    public function providerRequest(): HasOne
    {
        return $this->hasOne(ProviderRequest::class);
    }
}
