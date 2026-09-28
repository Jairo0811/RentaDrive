<x-public-layout
    :title="$company->name.' · Depósito de reserva'"
    :company="$company"
    :home-url="route('public.booking.show', ['company' => $company->slug])"
    :badge="$company->name"
>
    <section class="mx-auto max-w-2xl px-5 py-16 sm:px-8">
        <div class="panel p-6 sm:p-8">
            <p class="brand-text text-sm font-black uppercase tracking-[.16em]">Depósito de reserva</p>
            <h1 class="mt-2 text-3xl font-black">{{ $reservation->code }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $reservation->vehicle?->model?->display_name }}</p>

            <dl class="mt-7 space-y-3 rounded-2xl border border-slate-200 p-5 text-sm dark:border-slate-800">
                <div class="flex justify-between"><dt class="text-slate-500">Total estimado</dt><dd class="font-bold">{{ $company->currency }} {{ number_format((float) $reservation->estimated_total, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Depósito requerido</dt><dd class="font-bold">{{ $company->currency }} {{ number_format((float) $reservation->deposit_required, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Pagado</dt><dd class="font-bold text-emerald-600">{{ $company->currency }} {{ number_format((float) $reservation->deposit_paid, 2) }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-3 text-lg font-black dark:border-slate-800"><dt>Pendiente</dt><dd class="brand-text">{{ $company->currency }} {{ number_format($outstanding, 2) }}</dd></div>
            </dl>

            @if ($outstanding <= 0)
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                    El depósito ya está cubierto.
                </div>
            @elseif ($gateway === 'manual')
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                    Esta empresa tiene cobro manual configurado. Contacta al rent-a-car para completar el depósito; el pago quedará conciliado en RentaDrive.
                </div>
            @else
                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">
                    @csrf
                    <button class="btn-primary w-full py-3">
                        Pagar {{ $company->currency }} {{ number_format($outstanding, 2) }}
                    </button>
                </form>
            @endif

            @isset($intent)
                <p class="mt-4 text-xs text-slate-500">Intento de pago: {{ $intent->public_id }} · {{ $intent->status }}</p>
            @endisset
        </div>
    </section>
</x-public-layout>
