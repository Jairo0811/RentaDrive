<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $reservation->code }}</p><p class="text-xs text-slate-500">Detalle de reserva</p></div></x-slot>

    <x-page-header :title="'Reserva '.$reservation->code" :subtitle="$reservation->customer->full_name">
        <x-slot name="actions">
            @if (in_array($reservation->status, ['pending', 'confirmed']))
                @can('manage rentals')<a href="{{ route('rentals.create', ['reservation' => $reservation->id]) }}" class="btn-primary">Convertir en alquiler</a>@endcan
                @can('manage reservations')<a href="{{ route('reservations.edit', $reservation) }}" class="btn-secondary">Editar</a>@endcan
            @endif
        </x-slot>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel p-5 sm:p-6 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-black text-slate-950 dark:text-white">Programación</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ $reservation->source === 'public' ? 'Reserva recibida desde el portal público.' : 'Reserva creada por el equipo.' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $reservation->source === 'public' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $reservation->source === 'public' ? 'Online' : 'Interna' }}</span>
                    <x-status-badge :status="$reservation->status" />
                </div>
            </div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                @foreach ([
                    'Cliente' => $reservation->customer->full_name,
                    'Documento' => $reservation->customer->document_number,
                    'Sucursal' => $reservation->branch?->name ?? $reservation->pickup_location,
                    'Categoría' => $reservation->category->name,
                    'Vehículo' => $reservation->vehicle?->display_name ?? 'Pendiente de asignación',
                    'Inicio' => $reservation->start_at->format('d/m/Y h:i A'),
                    'Fin' => $reservation->end_at->format('d/m/Y h:i A'),
                    'Entrega' => $reservation->pickup_location,
                    'Devolución' => $reservation->return_location,
                ] as $label => $value)
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-950/50"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-2 font-semibold text-slate-800 dark:text-slate-200">{{ $value }}</p></div>
                @endforeach
            </div>

            @if ($reservation->bookingOptions->isNotEmpty())
                <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
                    <h3 class="font-black text-slate-950 dark:text-white">Extras y seguros</h3>
                    <div class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                        @foreach ($reservation->bookingOptions as $line)
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <div><p class="font-semibold">{{ $line->option->name }}</p><p class="text-xs text-slate-500">{{ $line->option->type === 'insurance' ? 'Seguro' : 'Extra' }}</p></div>
                                <strong>RD$ {{ number_format((float) $line->total, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
        <aside class="panel p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[.16em] text-blue-600">Estimado</p>
            <p class="mt-3 text-4xl font-black text-slate-950 dark:text-white">RD$ {{ number_format((float) $reservation->estimated_total, 2) }}</p>
            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Tarifa diaria</dt><dd class="font-bold">RD$ {{ number_format((float) $reservation->daily_rate, 2) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Alquiler</dt><dd class="font-bold">RD$ {{ number_format((float) ($reservation->subtotal ?? $reservation->estimated_total), 2) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Extras/seguros</dt><dd class="font-bold">RD$ {{ number_format((float) $reservation->extras_total, 2) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Depósito</dt><dd class="font-bold">RD$ {{ number_format((float) $reservation->deposit_amount, 2) }}</dd></div>
            </dl>
            @if ($reservation->notes)<div class="mt-6 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-300">{{ $reservation->notes }}</div>@endif
            @if ($reservation->rental)<a href="{{ route('rentals.show', $reservation->rental) }}" class="btn-secondary mt-6 w-full">Ver alquiler generado</a>@endif
        </aside>
    </div>
</x-app-layout>
