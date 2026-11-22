<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Guarda estos códigos en un lugar seguro (por ejemplo, un gestor de contraseñas). Cada uno sirve <strong>una sola vez</strong> si pierdes tu dispositivo.
                    <strong>No se volverán a mostrar.</strong>
                </p>

                <ul class="mt-5 grid gap-2 font-mono text-base text-gray-900 dark:text-gray-100 sm:grid-cols-2" aria-label="Códigos de recuperación">
                    @foreach ($codes as $code)
                        <li class="select-all rounded bg-slate-100 px-3 py-2 dark:bg-slate-800">{{ $code }}</li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    <a href="{{ route('profile.edit') }}" class="focus-ring inline-flex min-h-11 items-center rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white">Ya los guardé</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
