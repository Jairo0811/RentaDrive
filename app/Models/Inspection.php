<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'rental_id',
        'vehicle_id',
        'type',
        'inspected_at',
        'mileage',
        'fuel_level',
        'body_condition',
        'interior_condition',
        'tires_condition',
        'accessories',
        'damages',
        'photos',
        'photo_disk',
        'inspected_by',
    ];

    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
            'fuel_level' => 'decimal:2',
            'photos' => 'array',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
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
