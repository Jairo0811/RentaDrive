<?php

declare(strict_types=1);

namespace App\Support\Automation\Channels;

use App\Models\Company;
use App\Support\Automation\Contracts\AutomationChannel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class WhatsAppAutomationChannel implements AutomationChannel
{
    public function name(): string
    {
        return 'whatsapp';
    }

    public function send(
        Company $company,
        string $recipient,
        string $title,
        string $message,
    ): void {
        $token = trim((string) config('services.whatsapp.token'));
        $phoneNumberId = trim((string) config('services.whatsapp.phone_number_id'));
        $version = trim((string) config('services.whatsapp.graph_version'));
        $template = trim((string) config('services.whatsapp.automation_template'));

        if ($token === '' || $phoneNumberId === '' || $version === '' || $template === '') {
            throw new RuntimeException('El canal WhatsApp de automatizaciones no está configurado.');
        }

        $phone = preg_replace('/\D+/', '', $recipient) ?? '';

        if (strlen($phone) === 10 && preg_match('/^(809|829|849)/', $phone) === 1) {
            $phone = '1'.$phone;
        }

        if ($phone === '') {
            throw new RuntimeException('El destinatario de WhatsApp no tiene un número válido.');
        }

        Http::withToken($token)
            ->timeout(10)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => [
                        'code' => (string) config('services.whatsapp.automation_template_language', 'es'),
                    ],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $title],
                            ['type' => 'text', 'text' => $message],
                            ['type' => 'text', 'text' => $company->name],
                        ],
                    ]],
                ],
            ])
            ->throw();
    }
}
