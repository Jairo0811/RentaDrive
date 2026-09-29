<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\BackupSnapshot;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;

final class PlatformDashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'companies' => Company::query()->count(),
            'trial_companies' => Company::query()->where('status', 'trial')->count(),
            'active_companies' => Company::query()->where('status', 'active')->count(),
            'blocked_companies' => Company::query()->whereIn('status', ['suspended', 'cancelled'])->count(),
            'active_branches' => Branch::query()->where('is_active', true)->count(),
            'tenant_users' => User::query()
                ->where('is_platform_admin', false)
                ->whereNotNull('company_id')
                ->count(),
            'active_subscriptions' => Subscription::query()->whereIn('status', ['active', 'trialing'])->count(),
            'past_due_subscriptions' => Subscription::query()->where('status', 'past_due')->count(),
        ];

        $latestCompanies = Company::query()
            ->withCount(['branches', 'users'])
            ->latest()
            ->limit(8)
            ->get();

        $latestBackup = BackupSnapshot::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        return view('platform.dashboard', compact('metrics', 'latestCompanies', 'latestBackup'));
    }
}
