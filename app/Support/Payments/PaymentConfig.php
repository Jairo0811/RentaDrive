<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Company;

final class PaymentConfig
{
    public function gateway(Company $company): string
    {
        $gateway = strtolower((string) $company->setting('payments.gateway', 'manual'));

        return in_array($gateway, ['manual', 'hosted'], true) ? $gateway : 'manual';
    }

    public function depositRequired(Company $company, float $total): float
    {
        $type = (string) $company->setting('payments.deposit_type', 'percent');
        $value = max(0, (float) $company->setting('payments.deposit_value', 20));

        $amount = $type === 'fixed'
            ? $value
            : $total * (min(100, $value) / 100);

        return round(min($total, $amount), 2);
    }
}
