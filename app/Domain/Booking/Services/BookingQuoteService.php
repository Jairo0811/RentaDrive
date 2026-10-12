<?php

declare(strict_types=1);

namespace App\Domain\Booking\Services;

use App\Domain\Operations\Services\ReservationAvailabilityService;
use App\Models\BookingOption;
use App\Models\Branch;
use App\Models\VehicleCategory;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class BookingQuoteService
{
    public function __construct(private readonly ReservationAvailabilityService $availability) {}

    /**
     * @param  array<int, int|string>  $optionIds
     * @return array{
     *     days: int,
     *     available_count: int,
     *     daily_rate: float,
     *     subtotal: float,
     *     extras_total: float,
     *     estimated_total: float,
     *     deposit_amount: float,
     *     options: Collection<int, array{id:int,name:string,type:string,pricing_mode:string,unit_price:float,total:float}>
     * }
     */
    public function quote(
        Branch $branch,
        VehicleCategory $category,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        array $optionIds = [],
    ): array {
        $availableCount = $this->availability->availableCategoryCount(
            $branch,
            $category,
            $startAt,
            $endAt,
        );

        if ($availableCount < 1) {
            throw ValidationException::withMessages([
                'vehicle_category_id' => 'Ya no hay vehículos disponibles en esta categoría para el período seleccionado.',
            ]);
        }

        $days = $this->availability->rentalDays($startAt, $endAt);
        $dailyRate = (float) $category->daily_rate;
        $subtotal = round($days * $dailyRate, 2);

        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));

        $options = BookingOption::query()
            ->where('is_active', true)
            ->when($optionIds !== [], fn ($query) => $query->whereIn('id', $optionIds))
            ->when($optionIds === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(function (BookingOption $option) use ($days): array {
                $unitPrice = (float) $option->price;
                $total = $option->pricing_mode === 'per_day'
                    ? round($unitPrice * $days, 2)
                    : $unitPrice;

                return [
                    'id' => (int) $option->getKey(),
                    'name' => $option->name,
                    'type' => $option->type,
                    'pricing_mode' => $option->pricing_mode,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            });

        if ($options->count() !== count($optionIds)) {
            throw ValidationException::withMessages([
                'booking_option_ids' => 'Una de las opciones seleccionadas ya no está disponible.',
            ]);
        }

        $extrasTotal = round((float) $options->sum('total'), 2);

        return [
            'days' => $days,
            'available_count' => $availableCount,
            'daily_rate' => $dailyRate,
            'subtotal' => $subtotal,
            'extras_total' => $extrasTotal,
            'estimated_total' => round($subtotal + $extrasTotal, 2),
            'deposit_amount' => (float) $category->deposit_amount,
            'options' => $options,
        ];
    }
}
