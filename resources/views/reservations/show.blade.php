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
            <div class="flex items-center justify-between"><h2 class="font-black text-slate-950 dark:text-white">Programación</h2><x-status-badge :status="$reservation->status" /></div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                @foreach ([
                    'Cliente' => $reservation->customer->full_name,
                    'Documento' => $reservation->customer->document_number,
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
        </section>
        <aside class="panel p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[.16em] text-blue-600">Estimado</p>
            <p class="mt-3 text-4xl font-black text-slate-950 dark:text-white">RD$ {{ number_format((float) $reservation->estimated_total, 2) }}</p>
            <p class="mt-2 text-sm text-slate-500">RD$ {{ number_format((float) $reservation->daily_rate, 2) }} por día</p>
            @if ($reservation->notes)<div class="mt-6 border-t border-slate-200 pt-5 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-300">{{ $reservation->notes }}</div>@endif
            @if ($reservation->rental)<a href="{{ route('rentals.show', $reservation->rental) }}" class="btn-secondary mt-6 w-full">Ver alquiler generado</a>@endif
        </aside>
    </div>
</x-app-layout>
