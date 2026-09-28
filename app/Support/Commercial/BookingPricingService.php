<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Domain\Operations\Services\ReservationAvailabilityService;
use App\Models\Company;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;

final class BookingPricingService
{
    public function __construct(
        private readonly BookingConfig $config,
        private readonly ReservationAvailabilityService $availability,
    ) {}

    /**
     * @param  array<int, string>  $selectedExtraCodes
     * @return array<string, mixed>
     */
    public function quote(
        Company $company,
        Vehicle $vehicle,
        CarbonImmutable $startAt,
        CarbonImmutable $endAt,
        array $selectedExtraCodes = [],
        ?string $promoCode = null,
    ): array {
        $days = $this->availability->rentalDays($startAt, $endAt);
        $dailyRate = $vehicle->effective_daily_rate;
        $seasonalTotal = 0.0;
        $seasonNames = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $startAt->startOfDay()->addDays($offset);
            $multiplier = 1.0;

            foreach ($this->config->seasons($company) as $season) {
                if ($date->betweenIncluded($season['start'], $season['end'])) {
                    $multiplier = $season['multiplier'];
                    $seasonNames[$season['name']] = true;
                    break;
                }
            }

            $seasonalTotal += $dailyRate * $multiplier;
        }

        $durationDiscountPercent = $days >= 30
            ? $this->config->monthlyDiscountPercent($company)
            : ($days >= 7 ? $this->config->weeklyDiscountPercent($company) : 0.0);

        $durationDiscount = round($seasonalTotal * ($durationDiscountPercent / 100), 2);
        $rentalSubtotal = max(0, round($seasonalTotal - $durationDiscount, 2));

        $selectedExtraCodes = array_values(array_unique(array_map(
            static fn (string $code): string => strtoupper(trim($code)),
            $selectedExtraCodes,
        )));

        $selectedExtras = [];
        $extrasTotal = 0.0;

        foreach ($this->config->extras($company) as $extra) {
            if (! in_array($extra['code'], $selectedExtraCodes, true)) {
                continue;
            }

            $amount = $extra['billing'] === 'per_day'
                ? $extra['price'] * $days
                : $extra['price'];

            $amount = round($amount, 2);
            $extrasTotal += $amount;
            $selectedExtras[] = [...$extra, 'amount' => $amount];
        }

        $promoCode = strtoupper(trim((string) $promoCode));
        $promoDiscount = 0.0;
        $appliedPromo = null;
        $now = CarbonImmutable::now($company->timezone);

        if ($promoCode !== '') {
            foreach ($this->config->promotions($company) as $promotion) {
                if ($promotion['code'] !== $promoCode) {
                    continue;
                }

                if ($promotion['start'] !== null && $now->lt($promotion['start'])) {
                    continue;
                }

                if ($promotion['end'] !== null && $now->gt($promotion['end'])) {
                    continue;
                }

                $promoDiscount = $promotion['type'] === 'percent'
                    ? $rentalSubtotal * (min(100, $promotion['value']) / 100)
                    : $promotion['value'];

                $promoDiscount = round(min($rentalSubtotal, $promoDiscount), 2);
                $appliedPromo = $promotion;
                break;
            }
        }

        $estimatedTotal = max(0, round($rentalSubtotal + $extrasTotal - $promoDiscount, 2));

        return [
            'days' => $days,
            'daily_rate' => $dailyRate,
            'seasonal_total' => round($seasonalTotal, 2),
            'season_names' => array_keys($seasonNames),
            'duration_discount_percent' => $durationDiscountPercent,
            'duration_discount' => $durationDiscount,
            'rental_subtotal' => $rentalSubtotal,
            'selected_extras' => $selectedExtras,
            'extras_total' => round($extrasTotal, 2),
            'promo_code' => $appliedPromo['code'] ?? null,
            'promo_discount' => $promoDiscount,
            'discount_total' => round($durationDiscount + $promoDiscount, 2),
            'estimated_total' => $estimatedTotal,
        ];
    }
}
