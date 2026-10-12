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
        'company_id',
        'branch_id',
        'code',
        'customer_id',
        'vehicle_category_id',
        'vehicle_id',
        'start_at',
        'end_at',
        'pickup_location',
        'return_location',
        'daily_rate',
        'subtotal',
        'extras_total',
        'deposit_amount',
        'estimated_total',
        'status',
        'source',
        'public_token',
        'notes',
        'created_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'branch_id' => 'integer',
            'customer_id' => 'integer',
            'vehicle_category_id' => 'integer',
            'vehicle_id' => 'integer',
            'created_by' => 'integer',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'daily_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'extras_total' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'estimated_total' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function bookingOptions(): HasMany
    {
        return $this->hasMany(ReservationBookingOption::class);
    }

    public function rental(): HasOne
    {
        return $this->hasOne(Rental::class);
    }
}
