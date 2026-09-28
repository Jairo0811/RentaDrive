<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

final class BookingConfig
{
    public function primaryColor(Company $company): string
    {
        return (string) Arr::get($company->settings ?? [], 'branding.primary_color', '#0568f5');
    }

    public function accentColor(Company $company): string
    {
        return (string) Arr::get($company->settings ?? [], 'branding.accent_color', '#e2232e');
    }

    public function weeklyDiscountPercent(Company $company): float
    {
        return $this->percent(Arr::get($company->settings ?? [], 'booking.weekly_discount_percent', 5));
    }

    public function monthlyDiscountPercent(Company $company): float
    {
        return $this->percent(Arr::get($company->settings ?? [], 'booking.monthly_discount_percent', 12));
    }

    public function cancellationHours(Company $company): int
    {
        return max(0, min(720, (int) Arr::get($company->settings ?? [], 'booking.cancellation_hours', 24)));
    }

    public function emailConfirmationEnabled(Company $company): bool
    {
        return (bool) Arr::get($company->settings ?? [], 'booking.email_confirmation_enabled', true);
    }

    public function whatsappConfirmationEnabled(Company $company): bool
    {
        return (bool) Arr::get($company->settings ?? [], 'booking.whatsapp_confirmation_enabled', false);
    }

    /**
     * @return array<int, array{code:string,name:string,price:float,billing:string,type:string}>
     */
    public function extras(Company $company): array
    {
        $raw = (string) Arr::get($company->settings ?? [], 'booking.extras', '');
        $items = [];

        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));

            if (count($parts) < 5) {
                continue;
            }

            [$code, $name, $price, $billing, $type] = $parts;
            $billing = strtolower($billing);
            $type = strtolower($type);

            if (
                $code === ''
                || $name === ''
                || ! is_numeric($price)
                || ! in_array($billing, ['per_day', 'flat'], true)
                || ! in_array($type, ['extra', 'insurance'], true)
            ) {
                continue;
            }

            $items[] = [
                'code' => strtoupper($code),
                'name' => $name,
                'price' => max(0, (float) $price),
                'billing' => $billing,
                'type' => $type,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{name:string,start:CarbonImmutable,end:CarbonImmutable,multiplier:float}>
     */
    public function seasons(Company $company): array
    {
        $raw = (string) Arr::get($company->settings ?? [], 'booking.seasonal_rules', '');
        $items = [];

        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));

            if (count($parts) < 4 || ! is_numeric($parts[3])) {
                continue;
            }

            try {
                $start = CarbonImmutable::parse($parts[1], $company->timezone)->startOfDay();
                $end = CarbonImmutable::parse($parts[2], $company->timezone)->endOfDay();
            } catch (\Throwable) {
                continue;
            }

            $multiplier = (float) $parts[3];

            if ($parts[0] === '' || $end->lt($start) || $multiplier <= 0) {
                continue;
            }

            $items[] = [
                'name' => $parts[0],
                'start' => $start,
                'end' => $end,
                'multiplier' => $multiplier,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{code:string,type:string,value:float,start:?CarbonImmutable,end:?CarbonImmutable}>
     */
    public function promotions(Company $company): array
    {
        $raw = (string) Arr::get($company->settings ?? [], 'booking.promo_codes', '');
        $items = [];

        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));

            if (count($parts) < 3 || ! is_numeric($parts[2])) {
                continue;
            }

            $type = strtolower($parts[1]);

            if ($parts[0] === '' || ! in_array($type, ['percent', 'fixed'], true)) {
                continue;
            }

            $start = $this->optionalDate($parts[3] ?? null, $company);
            $end = $this->optionalDate($parts[4] ?? null, $company, true);

            $items[] = [
                'code' => strtoupper($parts[0]),
                'type' => $type,
                'value' => max(0, (float) $parts[2]),
                'start' => $start,
                'end' => $end,
            ];
        }

        return $items;
    }

    private function percent(mixed $value): float
    {
        return max(0, min(100, (float) $value));
    }

    private function optionalDate(?string $value, Company $company, bool $endOfDay = false): ?CarbonImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value, $company->timezone);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
