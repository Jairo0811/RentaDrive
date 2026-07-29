<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['vehicle_brand_id', 'name', 'year', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'year' => 'integer'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim(($this->brand?->name ?? '').' '.$this->name.' '.$this->year),
        );
    }
}
