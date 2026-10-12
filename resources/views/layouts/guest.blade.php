<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#071a38">

        <title>Acceso | {{ config('app.name', 'RentaDrive') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/rentadrive-mark.png') }}">
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        >

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full antialiased">
        <a href="#main-content" class="skip-link">Saltar al formulario de acceso</a>

        <div class="grid min-h-full bg-[#030914] lg:grid-cols-[1.08fr_.92fr]">
            <section class="login-brand-panel relative hidden overflow-hidden bg-[#030914] lg:flex lg:flex-col lg:p-12" aria-label="Identidad visual de RentaDrive">
                <img
                    src="{{ asset('images/rentadrive-racing.jpeg') }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    aria-hidden="true"
                >
                <div class="absolute inset-0 bg-gradient-to-b from-[#030914]/35 via-[#04152c]/20 to-[#030914]/55" aria-hidden="true"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-[#030914]/10 via-transparent to-[#030914]/55" aria-hidden="true"></div>
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#0568f5] via-[#25a7ff] to-[#e2232e]" aria-hidden="true"></div>

                <a href="{{ route('home') }}" class="focus-ring relative z-10 inline-flex rounded-xl" aria-label="Ir al inicio de RentaDrive">
                    <img src="{{ asset('images/rentadrive-logo-dark.png') }}" alt="RentaDrive" class="h-20 w-64 object-contain object-left">
                </a>
            </section>

            <main id="main-content" class="login-form-panel relative flex min-h-full items-center justify-center px-5 py-12 sm:px-8" tabindex="-1">
                <div class="absolute right-5 top-5">
                    @include('layouts.partials.accessibility-menu')
                </div>

                <div class="w-full max-w-md">
                    <div class="mb-8 lg:hidden">
                        <img src="{{ asset('images/rentadrive-logo-dark.png') }}" alt="RentaDrive" class="h-20 w-64 object-contain object-left">
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
