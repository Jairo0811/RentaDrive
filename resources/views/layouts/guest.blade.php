<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#071a38">

        <title>Acceso | {{ config('app.name', 'RentaDrive') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/rentadrive-mark.png') }}">

        @include('layouts.partials.theme-script')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full antialiased">
        <div class="grid min-h-full bg-slate-100 dark:bg-[#030914] lg:grid-cols-[1.08fr_.92fr]">
            <section class="login-brand-panel relative hidden overflow-hidden bg-[#030914] lg:flex lg:flex-col lg:justify-between lg:p-12">
                <img
                    src="{{ asset('images/rentadrive-racing.jpeg') }}"
                    alt="Escena automotriz de RentaDrive"
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-b from-[#030914]/35 via-[#04152c]/25 to-[#030914]/90"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-[#030914]/10 via-transparent to-[#030914]/55"></div>
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#0568f5] via-[#25a7ff] to-[#e2232e]"></div>

                <a href="{{ route('home') }}" class="relative z-10 inline-flex rounded-xl">
                    <img src="{{ asset('images/rentadrive-logo-dark.png') }}" alt="RentaDrive" class="h-20 w-64 object-contain object-left">
                </a>

                <div class="relative z-10 max-w-xl">
                    <p class="max-w-lg text-base leading-relaxed text-slate-100">
                        Reservas, alquileres, inspecciones, facturación y vehículos en una sola plataforma.
                    </p>
                </div>

                <p class="relative z-10 text-sm text-slate-300">RentaDrive · Gestiona tu flota. Impulsa tu negocio.</p>
            </section>

            <section class="login-form-panel relative flex min-h-full items-center justify-center px-5 py-12 sm:px-8">
                <button
                    type="button"
                    class="focus-ring absolute right-5 top-5 rounded-xl border border-[#cbd9eb] bg-white p-2.5 text-[#0b2a52] shadow-sm dark:border-[#17355f] dark:bg-[#081a35] dark:text-[#8ed2ff]"
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
                    <div class="mb-8 lg:hidden">
                        <img src="{{ asset('images/rentadrive-logo-transparent.png') }}" alt="RentaDrive" class="h-20 w-64 object-contain object-left dark:hidden">
                        <img src="{{ asset('images/rentadrive-logo-dark.png') }}" alt="RentaDrive" class="hidden h-20 w-64 object-contain object-left dark:block">
                    </div>

                    {{ $slot }}
                </div>
            </section>
        </div>
    </body>
</html>
