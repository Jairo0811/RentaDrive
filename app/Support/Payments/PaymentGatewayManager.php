<?php

declare(strict_types=1);

namespace App\Support\Payments;

use App\Models\Company;
use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\Gateways\HostedPaymentGateway;
use App\Support\Payments\Gateways\ManualPaymentGateway;

final class PaymentGatewayManager
{
    public function __construct(private readonly PaymentConfig $config) {}

    public function forCompany(Company $company): PaymentGateway
    {
        return match ($this->config->gateway($company)) {
            'hosted' => app(HostedPaymentGateway::class),
            default => app(ManualPaymentGateway::class),
        };
    }
}
