<?php

declare(strict_types=1);

namespace App\Support\Automation\Channels;

use App\Mail\AutomationAlertMail;
use App\Models\Company;
use App\Support\Automation\Contracts\AutomationChannel;
use Illuminate\Support\Facades\Mail;

final class EmailAutomationChannel implements AutomationChannel
{
    public function name(): string
    {
        return 'email';
    }

    public function send(
        Company $company,
        string $recipient,
        string $title,
        string $message,
    ): void {
        Mail::to($recipient)->send(
            new AutomationAlertMail($company->name, $title, $message),
        );
    }
}
