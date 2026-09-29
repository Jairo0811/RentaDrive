<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'receipt_number',
        'invoice_id',
        'reservation_id',
        'paid_at',
        'method',
        'reference',
        'amount',
        'gateway',
        'gateway_transaction_id',
        'currency',
        'status',
        'refunded_amount',
        'idempotency_key',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function netAmount(): float
    {
        return max(0, (float) $this->amount - (float) $this->refunded_amount);
    }
}
