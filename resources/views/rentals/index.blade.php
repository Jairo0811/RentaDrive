<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Alquileres</p><p class="text-xs text-slate-500">Contratos y devoluciones</p></div></x-slot>

    <x-page-header title="Alquileres" subtitle="Controla entregas, vehículos en uso y cierres.">
        <x-slot name="actions">@can('manage rentals')<a href="{{ route('rentals.create') }}" class="btn-primary">Abrir alquiler</a>@endcan</x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_180px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Código, cliente o placa">
        <select name="status" class="form-input"><option value="">Todos</option><option value="open" @selected(request('status') === 'open')>Abiertos</option><option value="closed" @selected(request('status') === 'closed')>Cerrados</option></select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="table-shell">
        @if ($rentals->isEmpty())
            <x-empty-state title="No hay alquileres" message="Abre un alquiler directamente o conviértelo desde una reserva." action="Abrir alquiler" :href="route('rentals.create')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Alquiler</th><th>Cliente</th><th>Vehículo</th><th>Período</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($rentals as $rental)
                            <tr>
                                <td class="font-bold text-slate-900 dark:text-white">{{ $rental->code }}</td><td>{{ $rental->customer->full_name }}</td><td>{{ $rental->vehicle->display_name }}</td>
                                <td><p>{{ $rental->start_at->format('d/m/Y h:i A') }}</p><p class="mt-1 text-xs {{ $rental->status === 'open' && $rental->expected_return_at->isPast() ? 'text-red-500 font-bold' : 'text-slate-500' }}">Retorno: {{ $rental->expected_return_at->format('d/m/Y h:i A') }}</p></td>
                                <td>RD$ {{ number_format((float) $rental->total, 2) }}</td><td><x-status-badge :status="$rental->status" /></td><td><a href="{{ route('rentals.show', $rental) }}" class="font-bold text-blue-600">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $rentals->links() }}</div>
        @endif
    </div>
</x-app-layout>
