<x-app-layout>
    <x-slot name="header">
        <div><p class="text-lg font-black text-slate-950 dark:text-white">Flota</p><p class="text-xs text-slate-500">Inventario y disponibilidad</p></div>
    </x-slot>

    <x-page-header title="Flota de vehículos" subtitle="Controla unidades, tarifas, kilometraje y estado operativo.">
        <x-slot name="actions">
            @can('manage vehicles')<a href="{{ route('vehicles.create') }}" class="btn-primary">Nuevo vehículo</a>@endcan
            <a href="{{ route('fleet.catalogs') }}" class="btn-secondary">Catálogos</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_170px_190px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Código, placa, VIN, marca o modelo">
        <select name="status" class="form-input">
            <option value="">Todos los estados</option>
            @foreach (['available' => 'Disponible', 'reserved' => 'Reservado', 'rented' => 'Alquilado', 'maintenance' => 'Mantenimiento', 'inactive' => 'Inactivo'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category" class="form-input">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach
        </select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="table-shell">
        @if ($vehicles->isEmpty())
            <x-empty-state title="No hay vehículos" message="Registra la primera unidad de la flota." action="Nuevo vehículo" :href="route('vehicles.create')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Unidad</th><th>Categoría</th><th>Tarifa diaria</th><th>Kilometraje</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($vehicles as $vehicle)
                            <tr>
                                <td><p class="font-bold text-slate-900 dark:text-white">{{ $vehicle->model->display_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $vehicle->code }} · {{ $vehicle->plate }} · {{ $vehicle->color }}</p></td>
                                <td>{{ $vehicle->category->name }}</td>
                                <td>RD$ {{ number_format($vehicle->effective_daily_rate, 2) }}</td>
                                <td>{{ number_format($vehicle->mileage) }} km</td>
                                <td><x-status-badge :status="$vehicle->status" /></td>
                                <td class="text-right"><a href="{{ route('vehicles.show', $vehicle) }}" class="font-bold text-blue-600">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $vehicles->links() }}</div>
        @endif
    </div>
</x-app-layout>
