<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReservationBookingOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'reservation_id',
        'booking_option_id',
        'quantity',
        'unit_price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'reservation_id' => 'integer',
            'booking_option_id' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(BookingOption::class, 'booking_option_id');
    }
}
