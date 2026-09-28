<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Commercial\PublicDomainResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        PublicDomainResolver $domainResolver,
    ): RedirectResponse|View {
        $user = $request->user();

        if ($user === null) {
            $company = $domainResolver->resolve($request->getHost());

            if ($company !== null) {
                return redirect('/r/'.$company->slug);
            }

            return view('home');
        }

        return redirect()->route(
            $user->isPlatformAdmin() ? 'platform.dashboard' : 'dashboard',
        );
    }
}
