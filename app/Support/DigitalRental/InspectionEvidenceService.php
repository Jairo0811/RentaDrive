<?php

declare(strict_types=1);

namespace App\Support\DigitalRental;

use App\Models\Inspection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InspectionEvidenceService
{
    public function seal(Inspection $inspection): Inspection
    {
        if ($inspection->isSealed()) {
            return $inspection;
        }

        return DB::transaction(function () use ($inspection): Inspection {
            /** @var Inspection $locked */
            $locked = Inspection::query()->lockForUpdate()->findOrFail($inspection->getKey());

            if ($locked->isSealed()) {
                return $locked;
            }

            $snapshot = Arr::only($locked->getAttributes(), [
                'company_id',
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
                'accessories_checklist',
                'damages',
                'damage_items',
                'photos',
                'latitude',
                'longitude',
                'inspected_by',
            ]);

            ksort($snapshot);

            $locked->update([
                'evidence_hash' => hash(
                    'sha256',
                    json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ),
                'sealed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    public function assertCanDelete(Inspection $inspection): void
    {
        if ($inspection->isSealed()) {
            throw ValidationException::withMessages([
                'inspection' => 'Una inspección sellada forma parte del expediente auditable y no puede eliminarse.',
            ]);
        }
    }
}
