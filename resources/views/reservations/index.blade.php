<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Reservas</p><p class="text-xs text-slate-500">Agenda y disponibilidad</p></div></x-slot>

    <x-page-header title="Reservas" subtitle="Programa vehículos, fechas, ubicaciones y tarifas estimadas.">
        <x-slot name="actions">@can('manage reservations')<a href="{{ route('reservations.create') }}" class="btn-primary">Nueva reserva</a>@endcan</x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_190px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Código o cliente">
        <select name="status" class="form-input">
            <option value="">Todos los estados</option>
            @foreach (['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'converted' => 'Convertida', 'cancelled' => 'Cancelada'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach
        </select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="table-shell">
        @if ($reservations->isEmpty())
            <x-empty-state title="No hay reservas" message="Crea la primera reserva y asegura la disponibilidad de la unidad." action="Nueva reserva" :href="route('reservations.create')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Reserva</th><th>Cliente</th><th>Vehículo / categoría</th><th>Período</th><th>Estimado</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($reservations as $reservation)
                            <tr>
                                <td class="font-bold text-slate-900 dark:text-white">{{ $reservation->code }}</td>
                                <td>{{ $reservation->customer->full_name }}</td>
                                <td><p>{{ $reservation->vehicle?->display_name ?? $reservation->category->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $reservation->pickup_location }}</p></td>
                                <td><p>{{ $reservation->start_at->format('d/m/Y h:i A') }}</p><p class="mt-1 text-xs text-slate-500">a {{ $reservation->end_at->format('d/m/Y h:i A') }}</p></td>
                                <td>RD$ {{ number_format((float) $reservation->estimated_total, 2) }}</td>
                                <td><x-status-badge :status="$reservation->status" /></td>
                                <td class="text-right"><a href="{{ route('reservations.show', $reservation) }}" class="font-bold text-blue-600">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $reservations->links() }}</div>
        @endif
    </div>
</x-app-layout>
