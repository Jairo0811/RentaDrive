<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
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
        ];

        $latestCompanies = Company::query()
            ->withCount(['branches', 'users'])
            ->latest()
            ->limit(8)
            ->get();

        return view('platform.dashboard', compact('metrics', 'latestCompanies'));
    }
}
