<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\Commercial\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PlatformSubscriptionController extends Controller
{
    public function activate(
        Request $request,
        Company $company,
        SubscriptionService $subscriptions,
    ): RedirectResponse {
        $data = $request->validate([
            'plan_code' => ['required', Rule::in(array_keys((array) config('rentadrive.plans')))],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'provider' => ['required', Rule::in(['manual', 'external'])],
            'provider_reference' => ['nullable', 'string', 'max:190'],
        ]);

        $subscriptions->activate(
            $company,
            $data['plan_code'],
            $data['billing_cycle'],
            $data['provider'],
            $data['provider_reference'] ?? null,
        );

        return back()->with('status', 'Suscripción activada y plan aplicado.');
    }

    public function pastDue(
        Request $request,
        Company $company,
        SubscriptionService $subscriptions,
    ): RedirectResponse {
        $data = $request->validate([
            'grace_days' => ['required', 'integer', 'between:1,30'],
        ]);

        $subscriptions->markPastDue($company, (int) $data['grace_days']);

        return back()->with('status', 'Suscripción marcada en mora con período de gracia.');
    }

    public function cancel(
        Company $company,
        SubscriptionService $subscriptions,
    ): RedirectResponse {
        $subscriptions->cancel($company);

        return back()->with('status', 'Suscripción cancelada y tenant bloqueado.');
    }
}
