<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Flota</p>
            <p class="text-xs text-slate-500">Inventario, disponibilidad y tarifas</p>
        </div>
    </x-slot>

    <x-page-header title="Flota de vehículos" subtitle="Controla unidades, tarifas, kilometraje y estado operativo.">
        <x-slot name="actions">
            @can('manage vehicles')
                <a href="{{ route('vehicles.create') }}" class="btn-primary">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Nuevo vehículo
                </a>
            @endcan
            <a href="{{ route('fleet.schedule') }}" class="btn-secondary">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                Calendario
            </a>
            <a href="{{ route('fleet.catalogs') }}" class="btn-secondary">Catálogos</a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-6 grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-[1fr_170px_190px_220px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Código, placa, VIN, marca o modelo">

        <select name="status" class="form-input">
            <option value="">Todos los estados</option>
            @foreach (['available' => 'Disponible', 'reserved' => 'Reservado', 'rented' => 'Alquilado', 'maintenance' => 'Mantenimiento', 'inactive' => 'Inactivo'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="category" class="form-input">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="branch" class="form-input">
            <option value="">Todas las sucursales</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) request('branch') === (string) $branch->id)>
                    {{ $branch->name }}{{ $branch->city ? ' · '.$branch->city : '' }}
                </option>
            @endforeach
        </select>

        <button class="btn-secondary">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>
            Filtrar
        </button>
    </form>

    @if ($vehicles->isEmpty())
        <div class="panel">
            <x-empty-state title="No hay vehículos" message="Registra la primera unidad de la flota." action="Nuevo vehículo" :href="route('vehicles.create')" />
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($vehicles as $vehicle)
                <article class="panel overflow-hidden">
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="group block">
                        <div class="relative h-52 overflow-hidden bg-gradient-to-br from-slate-950 via-[#071a38] to-blue-950">
                            @if ($vehicle->photo_url)
                                <img
                                    src="{{ $vehicle->photo_url }}"
                                    alt="{{ $vehicle->model->display_name }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent" aria-hidden="true"></div>
                            @else
                                <div class="grid h-full place-items-center">
                                    <i class="fa-solid fa-car-side text-7xl text-blue-200/80" aria-hidden="true"></i>
                                </div>
                            @endif

                            <div class="absolute left-4 top-4">
                                <x-status-badge :status="$vehicle->status" />
                            </div>

                            <span class="absolute bottom-4 right-4 rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-black text-white backdrop-blur">
                                {{ $vehicle->plate }}
                            </span>
                        </div>
                    </a>

                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[.15em] text-blue-600 dark:text-blue-400">{{ $vehicle->category->name }}</p>
                                <h2 class="mt-1 truncate text-xl font-black text-slate-950 dark:text-white">{{ $vehicle->model->display_name }}</h2>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $vehicle->code }} · {{ $vehicle->branch?->name ?? 'Sin sucursal' }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-lg font-black text-slate-950 dark:text-white">{{ $currency }} {{ number_format($vehicle->effective_daily_rate, 2) }}</p>
                                <p class="text-xs text-slate-500">por día</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-xl bg-slate-50 px-2 py-3 dark:bg-slate-800/60">
                                <i class="fa-solid fa-gauge-high text-slate-400" aria-hidden="true"></i>
                                <p class="mt-1 font-bold">{{ number_format($vehicle->mileage) }} km</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-2 py-3 dark:bg-slate-800/60">
                                <i class="fa-solid fa-users text-slate-400" aria-hidden="true"></i>
                                <p class="mt-1 font-bold">{{ $vehicle->seats }} asientos</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-2 py-3 dark:bg-slate-800/60">
                                <i class="fa-solid fa-gears text-slate-400" aria-hidden="true"></i>
                                <p class="mt-1 font-bold">{{ $vehicle->transmission === 'automatic' ? 'Auto' : 'Manual' }}</p>
                            </div>
                        </div>

                        @if ($vehicle->next_maintenance_at)
                            @php($remaining = $vehicle->next_maintenance_at - $vehicle->mileage)
                            <div class="mt-4 rounded-xl {{ $remaining <= 1000 ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' : 'bg-slate-50 text-slate-600 dark:bg-slate-800/60 dark:text-slate-300' }} px-3 py-2.5 text-xs">
                                <i class="fa-solid fa-screwdriver-wrench mr-1" aria-hidden="true"></i>
                                @if ($remaining <= 0)
                                    Mantenimiento vencido por {{ number_format(abs($remaining)) }} km
                                @else
                                    Próximo mantenimiento en {{ number_format($remaining) }} km
                                @endif
                            </div>
                        @endif

                        <div class="mt-5 flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
                            <span class="text-xs text-slate-500">{{ $vehicle->rentals_count }} alquileres · {{ $vehicle->maintenances_count }} mantenimientos</span>
                            <a href="{{ route('vehicles.show', $vehicle) }}" class="font-bold text-blue-600 dark:text-blue-400">Ver ficha</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6 panel px-5 py-4">
            {{ $vehicles->links() }}
        </div>
    @endif
</x-app-layout>
