<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user === null) {
            return view('home');
        }

        return redirect()->route(
            $user->isPlatformAdmin() ? 'platform.dashboard' : 'dashboard',
        );
    }
}
