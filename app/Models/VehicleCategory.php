<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleCategory extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['code', 'name', 'daily_rate', 'deposit_amount', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
