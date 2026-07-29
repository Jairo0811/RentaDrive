<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Reportes</p><p class="text-xs text-slate-500">Indicadores de operación y cobro</p></div></x-slot>

    <x-page-header title="Reportes" subtitle="Analiza el desempeño por período y exporta las operaciones.">
        <x-slot name="actions"><a href="{{ route('reports.export', request()->only('from', 'to')) }}" class="btn-primary">Exportar CSV</a></x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-6 grid gap-3 p-4 sm:grid-cols-[180px_180px_auto]">
        <div><label class="form-label" for="from">Desde</label><input id="from" type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-input"></div>
        <div><label class="form-label" for="to">Hasta</label><input id="to" type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-input"></div>
        <div class="flex items-end"><button class="btn-secondary">Actualizar</button></div>
    </form>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['Alquileres', $metrics['rental_count'], 'En el período'],
            ['Facturado', 'RD$ '.number_format((float) $metrics['billed'], 2), 'Emisión del período'],
            ['Cobrado', 'RD$ '.number_format((float) $metrics['collected'], 2), 'Pagos del período'],
            ['Por cobrar', 'RD$ '.number_format((float) $metrics['outstanding'], 2), 'Balance global'],
            ['Utilización', $metrics['utilization'].'%', 'Flota actualmente rentada'],
        ] as [$label, $value, $note])
            <article class="panel p-5"><p class="text-xs font-bold uppercase tracking-[.14em] text-slate-400">{{ $label }}</p><p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">{{ $value }}</p><p class="mt-1 text-xs text-slate-500">{{ $note }}</p></article>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_.7fr]">
        <section class="table-shell">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-black text-slate-950 dark:text-white">Operaciones del período</h2></div>
            @if ($rentals->isEmpty())<x-empty-state title="Sin operaciones" message="No hay alquileres dentro del rango elegido." />
            @else<div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Código</th><th>Cliente</th><th>Vehículo</th><th>Inicio</th><th>Total</th><th>Estado</th></tr></thead><tbody>@foreach ($rentals as $rental)<tr><td><a href="{{ route('rentals.show', $rental) }}" class="font-bold text-blue-600">{{ $rental->code }}</a></td><td>{{ $rental->customer->full_name }}</td><td>{{ $rental->vehicle->display_name }}</td><td>{{ $rental->start_at->format('d/m/Y') }}</td><td>RD$ {{ number_format((float) $rental->total, 2) }}</td><td><x-status-badge :status="$rental->status" /></td></tr>@endforeach</tbody></table></div>@endif
        </section>
        <aside class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Flota por estado</h2>
            <div class="mt-5 space-y-4">
                @php $fleetTotal = max(1, (int) collect($fleetByStatus)->sum()); @endphp
                @foreach (['available', 'reserved', 'rented', 'maintenance', 'inactive'] as $status)
                    @php $count = (int) ($fleetByStatus[$status] ?? 0); $percentage = round(($count / $fleetTotal) * 100); @endphp
                    <div><div class="flex justify-between text-sm"><x-status-badge :status="$status" /><strong>{{ $count }}</strong></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-blue-500" style="width: {{ $percentage }}%"></div></div></div>
                @endforeach
            </div>
        </aside>
    </div>
</x-app-layout>
