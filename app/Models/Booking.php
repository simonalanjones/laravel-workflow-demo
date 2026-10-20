<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'reference',
        'customer_name',
        'customer_email',
        'status',
        'simulate_network_delay',
        'simulate_lost_response',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

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

    public function activities(): HasMany
    {
        return $this->hasMany(BookingActivity::class);
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(BookingWorkflowStep::class);
    }

    public function total(): float
    {
        return $this->items->sum(function (BookingItem $item) {
            return $item->quantity * $item->unit_price;
        });
    }

    public function currentStep(): string
    {
        $activeWorkflows = $this->workflowSteps
            ->whereIn('status', ['processing', 'queued', 'awaiting_approval', 'failed'])
            ->sortBy('id');

        if ($activeWorkflows->isNotEmpty()) {
            return $activeWorkflows->map(function (BookingWorkflowStep $workflowStep): string {
                if ($workflowStep->type === 'operations_signoff' && $workflowStep->status === 'awaiting_approval') {
                    return 'Waiting for sign-off';
                }

                return ucfirst(str_replace('_', ' ', $workflowStep->type)).' — '.str_replace('_', ' ', $workflowStep->status);
            })->implode('; ');
        }

        if ($this->status === BookingStatus::Processed) {
            return 'Complete';
        }

        if ($this->workflowSteps->isNotEmpty() && $this->workflowSteps->every(fn (BookingWorkflowStep $workflowStep): bool => $workflowStep->status === 'completed')) {
            return 'Finalising booking';
        }

        return 'Waiting for the next step';
    }
}
