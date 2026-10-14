<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'customer_name',
        'customer_email',
        'status',
        'simulate_network_delay',
        'simulate_lost_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'simulate_network_delay' => 'boolean',
            'simulate_lost_response' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function total(): float
    {
        return $this->items->sum(function (BookingItem $item) {
            return $item->quantity * $item->unit_price;
        });
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BookingActivity::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(BookingWorkflow::class);
    }
}
