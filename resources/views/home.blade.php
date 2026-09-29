<x-public-layout title="Gestión moderna para rent-a-car">
    <section class="relative overflow-hidden bg-[#030914] text-white">
        <img
            src="{{ asset('images/rentadrive-racing.jpeg') }}"
            alt=""
            class="absolute inset-0 h-full w-full object-cover opacity-35"
            aria-hidden="true"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-[#030914] via-[#04152c]/95 to-[#030914]/60" aria-hidden="true"></div>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#0568f5] via-[#25a7ff] to-[#e2232e]" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[1.05fr_.95fr] lg:items-center lg:py-28">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-400/10 px-3 py-1.5 text-xs font-black uppercase tracking-[.18em] text-blue-200">
                    <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                    Operación + reservas + flota
                </p>
                <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">
                    Tu rent-a-car, operando desde un solo lugar.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                    RentaDrive centraliza clientes, vehículos, reservas, alquileres, inspecciones, facturación, pagos y reportes en una plataforma multiempresa preparada para crecer.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="btn-primary px-6 py-3">
                        <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                        Ir al panel
                    </a>
                    <a href="#producto" class="btn-secondary border-white/15 bg-white/10 px-6 py-3 text-white hover:bg-white/15">
                        Ver capacidades
                    </a>
                </div>

                <div class="mt-10 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([
                        ['value' => 'Multiempresa', 'label' => 'SaaS'],
                        ['value' => 'PWA', 'label' => 'Móvil'],
                        ['value' => 'SQL Server', 'label' => 'Datos'],
                        ['value' => 'RD', 'label' => 'Local'],
                    ] as $item)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                            <p class="font-black">{{ $item['value'] }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $item['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative">
                <div class="rounded-[2rem] border border-white/10 bg-white/10 p-3 shadow-2xl shadow-blue-950/40 backdrop-blur">
                    <div class="rounded-[1.5rem] border border-slate-700 bg-slate-950/90 p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-blue-300">Vista operativa</p>
                                <p class="mt-2 text-2xl font-black">Flota disponible hoy</p>
                            </div>
                            <span class="rounded-xl bg-emerald-400/10 px-3 py-2 text-sm font-black text-emerald-300">73% utilización</span>
                        </div>

                        <div class="mt-6 space-y-3">
                            @foreach ([
                                ['name' => 'Toyota Corolla 2025', 'status' => 'Disponible', 'class' => 'text-emerald-300 bg-emerald-400/10'],
                                ['name' => 'Hyundai Tucson 2024', 'status' => 'Alquilado', 'class' => 'text-red-300 bg-red-400/10'],
                                ['name' => 'Kia Picanto 2025', 'status' => 'Reservado', 'class' => 'text-blue-300 bg-blue-400/10'],
                            ] as $vehicle)
                                <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-slate-800 text-blue-300">
                                            <i class="fa-solid fa-car" aria-hidden="true"></i>
                                        </span>
                                        <div>
                                            <p class="font-bold">{{ $vehicle['name'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">Sucursal principal</p>
                                        </div>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $vehicle['class'] }}">{{ $vehicle['status'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="producto" class="mx-auto max-w-7xl px-5 py-20 sm:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-black uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Producto comercial</p>
            <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Del mostrador a la devolución del vehículo.</h2>
            <p class="mt-4 text-slate-600 dark:text-slate-400">
                RentaDrive conecta la operación interna con una experiencia pública de reservas por empresa, manteniendo los datos de cada rent-a-car aislados.
            </p>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['icon' => 'fa-calendar-check', 'title' => 'Reservas', 'text' => 'Disponibilidad por fecha, categoría y sucursal con cotización estimada.'],
                ['icon' => 'fa-car-side', 'title' => 'Flota', 'text' => 'Vehículos, estados, tarifas, kilometraje, mantenimiento y trazabilidad.'],
                ['icon' => 'fa-file-signature', 'title' => 'Alquileres', 'text' => 'Contratos, inspecciones, entrega, devolución y cargos operativos.'],
                ['icon' => 'fa-chart-line', 'title' => 'Negocio', 'text' => 'Facturación, pagos, reportes, auditoría y métricas para tomar decisiones.'],
            ] as $feature)
                <article class="panel p-6">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300">
                        <i class="fa-solid {{ $feature['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-5 text-lg font-black">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $feature['text'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-100/70 dark:border-slate-800 dark:bg-slate-950/50">
        <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                @foreach ([
                    ['name' => 'Starter', 'capacity' => '25 vehículos · 5 usuarios · 2 sucursales'],
                    ['name' => 'Professional', 'capacity' => '100 vehículos · 15 usuarios · 5 sucursales'],
                    ['name' => 'Business', 'capacity' => '500 vehículos · 50 usuarios · 20 sucursales'],
                ] as $plan)
                    <article class="panel p-6">
                        <p class="text-sm font-black uppercase tracking-[.16em] text-blue-600 dark:text-blue-400">{{ $plan['name'] }}</p>
                        <p class="mt-4 text-lg font-bold">{{ $plan['capacity'] }}</p>
                        <p class="mt-2 text-sm text-slate-500">Límites aplicados desde backend por tenant.</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
