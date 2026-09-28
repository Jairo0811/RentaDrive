<x-public-layout
    :title="$company->name.' · Reservas'"
    :home-url="route('public.booking.show', ['company' => $company->slug])"
    :badge="$company->name"
>
    <section class="border-b border-slate-200 bg-gradient-to-br from-blue-50 via-white to-slate-100 dark:border-slate-800 dark:from-blue-950/30 dark:via-[#030914] dark:to-slate-950">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Reserva directa</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ $company->name }}</h1>
                <p class="mt-4 text-lg leading-8 text-slate-600 dark:text-slate-400">
                    Consulta vehículos disponibles, compara la tarifa y genera una reserva pendiente en minutos.
                </p>
            </div>

            <form method="GET" action="{{ route('public.booking.search', ['company' => $company->slug]) }}" class="panel mt-8 grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <label for="branch_id" class="form-label">Sucursal</label>
                    <select id="branch_id" name="branch_id" class="form-input" required>
                        <option value="">Selecciona</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $search['branch_id'] ?? '') === (string) $branch->id)>
                                {{ $branch->name }}{{ $branch->city ? ' · '.$branch->city : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_at" class="form-label">Recogida</label>
                    <input
                        id="start_at"
                        name="start_at"
                        type="datetime-local"
                        class="form-input"
                        min="{{ now($company->timezone)->format('Y-m-d\TH:i') }}"
                        value="{{ old('start_at', $search['start_at'] ?? '') }}"
                        required
                    >
                </div>

                <div>
                    <label for="end_at" class="form-label">Devolución</label>
                    <input
                        id="end_at"
                        name="end_at"
                        type="datetime-local"
                        class="form-input"
                        value="{{ old('end_at', $search['end_at'] ?? '') }}"
                        required
                    >
                </div>

                <div>
                    <label for="category_id" class="form-label">Categoría</label>
                    <select id="category_id" name="category_id" class="form-input">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $search['category_id'] ?? '') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full py-3">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        Buscar
                    </button>
                </div>

                @if ($errors->any())
                    <div class="md:col-span-2 xl:col-span-5" role="alert">
                        <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                            Revisa las fechas y la sucursal antes de continuar.
                        </p>
                    </div>
                @endif
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
        @if (! $searchPerformed)
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'fa-calendar-days', 'title' => '1. Elige fechas', 'text' => 'Selecciona dónde y cuándo necesitas el vehículo.'],
                    ['icon' => 'fa-car', 'title' => '2. Compara opciones', 'text' => 'Mostramos solamente vehículos disponibles para ese período.'],
                    ['icon' => 'fa-circle-check', 'title' => '3. Reserva', 'text' => 'Confirma tus datos y recibe un código de reserva pendiente.'],
                ] as $step)
                    <article class="panel p-6">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300">
                            <i class="fa-solid {{ $step['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <h2 class="mt-5 text-lg font-black">{{ $step['title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $step['text'] }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.16em] text-blue-600 dark:text-blue-400">Disponibilidad</p>
                    <h2 class="mt-2 text-3xl font-black">{{ $vehicles->count() }} {{ $vehicles->count() === 1 ? 'vehículo disponible' : 'vehículos disponibles' }}</h2>
                </div>
                <p class="text-sm text-slate-500">{{ $days }} {{ $days === 1 ? 'día de alquiler' : 'días de alquiler' }}</p>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                @forelse ($vehicles as $vehicle)
                    @php
                        $estimated = round($vehicle->effective_daily_rate * $days, 2);
                    @endphp
                    <article class="panel overflow-hidden">
                        <div class="relative grid h-48 place-items-center overflow-hidden bg-gradient-to-br from-slate-950 via-[#071a38] to-blue-950 text-white">
                            <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 20% 20%, #168ce8 0, transparent 34%), radial-gradient(circle at 80% 80%, #e2232e 0, transparent 28%);"></div>
                            <i class="fa-solid fa-car-side relative text-7xl text-blue-200 drop-shadow-2xl" aria-hidden="true"></i>
                            <span class="absolute left-4 top-4 rounded-full bg-emerald-400/15 px-3 py-1.5 text-xs font-black text-emerald-200 ring-1 ring-inset ring-emerald-300/20">
                                Disponible
                            </span>
                        </div>

                        <div class="p-6">
                            <p class="text-xs font-black uppercase tracking-[.16em] text-blue-600 dark:text-blue-400">{{ $vehicle->category->name }}</p>
                            <h3 class="mt-2 text-xl font-black">{{ $vehicle->model->display_name }}</h3>

                            <div class="mt-4 grid grid-cols-2 gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <span><i class="fa-solid fa-gears mr-2 text-slate-400" aria-hidden="true"></i>{{ ucfirst($vehicle->transmission) }}</span>
                                <span><i class="fa-solid fa-users mr-2 text-slate-400" aria-hidden="true"></i>{{ $vehicle->seats }} pasajeros</span>
                                <span><i class="fa-solid fa-gas-pump mr-2 text-slate-400" aria-hidden="true"></i>{{ ucfirst($vehicle->fuel_type) }}</span>
                                <span><i class="fa-solid fa-palette mr-2 text-slate-400" aria-hidden="true"></i>{{ $vehicle->color }}</span>
                            </div>

                            <div class="mt-6 flex items-end justify-between gap-4 border-t border-slate-200 pt-5 dark:border-slate-800">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Desde</p>
                                    <p class="mt-1 text-2xl font-black">{{ $company->currency }} {{ number_format($vehicle->effective_daily_rate, 2) }}</p>
                                    <p class="text-xs text-slate-500">por día</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Estimado</p>
                                    <p class="mt-1 text-lg font-black text-blue-600 dark:text-blue-400">{{ $company->currency }} {{ number_format($estimated, 2) }}</p>
                                </div>
                            </div>

                            <a
                                href="{{ route('public.booking.create', [
                                    'company' => $company->slug,
                                    'branch_id' => $search['branch_id'],
                                    'vehicle_id' => $vehicle->id,
                                    'start_at' => $search['start_at'],
                                    'end_at' => $search['end_at'],
                                ]) }}"
                                class="btn-primary mt-5 w-full py-3"
                            >
                                Seleccionar vehículo
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="panel p-10 text-center lg:col-span-2 xl:col-span-3">
                        <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-300">
                            <i class="fa-solid fa-calendar-xmark text-2xl" aria-hidden="true"></i>
                        </span>
                        <h2 class="mt-5 text-xl font-black">No encontramos vehículos libres</h2>
                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                            Prueba otra sucursal, categoría o rango de fechas. RentaDrive excluye automáticamente reservas, alquileres activos y unidades en mantenimiento.
                        </p>
                    </div>
                @endforelse
            </div>
        @endif
    </section>
</x-public-layout>
