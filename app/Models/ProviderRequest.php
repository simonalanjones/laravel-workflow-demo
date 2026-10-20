<?php

namespace App\Models;

use Database\Factories\ProviderRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderRequest extends Model
{
    /** @use HasFactory<ProviderRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_workflow_step_id',
        'provider',
        'operation',
        'idempotency_key',
        'request_payload',
        'status',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ProviderRequestAttempt::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(BookingWorkflowStep::class, 'booking_workflow_step_id');
    }
}
