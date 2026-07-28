<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Dashboard</p>
            <p class="text-xs text-slate-500">Fundación técnica de RentaDrive</p>
        </div>
    </x-slot>

    <section class="overflow-hidden rounded-2xl bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10 sm:p-8">
        <div class="relative">
            <div class="absolute -right-20 -top-28 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="relative max-w-3xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-bold text-emerald-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Fase 1 activa
                </span>
                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">La base de RentaDrive está preparada.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400 sm:text-base">
                    Autenticación, control de acceso, navegación responsive y modo oscuro listos para recibir los módulos de flota y operaciones.
                </p>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen de la fundación">
        @php
            $cards = [
                ['label' => 'Usuarios', 'value' => $summary['users'], 'note' => 'Activos en el entorno'],
                ['label' => 'Roles', 'value' => $summary['roles'], 'note' => 'Perfiles operativos'],
                ['label' => 'Permisos', 'value' => $summary['permissions'], 'note' => 'Reglas centralizadas'],
                ['label' => 'Laravel', 'value' => $summary['laravel'], 'note' => 'Versión del framework'],
            ];
        @endphp

        @foreach ($cards as $card)
            <article class="panel p-5">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <section class="panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
                <h2 class="font-bold text-slate-950 dark:text-white">Estado de la fundación</h2>
                <p class="mt-1 text-sm text-slate-500">Componentes incluidos en esta entrega.</p>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($foundation as $item)
                    <div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                                </svg>
                            </span>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $item['label'] }}</span>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                            {{ $item['status'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="panel p-5 sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Próximo hito</p>
            <h2 class="mt-3 text-xl font-black text-slate-950 dark:text-white">Catálogos y flota</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                Marcas, modelos, categorías, vehículos, fotografías y control de estados.
            </p>
            <ol class="mt-5 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                @foreach (['Migraciones y modelos', 'CRUD de catálogos', 'Gestión de vehículos', 'Historiales y fotografías'] as $step)
                    <li class="flex items-center gap-3">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        {{ $step }}
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
</x-app-layout>
