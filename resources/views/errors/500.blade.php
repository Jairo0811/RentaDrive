<!DOCTYPE html>
<html lang="es" class="h-full dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#030914">
        <title>Error interno | RentaDrive</title>
        <link rel="icon" type="image/png" href="{{ asset('images/rentadrive-mark.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-full items-center justify-center bg-[#030914] px-6 py-12 text-white">
        <main class="w-full max-w-2xl text-center">
            <img src="{{ asset('images/rentadrive-mark.png') }}" alt="RentaDrive" class="mx-auto h-24 w-24 object-contain">
            <p class="mt-8 text-sm font-black uppercase tracking-[.35em] text-red-400">Error 500</p>
            <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-6xl">El motor necesita una revisión.</h1>
            <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-slate-400">
                Ocurrió un error inesperado. Intenta nuevamente en unos segundos o regresa al panel principal.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="btn-primary">Volver al dashboard</a>
                <button type="button" class="btn-secondary" onclick="location.reload()">Reintentar</button>
            </div>
        </main>
    </body>
</html>
