<?php

declare(strict_types=1);

namespace App\Support\Automation\Contracts;

use App\Models\Company;

interface AutomationChannel
{
    public function name(): string;

    public function send(
        Company $company,
        string $recipient,
        string $title,
        string $message,
    ): void;
}
