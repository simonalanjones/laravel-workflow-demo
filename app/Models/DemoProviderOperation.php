<?php

namespace App\Models;

use Database\Factories\DemoProviderOperationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoProviderOperation extends Model
{
    /** @use HasFactory<DemoProviderOperationFactory> */
    use HasFactory;

    protected $fillable = [
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
}
