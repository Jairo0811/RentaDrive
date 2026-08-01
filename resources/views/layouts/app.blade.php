<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0f172a">

        <title>{{ config('app.name', 'RentaDrive') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/rentadrive-mark.png') }}">

        @include('layouts.partials.theme-script')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full antialiased" x-data="{ sidebarOpen: false }">
        <div class="flex h-full overflow-hidden bg-slate-100 dark:bg-slate-950">
            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden"
                @click="sidebarOpen = false"
                aria-hidden="true"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full transition-transform duration-300 lg:static lg:translate-x-0"
                :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': ! sidebarOpen }"
            >
                @include('layouts.partials.sidebar')
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                @include('layouts.partials.topbar')

                <main class="min-h-0 flex-1 overflow-y-auto">
                    <div class="flex min-h-full flex-col">
                        <div class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 sm:px-6 lg:px-8">
                            @if (session('status'))
                                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/50 dark:text-red-300">
                                    <p class="font-bold">Revisa la información indicada:</p>
                                    <ul class="mt-1 list-disc space-y-1 pl-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{ $slot }}
                        </div>

                        <footer class="border-t border-slate-200 bg-white/80 px-4 py-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/80 sm:px-6 lg:px-8">
                            <div class="mx-auto flex w-full max-w-[1600px] flex-col items-center justify-between gap-3 text-center text-sm text-slate-500 dark:text-slate-400 sm:flex-row sm:text-left">
                                <div class="flex items-center gap-3">
                                    <img
                                        src="{{ asset('images/rentadrive-mark.png') }}"
                                        alt="RentaDrive"
                                        class="h-8 w-8 rounded-lg object-contain"
                                    >

                                    <div>
                                        <p class="font-semibold text-slate-700 dark:text-slate-200">
                                            © {{ now()->year }} RentaDrive
                                        </p>
                                        <p class="text-xs">
                                            Gestiona tu flota. Impulsa tu negocio.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:justify-end">
                                    <span>Versión 1.0</span>

                                    <span class="hidden text-slate-300 dark:text-slate-700 sm:inline">•</span>

                                    <a
                                        href="https://github.com/Jairo0811/RentaDrive"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-medium text-blue-600 transition hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        Repositorio en GitHub
                                    </a>
                                </div>
                            </div>
                        </footer>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
