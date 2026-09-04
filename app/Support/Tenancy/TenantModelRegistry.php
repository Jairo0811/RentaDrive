<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleMaintenance;
use App\Models\VehicleModel;

final class TenantModelRegistry
{
    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function models(): array
    {
        return [
            Customer::class,
            VehicleBrand::class,
            VehicleCategory::class,
            VehicleModel::class,
            Vehicle::class,
            VehicleMaintenance::class,
            Reservation::class,
            Rental::class,
            Inspection::class,
            Invoice::class,
            Payment::class,
            Setting::class,
            AuditLog::class,
        ];
    }

    /**
     * @return list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    public static function branchModels(): array
    {
        return [
            Vehicle::class,
            Reservation::class,
            Rental::class,
        ];
    }
}
