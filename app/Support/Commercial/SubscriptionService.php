<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubscriptionService
{
    public function current(Company $company): ?Subscription
    {
        return $company->subscriptions()
            ->latest('started_at')
            ->latest('id')
            ->first();
    }

    public function createTrial(Company $company): Subscription
    {
        $days = max(1, (int) config('rentadrive.trial_days', 14));
        $trialEndsAt = now($company->timezone)->addDays($days);

        return DB::transaction(function () use ($company, $trialEndsAt): Subscription {
            $company->update([
                'status' => 'trial',
                'trial_ends_at' => $trialEndsAt,
            ]);

            return $company->subscriptions()->create([
                'plan_code' => $company->plan_code,
                'status' => 'trialing',
                'billing_cycle' => 'monthly',
                'provider' => 'manual',
                'started_at' => now($company->timezone),
                'trial_ends_at' => $trialEndsAt,
            ]);
        });
    }

    public function activate(
        Company $company,
        string $planCode,
        string $billingCycle = 'monthly',
        string $provider = 'manual',
        ?string $providerReference = null,
    ): Subscription {
        $this->assertPlan($planCode);

        return DB::transaction(function () use (
            $company,
            $planCode,
            $billingCycle,
            $provider,
            $providerReference,
        ): Subscription {
            $now = now($company->timezone);
            $periodEnd = $billingCycle === 'yearly'
                ? $now->copy()->addYear()
                : $now->copy()->addMonth();

            $company->subscriptions()
                ->whereIn('status', ['trialing', 'active', 'past_due'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => $now,
                    'updated_at' => $now,
                ]);

            $subscription = $company->subscriptions()->create([
                'plan_code' => $planCode,
                'status' => 'active',
                'billing_cycle' => $billingCycle,
                'provider' => $provider,
                'provider_reference' => $providerReference,
                'started_at' => $now,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $periodEnd,
            ]);

            $company->update([
                'plan_code' => $planCode,
                'status' => 'active',
                'trial_ends_at' => null,
            ]);

            return $subscription;
        });
    }

    public function markPastDue(Company $company, int $graceDays = 7): Subscription
    {
        $subscription = $this->current($company);

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription' => 'La empresa no tiene una suscripción registrada.',
            ]);
        }

        $subscription->update([
            'status' => 'past_due',
            'grace_ends_at' => now($company->timezone)->addDays(max(1, $graceDays)),
        ]);

        return $subscription->fresh();
    }

    public function cancel(Company $company): Subscription
    {
        $subscription = $this->current($company);

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription' => 'La empresa no tiene una suscripción registrada.',
            ]);
        }

        return DB::transaction(function () use ($company, $subscription): Subscription {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now($company->timezone),
            ]);

            $company->update([
                'status' => 'cancelled',
                'trial_ends_at' => null,
            ]);

            return $subscription->fresh();
        });
    }

    public function entitled(Company $company): bool
    {
        $subscription = $this->current($company);

        if ($subscription === null) {
            if (! in_array($company->status, ['active', 'trial'], true)) {
                return false;
            }

            return $company->status !== 'trial'
                || ($company->trial_ends_at !== null && $company->trial_ends_at->isFuture());
        }

        return $subscription->isEntitled();
    }

    private function assertPlan(string $planCode): void
    {
        if (! array_key_exists($planCode, (array) config('rentadrive.plans'))) {
            throw ValidationException::withMessages([
                'plan_code' => 'El plan comercial indicado no existe.',
            ]);
        }
    }
}
