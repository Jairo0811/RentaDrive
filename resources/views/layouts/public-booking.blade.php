<!DOCTYPE html>
<html lang="es" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0f172a">
        <title>@yield('title', 'Reserva en línea') | {{ $company->name }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/rentadrive-mark.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <a href="#main-content" class="skip-link">Saltar al contenido principal</a>

        <header class="border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('public-booking.show', $company->slug) }}" class="flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <img src="{{ asset('images/rentadrive-mark.png') }}" alt="" class="h-11 w-11 rounded-xl object-contain" aria-hidden="true">
                    <div>
                        <p class="font-black tracking-tight text-slate-950 dark:text-white">{{ $company->name }}</p>
                        <p class="text-xs text-slate-500">Reservas con RentaDrive</p>
                    </div>
                </a>

                <div class="hidden text-right text-sm sm:block">
                    @if ($company->phone)
                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $company->phone }}</p>
                    @endif
                    @if ($company->email)
                        <p class="text-xs text-slate-500">{{ $company->email }}</p>
                    @endif
                </div>
            </div>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8" tabindex="-1">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300" role="alert">
                    <p class="font-bold">Revisa la información:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white py-6 dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 text-center text-xs text-slate-500 sm:flex-row sm:px-6 sm:text-left lg:px-8">
                <span>© {{ now()->year }} {{ $company->name }}</span>
                <span>Reservas gestionadas con RentaDrive</span>
            </div>
        </footer>
    </body>
</html>
