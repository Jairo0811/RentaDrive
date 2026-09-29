<x-public-layout
    :title="$company->name.' · Cancelar reserva'"
    :company="$company"
    :home-url="route('public.booking.show', ['company' => $company->slug])"
    :badge="$company->name"
>
    <section class="mx-auto max-w-2xl px-5 py-16 sm:px-8">
        <div class="panel p-6 sm:p-8">
            <p class="text-sm font-black uppercase tracking-[.16em] text-blue-600 dark:text-blue-400">Gestión de reserva</p>
            <h1 class="mt-2 text-3xl font-black">Cancelar {{ $reservation->code }}</h1>

            @if ($canCancel)
                <p class="mt-4 text-slate-600 dark:text-slate-400">
                    Esta reserva puede cancelarse en línea hasta {{ $deadline->format('d/m/Y h:i A') }}.
                </p>

                <form method="POST" action="{{ request()->fullUrl() }}" class="mt-7 space-y-5">
                    @csrf
                    <div>
                        <label for="reason" class="form-label">Motivo <span class="font-normal text-slate-400">(opcional)</span></label>
                        <textarea id="reason" name="reason" rows="3" class="form-input" maxlength="500">{{ old('reason') }}</textarea>
                    </div>
                    <button class="btn-primary w-full py-3">
                        Confirmar cancelación
                    </button>
                </form>
            @else
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                    Esta reserva ya no puede cancelarse en línea. Contacta directamente a {{ $company->name }}.
                </div>
            @endif
        </div>
    </section>
</x-public-layout>
