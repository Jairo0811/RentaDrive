<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PaymentIntent extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'reservation_id',
        'invoice_id',
        'public_id',
        'purpose',
        'gateway',
        'gateway_reference',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'checkout_url',
        'metadata',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
