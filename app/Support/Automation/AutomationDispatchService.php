<?php

declare(strict_types=1);

namespace App\Support\Automation;

use App\Jobs\SendAutomationDeliveryJob;
use App\Models\AutomationDelivery;
use App\Models\Company;

final class AutomationDispatchService
{
    public function queueOnce(
        Company $company,
        string $channel,
        string $eventKey,
        string $subjectType,
        int $subjectId,
        string $recipient,
        string $title,
        string $message,
    ): ?AutomationDelivery {
        $recipient = trim($recipient);

        if ($recipient === '') {
            return null;
        }

        /** @var AutomationDelivery $delivery */
        $delivery = AutomationDelivery::query()->firstOrCreate(
            [
                'event_key' => $eventKey,
                'channel' => $channel,
            ],
            [
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'recipient' => $recipient,
                'title' => $title,
                'message' => $message,
                'status' => 'pending',
                'attempts' => 0,
            ],
        );

        if (
            $delivery->wasRecentlyCreated
            || ($delivery->status === 'failed' && $delivery->attempts < 3)
        ) {
            SendAutomationDeliveryJob::dispatch(
                (int) $company->getKey(),
                (int) $delivery->getKey(),
            )->onQueue('notifications');
        }

        return $delivery;
    }
}
