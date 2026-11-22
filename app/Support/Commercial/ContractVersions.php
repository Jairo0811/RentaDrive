<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Rental;
use App\Models\RentalContractVersion;

final class ContractVersions
{
    /** Registra una versión solo si los términos cambiaron respecto de la última. */
    public static function record(Rental $rental, string $reason): ?RentalContractVersion
    {
        $snapshot = ContractIntegrity::snapshot($rental);
        $hash = ContractIntegrity::hash($snapshot);

        $last = RentalContractVersion::query()->where('rental_id', $rental->getKey())->orderByDesc('version')->first();

        if ($last !== null && hash_equals($last->document_hash, $hash)) {
            return null;
        }

        return RentalContractVersion::query()->create([
            'company_id' => $rental->company_id,
            'rental_id' => $rental->getKey(),
            'version' => ($last?->version ?? 0) + 1,
            'reason' => $reason,
            'document_hash' => $hash,
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Campos que cambiaron entre dos versiones consecutivas.
     *
     * @return array<string, array{antes: string|int|null, despues: string|int|null}>
     */
    public static function diff(?RentalContractVersion $previous, RentalContractVersion $current): array
    {
        if ($previous === null) {
            return [];
        }

        $changes = [];
        $before = $previous->terms();
        foreach ($current->terms() as $field => $value) {
            if (($before[$field] ?? null) !== $value) {
                $changes[$field] = ['antes' => $before[$field] ?? null, 'despues' => $value];
            }
        }

        return $changes;
    }
}
