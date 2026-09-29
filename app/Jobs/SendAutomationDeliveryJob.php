<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AutomationDelivery;
use App\Models\Company;
use App\Support\Automation\AutomationChannelManager;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class SendAutomationDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $companyId,
        public readonly int $deliveryId,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(
        TenantContext $tenant,
        AutomationChannelManager $channels,
    ): void {
        $company = Company::query()->findOrFail($this->companyId);
        $tenant->set($company);

        try {
            /** @var AutomationDelivery $delivery */
            $delivery = AutomationDelivery::query()->findOrFail($this->deliveryId);

            if ($delivery->status === 'sent') {
                return;
            }

            $delivery->increment('attempts');

            $channels->resolve($delivery->channel)->send(
                $company,
                $delivery->recipient,
                $delivery->title,
                $delivery->message,
            );

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now($company->timezone),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            AutomationDelivery::query()
                ->whereKey($this->deliveryId)
                ->update([
                    'status' => 'failed',
                    'error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);

            throw $exception;
        } finally {
            $tenant->clear();
        }
    }
}
