<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'vehicle_model_id',
        'vehicle_category_id',
        'code',
        'plate',
        'vin',
        'color',
        'transmission',
        'fuel_type',
        'seats',
        'mileage',
        'daily_rate_override',
        'status',
        'acquisition_date',
        'next_maintenance_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'daily_rate_override' => 'decimal:2',
            'mileage' => 'integer',
            'next_maintenance_at' => 'integer',
        ];
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim(($this->model?->display_name ?? 'Vehículo').' · '.$this->plate),
        );
    }

    protected function effectiveDailyRate(): Attribute
    {
        return Attribute::get(
            fn (): float => (float) ($this->daily_rate_override ?? $this->category?->daily_rate ?? 0),
        );
    }
}
