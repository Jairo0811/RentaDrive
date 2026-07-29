<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Dashboard</p>
            <p class="text-xs text-slate-500">Panorama operativo de RentaDrive</p>
        </div>
    </x-slot>

    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#031128] via-[#072654] to-[#0568f5] p-6 text-white shadow-xl shadow-blue-950/20 sm:p-8">
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
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
                        <a href="{{ route('reservations.create') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#0568f5] transition hover:bg-blue-50">Nueva reserva</a>
                    @endcan
                    @can('manage rentals')
                        <a href="{{ route('rentals.create') }}" class="rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">Abrir alquiler</a>
                    @endcan
                </div>
            </div>
            <img src="{{ asset('images/rentadrive-logo-dark.png') }}" alt="RentaDrive" class="relative h-40 w-full max-w-sm object-contain drop-shadow-2xl lg:h-44">
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Resumen operativo">
        @php
            $cards = [
                ['label' => 'Disponibles', 'value' => $metrics['available_vehicles'], 'note' => 'Vehículos listos'],
                ['label' => 'Alquileres', 'value' => $metrics['open_rentals'], 'note' => 'Actualmente abiertos'],
                ['label' => 'Reservas hoy', 'value' => $metrics['today_reservations'], 'note' => 'Entregas programadas'],
                ['label' => 'Cobrado mes', 'value' => 'RD$ '.number_format((float) $metrics['month_collected'], 2), 'note' => 'Pagos recibidos'],
                ['label' => 'Por cobrar', 'value' => 'RD$ '.number_format((float) $metrics['outstanding'], 2), 'note' => 'Balance pendiente'],
                ['label' => 'Clientes', 'value' => $metrics['active_customers'], 'note' => 'Clientes activos'],
            ];
        @endphp

        @foreach ($cards as $card)
            <article class="panel p-5">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <h2 class="font-bold text-slate-950 dark:text-white">Próximas reservas</h2>
                <p class="mt-1 text-sm text-slate-500">Entregas pendientes y confirmadas.</p>
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
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <h2 class="font-bold text-slate-950 dark:text-white">Alquileres activos</h2>
                <p class="mt-1 text-sm text-slate-500">Unidades actualmente fuera.</p>
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
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Estado de flota</p>
                <h2 class="mt-2 text-xl font-black text-slate-950 dark:text-white">Distribución operativa</h2>
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
