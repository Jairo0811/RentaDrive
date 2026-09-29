<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'code',
        'customer_id',
        'vehicle_category_id',
        'vehicle_id',
        'start_at',
        'end_at',
        'pickup_location',
        'return_location',
        'daily_rate',
        'base_total',
        'extras_total',
        'discount_total',
        'promo_code',
        'pricing_breakdown',
        'estimated_total',
        'deposit_required',
        'deposit_paid',
        'status',
        'notes',
        'created_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'daily_rate' => 'decimal:2',
            'base_total' => 'decimal:2',
            'extras_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'estimated_total' => 'decimal:2',
            'deposit_required' => 'decimal:2',
            'deposit_paid' => 'decimal:2',
            'pricing_breakdown' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rental(): HasOne
    {
        return $this->hasOne(Rental::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class);
    }
}
