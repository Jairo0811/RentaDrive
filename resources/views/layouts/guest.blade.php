<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0f172a">

        <title>Acceso | {{ config('app.name', 'RentaDrive') }}</title>

        @include('layouts.partials.theme-script')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full antialiased">
        <div class="grid min-h-full bg-slate-100 dark:bg-slate-950 lg:grid-cols-[1.05fr_.95fr]">
            <section class="relative hidden overflow-hidden bg-slate-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(37,99,235,.38),transparent_35%),radial-gradient(circle_at_80%_70%,rgba(14,165,233,.18),transparent_35%)]"></div>
                <div class="absolute -right-24 top-0 h-full w-72 rotate-12 bg-blue-500/10"></div>
                <div class="absolute -right-2 top-0 h-full w-px rotate-12 bg-blue-300/30"></div>

                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-3 text-white">
                    <x-application-logo class="h-12 w-12 text-blue-400" />
                    <span>
                        <span class="block text-xl font-black tracking-tight">RentaDrive</span>
                        <span class="block text-xs font-medium uppercase tracking-[.22em] text-slate-400">Fleet management</span>
                    </span>
                </a>

                <div class="relative z-10 max-w-xl">
                    <p class="mb-4 text-sm font-bold uppercase tracking-[.3em] text-blue-400">Control operativo</p>
                    <h1 class="text-5xl font-black leading-tight text-white xl:text-6xl">
                        Gestiona tu flota.<br>
                        <span class="text-blue-400">Impulsa tu negocio.</span>
                    </h1>
                    <p class="mt-6 max-w-lg text-lg leading-relaxed text-slate-400">
                        Una plataforma centralizada para reservas, alquileres, inspecciones, facturación y control de vehículos.
                    </p>
                </div>

                <p class="relative z-10 text-sm text-slate-500">RentaDrive · Versión 1.0 en desarrollo</p>
            </section>

            <section class="relative flex min-h-full items-center justify-center px-5 py-12 sm:px-8">
                <button
                    type="button"
                    class="focus-ring absolute right-5 top-5 rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300"
                    @click="$store.theme.toggle()"
                    :aria-label="$store.theme.dark ? 'Activar modo claro' : 'Activar modo oscuro'"
                >
                    <svg x-show="! $store.theme.dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.4 6.4-.7-.7M6.3 6.3l-.7-.7m12.8 0-.7.7M6.3 17.7l-.7.7M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
                    </svg>
                    <svg x-cloak x-show="$store.theme.dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/>
                    </svg>
                </button>

                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-application-logo class="h-11 w-11 text-blue-600 dark:text-blue-400" />
                        <div>
                            <p class="text-xl font-black text-slate-950 dark:text-white">RentaDrive</p>
                            <p class="text-xs uppercase tracking-[.18em] text-slate-500">Gestión de flotas</p>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </section>
        </div>
    </body>
</html>
