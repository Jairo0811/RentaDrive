<x-public-layout
    :title="$company->name.' · Reserva '.$reservation->code"
    :company="$company"
    :home-url="route('public.booking.show', ['company' => $company->slug])"
    :badge="$company->name"
>
    <section class="mx-auto max-w-3xl px-5 py-16 sm:px-8 lg:py-24">
        <div class="panel overflow-hidden text-center">
            <div class="{{ $reservation->status === 'cancelled' ? 'bg-gradient-to-br from-slate-600 to-slate-800' : 'brand-gradient' }} px-6 py-10 text-white">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-white/15 ring-1 ring-inset ring-white/20">
                    <i class="fa-solid {{ $reservation->status === 'cancelled' ? 'fa-ban' : 'fa-check' }} text-2xl" aria-hidden="true"></i>
                </span>
                <p class="mt-5 text-sm font-black uppercase tracking-[.18em] text-white/80">
                    {{ $reservation->status === 'cancelled' ? 'Reserva cancelada' : 'Solicitud recibida' }}
                </p>
                <h1 class="mt-2 text-3xl font-black">Reserva {{ $reservation->code }}</h1>
            </div>

            <div class="p-6 text-left sm:p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                <p class="text-center text-slate-600 dark:text-slate-400">
                    @if ($reservation->status === 'cancelled')
                        La reserva fue cancelada el {{ $reservation->cancelled_at?->format('d/m/Y h:i A') }}.
                    @else
                        La reserva está <strong class="text-slate-900 dark:text-white">{{ $reservation->status === 'confirmed' ? 'confirmada' : 'pendiente de confirmación' }}</strong>.
                    @endif
                </p>

                <dl class="mx-auto mt-8 max-w-xl divide-y divide-slate-200 rounded-2xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">Vehículo</dt><dd class="text-right font-bold">{{ $reservation->vehicle->model->display_name }}</dd></div>
                    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">Recogida</dt><dd class="text-right font-bold">{{ $reservation->start_at->format('d/m/Y h:i A') }}</dd></div>
                    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">Devolución</dt><dd class="text-right font-bold">{{ $reservation->end_at->format('d/m/Y h:i A') }}</dd></div>

                    @if ((float) $reservation->extras_total > 0)
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">Extras / seguros</dt><dd class="text-right font-bold">{{ $company->currency }} {{ number_format((float) $reservation->extras_total, 2) }}</dd></div>
                    @endif

                    @if ((float) $reservation->discount_total > 0)
                        <div class="flex justify-between gap-4 p-4 text-emerald-600"><dt>Descuentos</dt><dd class="text-right font-bold">- {{ $company->currency }} {{ number_format((float) $reservation->discount_total, 2) }}</dd></div>
                    @endif

                    @if ($reservation->promo_code)
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">Promoción</dt><dd class="text-right font-bold">{{ $reservation->promo_code }}</dd></div>
                    @endif

                    <div class="flex justify-between gap-4 p-4"><dt class="font-black">Total estimado</dt><dd class="brand-text text-right font-black">{{ $company->currency }} {{ number_format((float) $reservation->estimated_total, 2) }}</dd></div>
                    @if ((float) $reservation->deposit_required > 0)
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">Depósito requerido</dt><dd class="text-right font-bold">{{ $company->currency }} {{ number_format((float) $reservation->deposit_required, 2) }}</dd></div>
                        <div class="flex justify-between gap-4 p-4"><dt class="text-slate-500">Depósito pagado</dt><dd class="text-right font-bold text-emerald-600">{{ $company->currency }} {{ number_format((float) $reservation->deposit_paid, 2) }}</dd></div>
                    @endif
                </dl>

                @if (is_array($reservation->pricing_breakdown) && ! empty($reservation->pricing_breakdown['selected_extras']))
                    <div class="mx-auto mt-5 max-w-xl rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-800/60">
                        <p class="font-black">Incluido en la cotización</p>
                        <ul class="mt-2 space-y-1 text-slate-600 dark:text-slate-300">
                            @foreach ($reservation->pricing_breakdown['selected_extras'] as $extra)
                                <li>{{ $extra['name'] }} — {{ $company->currency }} {{ number_format((float) $extra['amount'], 2) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('public.booking.show', ['company' => $company->slug]) }}" class="btn-primary">Volver al portal</a>

                    @if ($depositPaymentUrl)
                        <a href="{{ $depositPaymentUrl }}" class="btn-primary">
                            Pagar depósito
                        </a>
                    @endif

                    @if ($cancellationUrl)
                        <a href="{{ $cancellationUrl }}" class="btn-secondary">
                            Gestionar cancelación
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
