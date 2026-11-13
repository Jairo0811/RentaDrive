@php
    $statusLabels = ['available' => 'Disponible', 'reserved' => 'Reservado', 'rented' => 'Alquilado', 'maintenance' => 'Mantenimiento', 'inactive' => 'Inactivo'];
    $transmissionLabels = ['automatic' => 'Automática', 'manual' => 'Manual'];
    $fuelLabels = ['gasoline' => 'Gasolina', 'diesel' => 'Diésel', 'hybrid' => 'Híbrido', 'electric' => 'Eléctrico'];
    $totalVehicles = $statusCounts->sum();
    $sortUrl = fn (string $column) => request()->fullUrlWithQuery([
        'sort' => $column,
        'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        'page' => null,
    ]);
    $sortMark = fn (string $column) => $sort === $column ? ($direction === 'asc' ? '▲' : '▼') : '';
    $ariaSort = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div><p class="text-lg font-black text-white">Flota</p><p class="text-xs text-slate-400">Inventario y disponibilidad</p></div>
    </x-slot>

    <x-page-header title="Flota de vehículos" subtitle="Controla unidades, tarifas, kilometraje y estado operativo.">
        <x-slot name="actions">
            @can('manage vehicles')<a href="{{ route('vehicles.create') }}" class="btn-primary">Nuevo vehículo</a>@endcan
            <a href="{{ route('fleet.catalogs') }}" class="btn-secondary">Catálogos</a>
        </x-slot>
    </x-page-header>

    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filtrar por estado">
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => null]) }}" class="chip {{ request()->filled('status') ? '' : 'chip-active' }}" @if (! request()->filled('status')) aria-current="true" @endif>
            Todos <span class="chip-count">{{ number_format($totalVehicles) }}</span>
        </a>
        @foreach ($statusLabels as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $value, 'page' => null]) }}" class="chip {{ request('status') === $value ? 'chip-active' : '' }}" @if (request('status') === $value) aria-current="true" @endif>
                {{ $label }} <span class="chip-count">{{ number_format($statusCounts[$value] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_190px_auto]">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $direction }}">
        <input type="hidden" name="view" value="{{ $viewMode }}">
        <label class="sr-only" for="fleet-search">Buscar vehículo</label>
        <input id="fleet-search" type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Código, placa, VIN, marca o modelo">
        <label class="sr-only" for="fleet-category">Categoría</label>
        <select id="fleet-category" name="category" class="form-input">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach
        </select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="mb-3 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-400">{{ number_format($vehicles->total()) }} {{ $vehicles->total() === 1 ? 'vehículo' : 'vehículos' }}</p>
        <div class="flex gap-2" role="group" aria-label="Tipo de vista">
            <a href="{{ request()->fullUrlWithQuery(['view' => 'table', 'page' => null]) }}" class="chip {{ $viewMode === 'table' ? 'chip-active' : '' }}" @if ($viewMode === 'table') aria-current="true" @endif>Tabla</a>
            <a href="{{ request()->fullUrlWithQuery(['view' => 'cards', 'page' => null]) }}" class="chip {{ $viewMode === 'cards' ? 'chip-active' : '' }}" @if ($viewMode === 'cards') aria-current="true" @endif>Tarjetas</a>
        </div>
    </div>

    @if ($vehicles->isEmpty())
        <div class="table-shell">
            <x-empty-state title="No hay vehículos" message="Ningún vehículo coincide con los filtros actuales." action="Nuevo vehículo" :href="route('vehicles.create')" ability="manage vehicles" />
        </div>
    @elseif ($viewMode === 'cards')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($vehicles as $vehicle)
                <a href="{{ route('vehicles.show', $vehicle) }}" class="panel focus-ring block p-5 transition hover:border-slate-600">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-black text-white">{{ $vehicle->model->display_name }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $vehicle->code }} · {{ $vehicle->plate }} · {{ $vehicle->color }}</p>
                        </div>
                        <x-status-badge :status="$vehicle->status" />
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div><dt class="text-xs text-slate-400">Categoría</dt><dd class="font-semibold text-slate-100">{{ $vehicle->category->name }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Tarifa diaria</dt><dd class="font-semibold text-slate-100">RD$ {{ number_format($vehicle->effective_daily_rate, 2) }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Transmisión</dt><dd class="text-slate-200">{{ $transmissionLabels[$vehicle->transmission] ?? $vehicle->transmission }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Combustible</dt><dd class="text-slate-200">{{ $fuelLabels[$vehicle->fuel_type] ?? $vehicle->fuel_type }}</dd></div>
                        <div><dt class="text-xs text-slate-400">Kilometraje</dt><dd class="text-slate-200">{{ number_format($vehicle->mileage) }} km</dd></div>
                        <div><dt class="text-xs text-slate-400">Asientos</dt><dd class="text-slate-200">{{ $vehicle->seats }}</dd></div>
                    </dl>
                </a>
            @endforeach
        </div>
        <div class="mt-5">{{ $vehicles->links() }}</div>
    @else
        <div class="table-shell">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th aria-sort="{{ $ariaSort('code') }}"><a href="{{ $sortUrl('code') }}" class="sort-link">Unidad {{ $sortMark('code') }}</a></th>
                            <th>Categoría</th>
                            <th>Transmisión / Combustible</th>
                            <th>Tarifa diaria</th>
                            <th aria-sort="{{ $ariaSort('mileage') }}"><a href="{{ $sortUrl('mileage') }}" class="sort-link">Kilometraje {{ $sortMark('mileage') }}</a></th>
                            <th aria-sort="{{ $ariaSort('status') }}"><a href="{{ $sortUrl('status') }}" class="sort-link">Estado {{ $sortMark('status') }}</a></th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vehicles as $vehicle)
                            <tr>
                                <td><p class="font-bold text-white">{{ $vehicle->model->display_name }}</p><p class="mt-1 text-xs text-slate-400">{{ $vehicle->code }} · {{ $vehicle->plate }} · {{ $vehicle->color }}</p></td>
                                <td>{{ $vehicle->category->name }}</td>
                                <td>{{ $transmissionLabels[$vehicle->transmission] ?? $vehicle->transmission }} · {{ $fuelLabels[$vehicle->fuel_type] ?? $vehicle->fuel_type }}</td>
                                <td>RD$ {{ number_format($vehicle->effective_daily_rate, 2) }}</td>
                                <td>{{ number_format($vehicle->mileage) }} km</td>
                                <td><x-status-badge :status="$vehicle->status" /></td>
                                <td class="text-right"><a href="{{ route('vehicles.show', $vehicle) }}" class="link-action" aria-label="Ver {{ $vehicle->plate }}">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-800 px-5 py-4">{{ $vehicles->links() }}</div>
        </div>
    @endif
</x-app-layout>
