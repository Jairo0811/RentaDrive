<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Historial append-only de los términos del contrato: no se edita ni se borra. */
final class RentalContractVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['company_id', 'rental_id', 'version', 'reason', 'document_hash', 'snapshot', 'created_by'];

    protected static function booted(): void
    {
        self::updating(static fn () => throw new LogicException('Las versiones del contrato son inmutables.'));
        self::deleting(static fn () => throw new LogicException('Las versiones del contrato no se pueden borrar.'));
    }

    protected function casts(): array
    {
        return ['company_id' => 'integer', 'version' => 'integer', 'created_at' => 'datetime'];
    }

    /** @return array<string, string|int|null> */
    public function terms(): array
    {
        return json_decode($this->snapshot, true, 512, JSON_THROW_ON_ERROR);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
