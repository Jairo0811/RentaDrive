@props([
    'title' => 'RentaDrive',
    'homeUrl' => null,
    'badge' => 'SaaS para rent-a-car',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#030914">

        <title>{{ $title }} | RentaDrive</title>
        <link rel="icon" type="image/png" href="{{ asset('images/rentadrive-mark.png') }}">
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        >

        @include('layouts.partials.theme-script')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-slate-50 text-slate-950 antialiased dark:bg-[#030914] dark:text-white">
        <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

        <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-[#030914]/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3 sm:px-8">
                <a href="{{ $homeUrl ?: route('home') }}" class="focus-ring inline-flex items-center gap-3 rounded-xl">
                    <img
                        src="{{ asset('images/rentadrive-logo-transparent.png') }}"
                        alt="RentaDrive"
                        class="h-12 w-44 object-contain object-left dark:hidden"
                    >
                    <img
                        src="{{ asset('images/rentadrive-logo-dark.png') }}"
                        alt="RentaDrive"
                        class="hidden h-12 w-44 object-contain object-left dark:block"
                    >
                </a>

                <div class="flex items-center gap-2">
                    <span class="hidden rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-300 md:inline-flex">
                        {{ $badge }}
                    </span>

                    @include('layouts.partials.accessibility-menu')

                    <button
                        type="button"
                        class="focus-ring rounded-xl border border-slate-200 bg-white p-2.5 text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                        @click="$store.theme.toggle()"
                        :aria-label="$store.theme.dark ? 'Activar modo claro' : 'Activar modo oscuro'"
                    >
                        <i x-show="! $store.theme.dark" class="fa-solid fa-sun h-5 w-5 text-center leading-5" aria-hidden="true"></i>
                        <i x-cloak x-show="$store.theme.dark" class="fa-solid fa-moon h-5 w-5 text-center leading-5" aria-hidden="true"></i>
                    </button>

                    <a href="{{ route('login') }}" class="btn-secondary hidden sm:inline-flex">
                        <i class="fa-solid fa-user-lock" aria-hidden="true"></i>
                        Acceso
                    </a>
                </div>
            </div>
        </header>

        <main id="main-content" tabindex="-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-8 text-sm text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">
                <p>© {{ now()->year }} RentaDrive. Gestión y reservas para rent-a-car.</p>
                <p class="font-semibold">Hecho para operar en República Dominicana.</p>
            </div>
        </footer>
    </body>
</html>
