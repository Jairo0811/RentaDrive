<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'settings' => Setting::query()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_rnc' => ['nullable', 'string', 'max:30'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'in:DOP,USD'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
            'default_pickup_location' => ['required', 'string', 'max:255'],
        ]);

        $map = [
            'business_name' => ['general', 'business.name'],
            'business_rnc' => ['general', 'business.rnc'],
            'business_phone' => ['general', 'business.phone'],
            'business_email' => ['general', 'business.email'],
            'business_address' => ['general', 'business.address'],
            'currency' => ['billing', 'billing.currency'],
            'tax_rate' => ['billing', 'billing.tax_rate'],
            'default_pickup_location' => ['operations', 'operations.default_pickup_location'],
        ];

        DB::transaction(function () use ($data, $map): void {
            foreach ($map as $field => [$group, $key]) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['group' => $group, 'value' => $data[$field] ?? null, 'type' => 'string'],
                );
            }
        });

        return back()->with('status', 'Configuración guardada.');
    }
}
