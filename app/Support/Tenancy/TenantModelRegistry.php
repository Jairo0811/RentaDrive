<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\AuditLog;
use App\Models\BookingOption;
use App\Models\Customer;
use App\Models\FiscalDraft;
use App\Models\FiscalProfile;
use App\Models\RentalAcceptance;
use App\Models\AutomationDelivery;
use App\Models\Inspection;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentIntent;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\ReservationBookingOption;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleMaintenance;
use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Model;

final class TenantModelRegistry
{
    /**
     * @return list<class-string<Model>>
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
            BookingOption::class,
            ReservationBookingOption::class,
            Rental::class,
            Inspection::class,
            Invoice::class,
            Payment::class,
            PaymentIntent::class,
            PaymentEvent::class,
            FiscalProfile::class,
            FiscalDraft::class,
            RentalAcceptance::class,
            AutomationDelivery::class,
            Setting::class,
            AuditLog::class,
        ];
    }

    /**
     * @return list<class-string<Model>>
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
