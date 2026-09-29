<?php

declare(strict_types=1);

namespace App\Support\Automation;

use App\Support\Automation\Channels\EmailAutomationChannel;
use App\Support\Automation\Channels\WhatsAppAutomationChannel;
use App\Support\Automation\Contracts\AutomationChannel;
use InvalidArgumentException;

final class AutomationChannelManager
{
    public function resolve(string $channel): AutomationChannel
    {
        return match ($channel) {
            'email' => app(EmailAutomationChannel::class),
            'whatsapp' => app(WhatsAppAutomationChannel::class),
            default => throw new InvalidArgumentException('Canal de automatización no soportado: '.$channel),
        };
    }
}
