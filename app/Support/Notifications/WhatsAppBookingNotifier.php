<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\Company;
use App\Models\Reservation;
use App\Support\Commercial\BookingConfig;
use Illuminate\Support\Facades\Http;

final class WhatsAppBookingNotifier
{
    public function __construct(private readonly BookingConfig $config) {}

    public function sendCreated(Company $company, Reservation $reservation): void
    {
        if (! $this->config->whatsappConfirmationEnabled($company)) {
            return;
        }

        $token = (string) config('services.whatsapp.token');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $template = (string) config('services.whatsapp.booking_template');

        $version = (string) config('services.whatsapp.graph_version');

        if ($token === '' || $phoneNumberId === '' || $version === '' || $template === '' || $reservation->customer?->phone === null) {
            return;
        }

        $phone = preg_replace('/\D+/', '', $reservation->customer->phone) ?? '';

        if ($phone === '') {
            return;
        }

        if (strlen($phone) === 10 && preg_match('/^(809|829|849)/', $phone) === 1) {
            $phone = '1'.$phone;
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
                        'code' => (string) config('services.whatsapp.booking_template_language', 'es'),
                    ],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $reservation->code],
                            ['type' => 'text', 'text' => $company->name],
                            ['type' => 'text', 'text' => $company->currency.' '.number_format((float) $reservation->estimated_total, 2)],
                        ],
                    ]],
                ],
            ])
            ->throw();
    }
}
