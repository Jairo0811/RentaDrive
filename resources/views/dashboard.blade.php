<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Dashboard</p>
            <p class="text-xs text-slate-500">Panorama operativo de RentaDrive</p>
        </div>
    </x-slot>

    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#031128] via-[#072654] to-[#0568f5] p-6 text-white shadow-xl shadow-blue-950/20 sm:p-8">
        <div class="relative">
            <div class="absolute -right-20 -top-28 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="relative max-w-3xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-3 py-1 text-xs font-bold text-emerald-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Sistema operativo
                </span>
                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">Gestiona tu flota. Impulsa tu negocio.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-blue-100 sm:text-base">
                    Reservas, alquileres, inspecciones, facturación y pagos conectados en un solo flujo.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    @can('manage reservations')
                        <a href="{{ route('reservations.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#0568f5] transition hover:bg-blue-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>
                            </svg>
                            Nueva reserva
                        </a>
                    @endcan
                    @can('manage rentals')
                        <a href="{{ route('rentals.create') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17h14M7 17l1-5h8l1 5M8.5 12l1.5-4h4l1.5 4M7 17v2m10-2v2M9 17a1 1 0 1 1-2 0m10 0a1 1 0 1 1-2 0"/>
                            </svg>
                            Abrir alquiler
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Resumen operativo">
        @php
            $cards = [
                [
                    'label' => 'Disponibles',
                    'value' => $metrics['available_vehicles'],
                    'note' => 'Vehículos listos',
                    'tone' => 'emerald',
                    'icon' => 'car',
                ],
                [
                    'label' => 'Alquileres',
                    'value' => $metrics['open_rentals'],
                    'note' => 'Actualmente abiertos',
                    'tone' => 'violet',
                    'icon' => 'steering',
                ],
                [
                    'label' => 'Reservas hoy',
                    'value' => $metrics['today_reservations'],
                    'note' => 'Entregas programadas',
                    'tone' => 'blue',
                    'icon' => 'calendar',
                ],
                [
                    'label' => 'Cobrado mes',
                    'value' => 'RD$ '.number_format((float) $metrics['month_collected'], 2),
                    'note' => 'Pagos recibidos',
                    'tone' => 'amber',
                    'icon' => 'money',
                ],
                [
                    'label' => 'Por cobrar',
                    'value' => 'RD$ '.number_format((float) $metrics['outstanding'], 2),
                    'note' => 'Balance pendiente',
                    'tone' => 'rose',
                    'icon' => 'wallet',
                ],
                [
                    'label' => 'Clientes',
                    'value' => $metrics['active_customers'],
                    'note' => 'Clientes activos',
                    'tone' => 'cyan',
                    'icon' => 'users',
                ],
            ];

            $toneClasses = [
                'emerald' => 'bg-emerald-500/10 text-emerald-500 dark:bg-emerald-400/10 dark:text-emerald-300',
                'violet' => 'bg-violet-500/10 text-violet-500 dark:bg-violet-400/10 dark:text-violet-300',
                'blue' => 'bg-blue-500/10 text-blue-500 dark:bg-blue-400/10 dark:text-blue-300',
                'amber' => 'bg-amber-500/10 text-amber-500 dark:bg-amber-400/10 dark:text-amber-300',
                'rose' => 'bg-rose-500/10 text-rose-500 dark:bg-rose-400/10 dark:text-rose-300',
                'cyan' => 'bg-cyan-500/10 text-cyan-500 dark:bg-cyan-400/10 dark:text-cyan-300',
            ];
        @endphp

        @foreach ($cards as $card)
            <article class="panel p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
                    </div>

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $toneClasses[$card['tone']] }}">
                        @switch($card['icon'])
                            @case('car')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17h14M7 17l1-5h8l1 5M8.5 12l1.5-4h4l1.5 4M7 17v2m10-2v2"/>
                                </svg>
                                @break
                            @case('steering')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8" stroke-width="2"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10h14M12 12v8M8.5 10l3.5 3 3.5-3"/>
                                </svg>
                                @break
                            @case('calendar')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>
                                </svg>
                                @break
                            @case('money')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18m4-14.5c-.9-.7-2.1-1.1-3.5-1.1-2 0-3.5 1-3.5 2.5 0 4 7 1.5 7 5.5 0 1.5-1.5 2.6-3.7 2.6-1.6 0-3-.5-4-1.4"/>
                                </svg>
                                @break
                            @case('wallet')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v2H8a2 2 0 0 0 0 4h11v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9v4h-4a2 2 0 1 1 0-4h4Z"/>
                                </svg>
                                @break
                            @case('users')
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7.5-1a3 3 0 1 0 0-6m4 17v-2a4 4 0 0 0-3-3.87"/>
                                </svg>
                                @break
                        @endswitch
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-500 dark:bg-blue-400/10 dark:text-blue-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-slate-950 dark:text-white">Próximas reservas</h2>
                    <p class="mt-1 text-sm text-slate-500">Entregas pendientes y confirmadas.</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($upcomingReservations as $reservation)
                    <a href="{{ route('reservations.show', $reservation) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:px-6">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $reservation->customer->full_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $reservation->code }} · {{ $reservation->vehicle?->display_name ?? $reservation->category->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $reservation->start_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-500">{{ $reservation->start_at->format('h:i A') }}</p>
                        </div>
                    </a>
                @empty
                    <x-empty-state title="Sin reservas próximas" message="Las nuevas reservas aparecerán aquí." />
                @endforelse
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-500/10 text-violet-500 dark:bg-violet-400/10 dark:text-violet-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="8" stroke-width="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10h14M12 12v8M8.5 10l3.5 3 3.5-3"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-slate-950 dark:text-white">Alquileres activos</h2>
                    <p class="mt-1 text-sm text-slate-500">Unidades actualmente fuera.</p>
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($activeRentals as $rental)
                    <a href="{{ route('rentals.show', $rental) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:px-6">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $rental->vehicle->display_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $rental->customer->full_name }} · {{ $rental->code }}</p>
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

    <section class="mt-6 panel p-5 sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-500 dark:bg-cyan-400/10 dark:text-cyan-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 17h14M7 17l1-5h8l1 5M8.5 12l1.5-4h4l1.5 4M7 17v2m10-2v2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Estado de flota</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950 dark:text-white">Distribución operativa</h2>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                @foreach (['available', 'reserved', 'rented', 'maintenance', 'inactive'] as $status)
                    <div class="rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                        <x-status-badge :status="$status" />
                        <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white">{{ $fleetStatus[$status] ?? 0 }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-app-layout>
