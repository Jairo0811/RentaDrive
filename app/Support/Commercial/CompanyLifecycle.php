<?php

declare(strict_types=1);

namespace App\Support\Commercial;

use App\Models\Company;
use App\Models\CompanyLifecycleEvent;
use Illuminate\Support\Facades\DB;

final class CompanyLifecycle
{
    public function record(Company $company, ?string $previousStatus, ?string $previousPlan, string $reason, ?int $actorId = null): void
    {
        if ($previousStatus === $company->status && $previousPlan === $company->plan_code) {
            return;
        }

        CompanyLifecycleEvent::query()->create([
            'company_id' => $company->getKey(),
            'actor_id' => $actorId,
            'previous_status' => $previousStatus,
            'new_status' => $company->status,
            'previous_plan' => $previousPlan,
            'new_plan' => $company->plan_code,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    public function expireTrials(): int
    {
        $count = 0;

        Company::query()->where('status', 'trial')
            ->where('trial_ends_at', '<=', now())
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $companyId) use (&$count): void {
                DB::transaction(function () use ($companyId, &$count): void {
                    $company = Company::query()->lockForUpdate()->find($companyId);

                    if ($company === null || $company->status !== 'trial'
                        || $company->trial_ends_at === null || $company->trial_ends_at->isFuture()) {
                        return;
                    }

                    $previousStatus = $company->status;
                    $previousPlan = $company->plan_code;
                    $company->update(['status' => 'suspended']);
                    $this->record($company, $previousStatus, $previousPlan, 'trial_expired');
                    $count++;
                }, 3);
            });

        return $count;
    }
}
