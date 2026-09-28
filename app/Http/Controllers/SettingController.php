<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Fiscal\FiscalSequenceService;
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

    public function edit(Request $request, FiscalSequenceService $fiscalSequences): View
    {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        return view('settings.edit', [
            'settings' => Setting::query()->pluck('value', 'key'),
            'company' => $company,
            'fiscalSequences' => $fiscalSequences->serialize(),
        ]);
    }

    public function update(
        Request $request,
        FiscalSequenceService $fiscalSequences,
    ): RedirectResponse {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        if ($request->filled('fiscal_rnc')) {
            $request->merge([
                'fiscal_rnc' => preg_replace('/\D+/', '', (string) $request->input('fiscal_rnc')),
            ]);
        }

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
            'payment_gateway' => ['required', Rule::in(['manual', 'hosted'])],
            'deposit_type' => ['required', Rule::in(['percent', 'fixed'])],
            'deposit_value' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'fiscal_enabled' => ['nullable', 'boolean'],
            'fiscal_mode' => ['required', Rule::in(['paper', 'electronic'])],
            'fiscal_authorized_electronic_issuer' => ['nullable', 'boolean'],
            'fiscal_legal_name' => ['nullable', 'string', 'max:160'],
            'fiscal_rnc' => ['nullable', 'digits:9'],
            'fiscal_address' => ['nullable', 'string', 'max:255'],
            'fiscal_sequences' => ['nullable', 'string', 'max:10000'],
            'automation_email_enabled' => ['nullable', 'boolean'],
            'automation_whatsapp_enabled' => ['nullable', 'boolean'],
            'reservation_reminder_hours' => ['required', 'integer', 'between:1,168'],
            'return_reminder_hours' => ['required', 'integer', 'between:1,72'],
            'maintenance_reminder_days' => ['required', 'integer', 'between:1,90'],
            'document_reminder_days' => ['required', 'integer', 'between:1,180'],
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

        DB::transaction(function () use ($request, $company, $data, $map, $fiscalSequences): void {
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

            Arr::set($companySettings, 'payments.gateway', $data['payment_gateway']);
            Arr::set($companySettings, 'payments.deposit_type', $data['deposit_type']);
            Arr::set($companySettings, 'payments.deposit_value', (float) $data['deposit_value']);

            Arr::set($companySettings, 'fiscal.enabled', $request->boolean('fiscal_enabled'));
            Arr::set($companySettings, 'fiscal.mode', $data['fiscal_mode']);
            Arr::set(
                $companySettings,
                'fiscal.authorized_electronic_issuer',
                $request->boolean('fiscal_authorized_electronic_issuer'),
            );
            Arr::set($companySettings, 'fiscal.legal_name', $data['fiscal_legal_name'] ?? '');
            Arr::set($companySettings, 'fiscal.rnc', $data['fiscal_rnc'] ?? '');
            Arr::set($companySettings, 'fiscal.address', $data['fiscal_address'] ?? '');

            Arr::set($companySettings, 'automation.email_enabled', $request->boolean('automation_email_enabled'));
            Arr::set($companySettings, 'automation.whatsapp_enabled', $request->boolean('automation_whatsapp_enabled'));
            Arr::set($companySettings, 'automation.reservation_reminder_hours', (int) $data['reservation_reminder_hours']);
            Arr::set($companySettings, 'automation.return_reminder_hours', (int) $data['return_reminder_hours']);
            Arr::set($companySettings, 'automation.maintenance_reminder_days', (int) $data['maintenance_reminder_days']);
            Arr::set($companySettings, 'automation.document_reminder_days', (int) $data['document_reminder_days']);

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

            $fiscalSequences->syncFromText($company, (string) ($data['fiscal_sequences'] ?? ''));
        });

        return back()->with('status', 'Configuración comercial, fiscal y de automatizaciones guardada.');
    }
}
