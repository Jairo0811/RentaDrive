<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use App\Support\Payments\PaymentConfig;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PublicPaymentController extends Controller
{
    public function show(
        Company $company,
        string $code,
        TenantContext $tenant,
        PaymentConfig $config,
    ): View {
        $this->activateTenant($company, $tenant);

        $reservation = Reservation::query()
            ->with(['customer', 'vehicle.model.brand'])
            ->where('code', $code)
            ->firstOrFail();

        $outstanding = max(0, round(
            (float) $reservation->deposit_required - (float) $reservation->deposit_paid,
            2,
        ));

        return view('public.deposit', [
            'company' => $company,
            'reservation' => $reservation,
            'outstanding' => $outstanding,
            'gateway' => $config->gateway($company),
        ]);
    }

    public function start(
        Request $request,
        Company $company,
        string $code,
        TenantContext $tenant,
        PaymentConfig $config,
        PaymentGatewayManager $gateways,
    ): RedirectResponse|View {
        $this->activateTenant($company, $tenant);

        $reservation = Reservation::query()
            ->with(['customer', 'vehicle.model.brand'])
            ->where('code', $code)
            ->firstOrFail();

        $outstanding = max(0, round(
            (float) $reservation->deposit_required - (float) $reservation->deposit_paid,
            2,
        ));

        if ($outstanding <= 0) {
            return redirect()->route('public.booking.confirmation', [
                'company' => $company->slug,
                'code' => $reservation->code,
            ])->with('status', 'El depósito requerido ya está cubierto.');
        }

        $gatewayName = $config->gateway($company);
        $idempotencyKey = sprintf(
            'reservation-deposit:%d:%s:%s',
            $reservation->getKey(),
            number_format($outstanding, 2, '.', ''),
            $company->currency,
        );

        /** @var PaymentIntent $intent */
        $intent = PaymentIntent::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'branch_id' => $reservation->branch_id,
                'reservation_id' => $reservation->getKey(),
                'public_id' => (string) Str::uuid(),
                'purpose' => 'reservation_deposit',
                'gateway' => $gatewayName,
                'amount' => $outstanding,
                'currency' => $company->currency,
                'status' => 'pending',
                'metadata' => [
                    'reservation_code' => $reservation->code,
                    'return_url' => route('public.booking.confirmation', [
                        'company' => $company->slug,
                        'code' => $reservation->code,
                    ]),
                ],
                'expires_at' => $reservation->start_at->copy()->subHour(),
            ],
        );

        if ($intent->status === 'paid') {
            return redirect()->route('public.booking.confirmation', [
                'company' => $company->slug,
                'code' => $reservation->code,
            ]);
        }

        if ($intent->checkout_url === null && $intent->gateway_reference === null) {
            $result = $gateways->forCompany($company)->createCheckout($intent);

            $intent->update([
                'gateway_reference' => $result['reference'],
                'checkout_url' => $result['checkout_url'],
                'status' => $result['status'],
                'metadata' => [
                    ...($intent->metadata ?? []),
                    'gateway_response' => $result['metadata'],
                ],
            ]);
        }

        if ($intent->checkout_url !== null) {
            return redirect()->away($intent->checkout_url);
        }

        return view('public.deposit', [
            'company' => $company,
            'reservation' => $reservation,
            'outstanding' => $outstanding,
            'gateway' => $gatewayName,
            'intent' => $intent,
        ]);
    }

    private function activateTenant(Company $company, TenantContext $tenant): void
    {
        abort_unless(in_array($company->status, ['active', 'trial'], true), 404);

        if (
            $company->status === 'trial'
            && $company->trial_ends_at !== null
            && $company->trial_ends_at->isPast()
        ) {
            abort(404);
        }

        $tenant->set($company);
    }
}
