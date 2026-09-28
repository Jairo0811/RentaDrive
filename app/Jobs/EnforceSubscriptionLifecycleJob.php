<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class EnforceSubscriptionLifecycleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $now = now();

        Subscription::query()
            ->with('company')
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', $now)
            ->chunkById(100, function ($subscriptions) use ($now): void {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, $now): void {
                        $subscription->update(['status' => 'expired']);

                        if ($subscription->company?->status === 'trial') {
                            $subscription->company->update([
                                'status' => 'suspended',
                                'trial_ends_at' => null,
                            ]);
                        }
                    });
                }
            });

        Subscription::query()
            ->with('company')
            ->where('status', 'active')
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<=', $now)
            ->chunkById(100, function ($subscriptions) use ($now): void {
                foreach ($subscriptions as $subscription) {
                    $subscription->update([
                        'status' => 'past_due',
                        'grace_ends_at' => $now->copy()->addDays(
                            max(1, (int) config('rentadrive.subscription_grace_days', 7)),
                        ),
                    ]);
                }
            });

        Subscription::query()
            ->with('company')
            ->where('status', 'past_due')
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<=', $now)
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription): void {
                        $subscription->update(['status' => 'suspended']);

                        if ($subscription->company !== null) {
                            $subscription->company->update(['status' => 'suspended']);
                        }
                    });
                }
            });
    }
}
