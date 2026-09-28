<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class SettingController extends Controller
{
    private const FIXED_ITBIS_RATE = '18';

    public function edit(Request $request): View
    {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        return view('settings.edit', [
            'settings' => Setting::query()->pluck('value', 'key'),
            'company' => $company,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_rnc' => ['nullable', 'string', 'max:30'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'in:DOP,USD'],
            'default_pickup_location' => ['required', 'string', 'max:255'],
            'brand_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_brand_logo' => ['nullable', 'boolean'],
            'public_domain' => [
                'nullable',
                'string',
                'max:190',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                Rule::unique('companies', 'public_domain')->ignore($company->getKey()),
            ],
            'weekly_discount_percent' => ['required', 'numeric', 'between:0,100'],
            'monthly_discount_percent' => ['required', 'numeric', 'between:0,100'],
            'cancellation_hours' => ['required', 'integer', 'between:0,720'],
            'seasonal_rules' => ['nullable', 'string', 'max:10000'],
            'booking_extras' => ['nullable', 'string', 'max:10000'],
            'promo_codes' => ['nullable', 'string', 'max:10000'],
            'email_confirmation_enabled' => ['nullable', 'boolean'],
            'whatsapp_confirmation_enabled' => ['nullable', 'boolean'],
        ]);

        $data['tax_rate'] = self::FIXED_ITBIS_RATE;
        $data['public_domain'] = isset($data['public_domain']) && $data['public_domain'] !== ''
            ? strtolower(trim($data['public_domain']))
            : null;

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

        DB::transaction(function () use ($request, $company, $data, $map): void {
            foreach ($map as $field => [$group, $key]) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['group' => $group, 'value' => $data[$field] ?? null, 'type' => 'string'],
                );
            }

            $companySettings = $company->settings ?? [];
            Arr::set($companySettings, 'branding.primary_color', $data['brand_primary_color']);
            Arr::set($companySettings, 'branding.accent_color', $data['brand_accent_color']);
            Arr::set($companySettings, 'booking.weekly_discount_percent', (float) $data['weekly_discount_percent']);
            Arr::set($companySettings, 'booking.monthly_discount_percent', (float) $data['monthly_discount_percent']);
            Arr::set($companySettings, 'booking.cancellation_hours', (int) $data['cancellation_hours']);
            Arr::set($companySettings, 'booking.seasonal_rules', $data['seasonal_rules'] ?? '');
            Arr::set($companySettings, 'booking.extras', $data['booking_extras'] ?? '');
            Arr::set($companySettings, 'booking.promo_codes', $data['promo_codes'] ?? '');
            Arr::set($companySettings, 'booking.email_confirmation_enabled', $request->boolean('email_confirmation_enabled'));
            Arr::set($companySettings, 'booking.whatsapp_confirmation_enabled', $request->boolean('whatsapp_confirmation_enabled'));

            $oldLogoPath = Arr::get($companySettings, 'branding.logo_path');

            if ($request->boolean('remove_brand_logo') && is_string($oldLogoPath) && $oldLogoPath !== '') {
                Storage::disk('public')->delete($oldLogoPath);
                Arr::forget($companySettings, 'branding.logo_path');
                $oldLogoPath = null;
            }

            if ($request->hasFile('brand_logo')) {
                if (is_string($oldLogoPath) && $oldLogoPath !== '') {
                    Storage::disk('public')->delete($oldLogoPath);
                }

                Arr::set(
                    $companySettings,
                    'branding.logo_path',
                    $request->file('brand_logo')->store('branding/'.$company->getKey(), 'public'),
                );
            }

            $company->fill([
                'name' => $data['business_name'],
                'rnc' => $data['business_rnc'] ?? null,
                'phone' => $data['business_phone'] ?? null,
                'email' => $data['business_email'] ?? null,
                'currency' => $data['currency'],
                'public_domain' => $data['public_domain'],
                'settings' => $companySettings,
            ])->save();
        });

        return back()->with('status', 'Configuración comercial guardada.');
    }
}
