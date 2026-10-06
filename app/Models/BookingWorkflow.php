<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingWorkflow extends Model
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
}
