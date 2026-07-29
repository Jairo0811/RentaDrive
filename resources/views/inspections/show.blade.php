<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Inspección #{{ $inspection->id }}</p><p class="text-xs text-slate-500">{{ $inspection->rental->code }}</p></div></x-slot>

    <x-page-header :title="'Inspección de '.($inspection->type === 'delivery' ? 'entrega' : 'devolución')" :subtitle="$inspection->vehicle->display_name">
        <x-slot name="actions"><a href="{{ route('rentals.show', $inspection->rental) }}" class="btn-secondary">Ver alquiler</a></x-slot>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel p-5 sm:p-6 lg:col-span-2">
            <div class="flex items-center justify-between"><h2 class="font-black text-slate-950 dark:text-white">Resultado</h2><x-status-badge :status="$inspection->type" /></div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Fecha' => $inspection->inspected_at->format('d/m/Y h:i A'),
                    'Kilometraje' => number_format($inspection->mileage).' km',
                    'Combustible' => $inspection->fuel_level.'%',
                    'Carrocería' => ucfirst($inspection->body_condition),
                    'Interior' => ucfirst($inspection->interior_condition),
                    'Neumáticos' => ucfirst($inspection->tires_condition),
                ] as $label => $value)<div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-950/50"><p class="text-xs font-bold uppercase text-slate-400">{{ $label }}</p><p class="mt-2 font-semibold text-slate-800 dark:text-slate-200">{{ $value }}</p></div>@endforeach
            </div>
            @if ($inspection->accessories || $inspection->damages)<div class="mt-6 grid gap-5 md:grid-cols-2"><div><h3 class="font-bold text-slate-900 dark:text-white">Accesorios</h3><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $inspection->accessories ?: 'Sin detalle' }}</p></div><div><h3 class="font-bold text-slate-900 dark:text-white">Daños</h3><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $inspection->damages ?: 'Sin daños reportados' }}</p></div></div>@endif
        </section>
        <aside class="panel p-5 sm:p-6"><h2 class="font-black text-slate-950 dark:text-white">Responsable</h2><p class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $inspection->inspector?->name ?: 'Sistema' }}</p><p class="mt-1 text-xs text-slate-500">{{ $inspection->inspector?->email }}</p><div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-800"><p class="text-xs font-bold uppercase text-slate-400">Cliente</p><p class="mt-2 font-semibold">{{ $inspection->rental->customer->full_name }}</p></div></aside>
    </div>

    @if (!empty($inspection->photos))
        <section class="panel mt-6 p-5 sm:p-6"><h2 class="font-black text-slate-950 dark:text-white">Evidencias</h2><div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ($inspection->photos as $photo)<a href="{{ Storage::url($photo) }}" target="_blank"><img src="{{ Storage::url($photo) }}" alt="Evidencia de inspección" class="aspect-video w-full rounded-xl object-cover"></a>@endforeach</div></section>
    @endif
</x-app-layout>
