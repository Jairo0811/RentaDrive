<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'number',
        'rental_id',
        'customer_id',
        'issued_at',
        'due_at',
        'subtotal',
        'tax',
        'discount',
        'total',
        'paid_amount',
        'balance',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_at' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function fiscalDraft(): HasOne
    {
        return $this->hasOne(FiscalDraft::class);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Visible solo si el alquiler asociado es de la sucursal del usuario (ver BranchVisible). */
    public function scopeVisibleToBranch(Builder $query, ?User $user = null): void
    {
        $user ??= auth()->user();

        if ($user?->branchRestriction() !== null) {
            $query->whereHas('rental', fn (Builder $rental) => $rental->visibleToBranch($user));
        }
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()->visibleToBranch()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
