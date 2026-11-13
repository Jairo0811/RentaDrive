<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Dashboard</p>
            <p class="text-xs text-slate-400">Panorama operativo de RentaDrive</p>
        </div>
    </x-slot>

    @php
        $cards = [
            ['label' => 'Reservas hoy', 'value' => $metrics['today_reservations'], 'note' => 'Entregas programadas', 'tone' => 'blue', 'icon' => 'calendar', 'url' => route('reservations.index'), 'perm' => 'view reservations'],
            ['label' => 'Devoluciones hoy', 'value' => $metrics['returns_today'], 'note' => 'Alquileres que vencen hoy', 'tone' => 'cyan', 'icon' => 'car', 'url' => route('rentals.index', ['status' => 'open']), 'perm' => 'view rentals'],
            ['label' => 'Devoluciones atrasadas', 'value' => $metrics['overdue_returns'], 'note' => 'Pasaron la fecha prevista', 'tone' => 'rose', 'icon' => 'car', 'url' => route('rentals.index', ['status' => 'open']), 'perm' => 'view rentals'],
            ['label' => 'Por confirmar', 'value' => $metrics['pending_reservations'], 'note' => 'Reservas pendientes', 'tone' => 'amber', 'icon' => 'calendar', 'url' => route('reservations.index', ['status' => 'pending']), 'perm' => 'view reservations'],
            ['label' => 'Disponibles', 'value' => $metrics['available_vehicles'], 'note' => 'Vehículos listos', 'tone' => 'emerald', 'icon' => 'car', 'url' => route('vehicles.index', ['status' => 'available']), 'perm' => 'view vehicles'],
            ['label' => 'Alquileres', 'value' => $metrics['open_rentals'], 'note' => 'Actualmente abiertos', 'tone' => 'violet', 'icon' => 'car', 'url' => route('rentals.index', ['status' => 'open']), 'perm' => 'view rentals'],
            ['label' => 'En mantenimiento', 'value' => $metrics['maintenance_vehicles'], 'note' => 'Fuera de servicio', 'tone' => 'amber', 'icon' => 'car', 'url' => route('vehicles.index', ['status' => 'maintenance']), 'perm' => 'view vehicles'],
            ['label' => 'Ocupación', 'value' => $metrics['occupancy'] === null ? '—' : $metrics['occupancy'].'%', 'note' => 'Alquilados sobre flota activa', 'tone' => 'blue', 'icon' => 'car', 'url' => route('vehicles.index', ['status' => 'rented']), 'perm' => 'view vehicles'],
            ['label' => 'Cobrado mes', 'value' => 'RD$ '.number_format((float) $metrics['month_collected'], 2), 'note' => 'Pagos recibidos', 'tone' => 'amber', 'icon' => 'cash', 'url' => route('payments.index'), 'perm' => 'view payments'],
            ['label' => 'Por cobrar', 'value' => 'RD$ '.number_format((float) $metrics['outstanding'], 2), 'note' => 'Balance pendiente', 'tone' => 'rose', 'icon' => 'wallet', 'url' => route('invoices.index'), 'perm' => 'view invoices'],
            ['label' => 'Clientes', 'value' => $metrics['active_customers'], 'note' => 'Clientes activos', 'tone' => 'cyan', 'icon' => 'users', 'url' => route('customers.index'), 'perm' => 'view customers'],
        ];

        $reservationLabelMap = ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'converted' => 'Convertida', 'cancelled' => 'Cancelada'];
        $reservationLabels = $reservationStatus->keys()->map(fn ($status) => $reservationLabelMap[$status] ?? ucfirst((string) $status))->values()->all();
        $reservationValues = $reservationStatus->values()->map(fn ($total) => (int) $total)->all();
        $categoryLabels = $topCategories->keys()->values()->all();
        $categoryValues = $topCategories->values()->all();

        $incomeLabels = $monthlyIncome->pluck('label')->values()->all();
        $incomeValues = $monthlyIncome->pluck('value')->values()->all();
        $fleetLabels = ['Disponible', 'Reservado', 'Alquilado', 'Mantenimiento', 'Inactivo'];
        $fleetValues = [
            (int) ($fleetStatus['available'] ?? 0),
            (int) ($fleetStatus['reserved'] ?? 0),
            (int) ($fleetStatus['rented'] ?? 0),
            (int) ($fleetStatus['maintenance'] ?? 0),
            (int) ($fleetStatus['inactive'] ?? 0),
        ];
    @endphp

    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#031128] via-[#072654] to-[#0568f5] p-6 text-white shadow-xl shadow-blue-950/20 sm:p-8">
        <div class="relative max-w-3xl">
            <div class="absolute -right-20 -top-28 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>
            <span class="relative inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-3 py-1 text-xs font-bold text-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Sistema operativo
            </span>
            <h1 class="relative mt-5 text-3xl font-black tracking-tight sm:text-4xl">Gestiona tu flota. Impulsa tu negocio.</h1>
            <p class="relative mt-3 max-w-2xl text-sm leading-relaxed text-blue-100 sm:text-base">
                Reservas, alquileres, inspecciones, facturación y pagos conectados en un solo flujo.
            </p>
            <div class="relative mt-5 flex flex-wrap gap-2">
                @can('manage reservations')
                    <a href="{{ route('reservations.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#0568f5] transition hover:bg-blue-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva reserva
                    </a>
                @endcan
                @can('manage rentals')
                    <a href="{{ route('rentals.create') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17h14M6 17l1-6h10l1 6M8 11l1-3h6l1 3M8 17v2m8-2v2" />
                        </svg>
                        Abrir alquiler
                    </a>
                @endcan
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen operativo">
        @foreach ($cards as $card)
            <article class="panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                    </div>
                    <span class="metric-icon metric-icon-{{ $card['tone'] }}">
                        @switch($card['icon'])
                            @case('calendar')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z" /></svg>
                                @break
                            @case('cash')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m3-9.5c0-1.38-1.34-2.5-3-2.5s-3 1.12-3 2.5 1.34 2.5 3 2.5 3 1.12 3 2.5-1.34 2.5-3 2.5-3-1.12-3-2.5" /></svg>
                                @break
                            @case('wallet')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h16a2 2 0 0 1 2 2v10H5a2 2 0 0 1-2-2V7Zm0 0 2-3h12l2 3m-4 5h6" /></svg>
                                @break
                            @case('users')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                @break
                            @default
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17h14M6 17l1-6h10l1 6M8 11l1-3h6l1 3M8 17v2m8-2v2" /></svg>
                        @endswitch
                    </span>
                </div>
                <p class="mt-2 text-xs text-slate-400">{{ $card['note'] }}</p>
                @can($card['perm'])
                    <a href="{{ $card['url'] }}" class="link-action mt-3 inline-block text-xs" aria-label="Ver detalle de {{ $card['label'] }}">Ver detalle →</a>
                @endcan
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
        <article class="panel p-5 sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-400 dark:text-blue-400">Rendimiento financiero</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950 dark:text-white">Ingresos de los últimos 6 meses</h2>
                </div>
                <span class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-400 dark:border-slate-700">RD$</span>
            </div>
            <div class="mt-6 h-72">
                <canvas id="incomeChart" data-labels='@json($incomeLabels)' data-values='@json($incomeValues)'></canvas>
            </div>
        </article>

        <article class="panel p-5 sm:p-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-400 dark:text-blue-400">Estado de flota</p>
                <h2 class="mt-2 text-xl font-black text-slate-950 dark:text-white">Distribución operativa</h2>
            </div>
            <div class="mt-6 h-72">
                <canvas id="fleetChart" data-labels='@json($fleetLabels)' data-values='@json($fleetValues)'></canvas>
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[.85fr_1.15fr]" aria-label="Alertas y análisis">
        <article class="panel p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-400">Alertas operativas</p>
            <h2 class="mt-2 text-xl font-black text-white">Requieren atención</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($overdueRentals as $rental)
                    <li class="rounded-xl border border-red-900/60 bg-red-950/30 p-3 text-sm">
                        <a href="{{ route('rentals.show', $rental) }}" class="link-action">{{ $rental->code }}</a>
                        <span class="text-slate-200"> · {{ $rental->customer->full_name }}</span>
                        <p class="mt-1 text-xs text-slate-400">{{ $rental->vehicle->display_name }} — debía regresar el {{ $rental->expected_return_at->format('d/m/Y h:i A') }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No hay devoluciones atrasadas.</li>
                @endforelse
                @if ($metrics['pending_reservations'] > 0)
                    <li class="rounded-xl border border-amber-900/60 bg-amber-950/30 p-3 text-sm text-slate-200">
                        {{ $metrics['pending_reservations'] }} {{ $metrics['pending_reservations'] === 1 ? 'reserva pendiente' : 'reservas pendientes' }} de confirmación.
                        <a href="{{ route('reservations.index', ['status' => 'pending']) }}" class="link-action ml-1">Revisar</a>
                    </li>
                @endif
            </ul>
        </article>

        <div class="grid gap-6 sm:grid-cols-2">
            <article class="panel p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-400">Reservas del mes</p>
                <h2 class="mt-2 text-lg font-black text-white">Por estado</h2>
                @if (array_sum($reservationValues) > 0)
                    <div class="mt-4 h-56"><canvas id="reservationStatusChart" role="img" aria-label="Reservas del mes por estado" data-labels='@json($reservationLabels)' data-values='@json($reservationValues)'></canvas></div>
                @else
                    <p class="mt-4 text-sm text-slate-400">Sin reservas este mes.</p>
                @endif
            </article>
            <article class="panel p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-400">Últimos 90 días</p>
                <h2 class="mt-2 text-lg font-black text-white">Categorías más alquiladas</h2>
                @if (count($categoryValues) > 0)
                    <div class="mt-4 h-56"><canvas id="categoryChart" role="img" aria-label="Categorías más alquiladas" data-labels='@json($categoryLabels)' data-values='@json($categoryValues)'></canvas></div>
                @else
                    <p class="mt-4 text-sm text-slate-400">Sin alquileres en el período.</p>
                @endif
            </article>
        </div>
    </section>

    <section class="mt-6 panel overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-slate-950 dark:text-white">Calendario de reservas</h2>
                    <p class="mt-1 text-sm text-slate-400">{{ $calendar['title'] }}</p>
                </div>
                <a href="{{ route('reservations.index') }}" class="text-sm font-bold text-blue-400 hover:text-blue-300 dark:text-blue-400">Ver todas</a>
            </div>
        </div>
        <div class="overflow-x-auto p-4 sm:p-6">
            <div class="calendar-grid min-w-[720px]">
                @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
                    <div class="calendar-weekday">{{ $weekday }}</div>
                @endforeach

                @for ($blank = 1; $blank < $calendar['firstWeekday']; $blank++)
                    <div class="calendar-day calendar-day-muted"></div>
                @endfor

                @for ($day = 1; $day <= $calendar['daysInMonth']; $day++)
                    @php
                        $events = $calendar['reservations']->where('day', $day);
                    @endphp
                    <div class="calendar-day {{ now()->day === $day ? 'calendar-day-today' : '' }}">
                        <span class="calendar-number">{{ $day }}</span>
                        <div class="mt-2 space-y-1">
                            @foreach ($events->take(2) as $event)
                                <a href="{{ $event['url'] }}" class="calendar-event" title="{{ $event['customer'] }} · {{ $event['vehicle'] }}">
                                    <span>{{ $event['time'] }}</span>
                                    <strong>{{ $event['customer'] }}</strong>
                                </a>
                            @endforeach
                            @if ($events->count() > 2)
                                <span class="text-[10px] font-bold text-blue-500">+{{ $events->count() - 2 }} más</span>
                            @endif
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <h2 class="font-bold text-slate-950 dark:text-white">Próximas reservas</h2>
                <p class="mt-1 text-sm text-slate-400">Entregas pendientes y confirmadas.</p>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($upcomingReservations as $reservation)
                    <a href="{{ route('reservations.show', $reservation) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:px-6">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $reservation->customer->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $reservation->code }} · {{ $reservation->vehicle?->display_name ?? $reservation->category->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $reservation->start_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-400">{{ $reservation->start_at->format('h:i A') }}</p>
                        </div>
                    </a>
                @empty
                    <x-empty-state title="Sin reservas próximas" message="Las nuevas reservas aparecerán aquí." />
                @endforelse
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <h2 class="font-bold text-slate-950 dark:text-white">Alquileres activos</h2>
                <p class="mt-1 text-sm text-slate-400">Unidades actualmente fuera.</p>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($activeRentals as $rental)
                    <a href="{{ route('rentals.show', $rental) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:px-6">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $rental->vehicle->display_name }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $rental->customer->full_name }} · {{ $rental->code }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Retorno</p>
                            <p class="mt-1 text-sm font-semibold {{ $rental->expected_return_at->isPast() ? 'text-red-500' : 'text-slate-700 dark:text-slate-200' }}">
                                {{ $rental->expected_return_at->format('d/m h:i A') }}
                            </p>
                        </div>
                    </a>
                @empty
                    <x-empty-state title="Sin alquileres activos" message="Cuando abras un alquiler, aparecerá en esta sección." />
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
