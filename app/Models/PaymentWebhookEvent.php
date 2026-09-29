<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider',
        'event_id',
        'event_type',
        'payload_hash',
        'payload',
        'status',
        'processed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
