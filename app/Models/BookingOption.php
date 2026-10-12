<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BookingOption extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'pricing_mode',
        'price',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reservationOptions(): HasMany
    {
        return $this->hasMany(ReservationBookingOption::class);
    }
}
