<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'code',
        'reservation_id',
        'customer_id',
        'vehicle_id',
        'start_at',
        'expected_return_at',
        'returned_at',
        'opening_mileage',
        'closing_mileage',
        'fuel_out',
        'fuel_in',
        'daily_rate',
        'deposit_amount',
        'subtotal',
        'fees',
        'taxes',
        'total',
        'status',
        'notes',
        'opened_by',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'expected_return_at' => 'datetime',
            'returned_at' => 'datetime',
            'fuel_out' => 'decimal:2',
            'fuel_in' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'fees' => 'decimal:2',
            'taxes' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
