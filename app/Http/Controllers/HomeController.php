<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user === null) {
            $company = Company::query()
                ->where('public_domain', strtolower($request->getHost()))
                ->whereIn('status', ['active', 'trial'])
                ->first();

            if (
                $company !== null
                && ($company->status !== 'trial' || $company->trial_ends_at === null || $company->trial_ends_at->isFuture())
            ) {
                return redirect('/r/'.$company->slug);
            }

            return view('home');
        }

        return redirect()->route(
            $user->isPlatformAdmin() ? 'platform.dashboard' : 'dashboard',
        );
    }
}
