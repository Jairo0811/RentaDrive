<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Commercial\OnboardingChecklist;
use App\Support\Commercial\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class OnboardingController extends Controller
{
    public function show(
        Request $request,
        OnboardingChecklist $checklist,
        SubscriptionService $subscriptions,
    ): View {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $items = $checklist->for($company);

        return view('onboarding.show', [
            'company' => $company,
            'items' => $items,
            'progress' => $checklist->progress($items),
            'currentSubscription' => $subscriptions->current($company),
        ]);
    }

    public function complete(
        Request $request,
        OnboardingChecklist $checklist,
    ): RedirectResponse {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $items = $checklist->for($company);

        if (! $checklist->complete($items)) {
            throw ValidationException::withMessages([
                'onboarding' => 'Completa todos los pasos obligatorios antes de cerrar el onboarding.',
            ]);
        }

        $company->update(['onboarding_completed_at' => now($company->timezone)]);

        return redirect()->route('dashboard')->with('status', 'Onboarding comercial completado.');
    }
}
