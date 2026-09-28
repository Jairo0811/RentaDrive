<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Calendario de flota</p>
            <p class="text-xs text-slate-500">Disponibilidad por vehículo</p>
        </div>
    </x-slot>

    <x-page-header title="Timeline operativo" subtitle="Visualiza reservas, alquileres y mantenimientos de los próximos 14 días.">
        <x-slot name="actions">
            <a href="{{ route('vehicles.index') }}" class="btn-secondary">
                <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                Ver flota
            </a>
        </x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[210px_260px_auto_1fr]">
        <div>
            <label for="start" class="form-label">Inicio</label>
            <input id="start" name="start" type="date" class="form-input" value="{{ $start->format('Y-m-d') }}">
        </div>

        <div>
            <label for="branch" class="form-label">Sucursal</label>
            <select id="branch" name="branch" class="form-input">
                <option value="">Todas las sucursales</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                        {{ $branch->name }}{{ $branch->city ? ' · '.$branch->city : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end">
            <button class="btn-secondary">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Aplicar
            </button>
        </div>

        <div class="flex items-end justify-start gap-2 sm:justify-end">
            <a href="{{ route('fleet.schedule', ['start' => $start->subDays(14)->format('Y-m-d'), 'branch' => $branchId]) }}" class="btn-secondary" aria-label="Ver 14 días anteriores">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </a>
            <a href="{{ route('fleet.schedule', ['start' => now()->format('Y-m-d'), 'branch' => $branchId]) }}" class="btn-secondary">Hoy</a>
            <a href="{{ route('fleet.schedule', ['start' => $start->addDays(14)->format('Y-m-d'), 'branch' => $branchId]) }}" class="btn-secondary" aria-label="Ver próximos 14 días">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
    </form>

    <div class="mb-4 flex flex-wrap gap-2 text-xs font-bold">
        <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Disponible</span>
        <span class="rounded-full bg-blue-100 px-3 py-1.5 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">Reservado</span>
        <span class="rounded-full bg-violet-100 px-3 py-1.5 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">Alquilado</span>
        <span class="rounded-full bg-amber-100 px-3 py-1.5 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">Mantenimiento</span>
        <span class="rounded-full bg-slate-200 px-3 py-1.5 text-slate-600 dark:bg-slate-800 dark:text-slate-300">Inactivo</span>
    </div>

    <section class="panel overflow-hidden">
        @if ($rows->isEmpty())
            <x-empty-state title="Sin vehículos" message="No hay unidades para los filtros seleccionados." />
        @else
            <div class="overflow-x-auto">
                <div class="min-w-[1320px]">
                    <div class="grid grid-cols-[250px_repeat(14,minmax(72px,1fr))] border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/50">
                        <div class="sticky left-0 z-20 bg-slate-50 px-4 py-3 text-xs font-black uppercase tracking-[.14em] text-slate-500 dark:bg-slate-950">
                            Vehículo
                        </div>

                        @foreach ($days as $day)
                            <div class="border-l border-slate-200 px-2 py-3 text-center dark:border-slate-800 {{ $day->isToday() ? 'bg-blue-50 dark:bg-blue-950/30' : '' }}">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ ucfirst($day->translatedFormat('D')) }}</p>
                                <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-200">{{ $day->format('d') }}</p>
                                <p class="text-[10px] text-slate-400">{{ ucfirst($day->translatedFormat('M')) }}</p>
                            </div>
                        @endforeach
                    </div>

                    @foreach ($rows as $row)
                        <div class="grid grid-cols-[250px_repeat(14,minmax(72px,1fr))] border-b border-slate-100 last:border-0 dark:border-slate-800">
                            <a href="{{ route('vehicles.show', $row['vehicle']) }}" class="sticky left-0 z-10 flex items-center gap-3 bg-white px-4 py-3 transition hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800">
                                @if ($row['vehicle']->photo_url)
                                    <img src="{{ $row['vehicle']->photo_url }}" alt="" class="h-11 w-16 rounded-lg object-cover" aria-hidden="true">
                                @else
                                    <span class="grid h-11 w-16 place-items-center rounded-lg bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-300">
                                        <i class="fa-solid fa-car-side text-xl" aria-hidden="true"></i>
                                    </span>
                                @endif

                                <span class="min-w-0">
                                    <strong class="block truncate text-sm text-slate-900 dark:text-white">{{ $row['vehicle']->model->display_name }}</strong>
                                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $row['vehicle']->plate }} · {{ $row['vehicle']->branch?->name ?? 'Sin sucursal' }}</span>
                                </span>
                            </a>

                            @foreach ($row['cells'] as $cell)
                                <div class="border-l border-slate-100 p-1.5 dark:border-slate-800">
                                    <a
                                        href="{{ $cell['url'] }}"
                                        class="focus-ring flex min-h-14 items-center justify-center rounded-lg px-1.5 py-2 text-center text-[10px] font-black leading-4 transition
                                            {{ $cell['status'] === 'rented' ? 'bg-violet-100 text-violet-700 hover:bg-violet-200 dark:bg-violet-950/45 dark:text-violet-300 dark:hover:bg-violet-950/70' : '' }}
                                            {{ $cell['status'] === 'reserved' ? 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-950/45 dark:text-blue-300 dark:hover:bg-blue-950/70' : '' }}
                                            {{ $cell['status'] === 'maintenance' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-950/45 dark:text-amber-300 dark:hover:bg-amber-950/70' : '' }}
                                            {{ $cell['status'] === 'inactive' ? 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400' : '' }}
                                            {{ $cell['status'] === 'available' ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:hover:bg-emerald-950/40' : '' }}"
                                        title="{{ $cell['label'] }}"
                                    >
                                        <span class="line-clamp-2">{{ $cell['label'] }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-app-layout>
