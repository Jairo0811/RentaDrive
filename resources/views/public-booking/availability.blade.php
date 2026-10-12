@extends('layouts.public-booking')

@section('title', 'Disponibilidad')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Disponibilidad</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight">Elige una categoría</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $branch->name }} · {{ $startAt->format('d/m/Y h:i A') }} → {{ $endAt->format('d/m/Y h:i A') }} · {{ $days }} {{ $days === 1 ? 'día' : 'días' }}</p>
        </div>
        <a href="{{ route('public-booking.show', $company->slug) }}" class="font-bold text-blue-600 dark:text-blue-400">Cambiar búsqueda</a>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        @forelse ($categories as $category)
            @php($available = (int) $category->getAttribute('available_count'))
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $available > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800' }}">
                            {{ $available > 0 ? $available.' disponibles' : 'Sin disponibilidad' }}
                        </span>
                        <h2 class="mt-3 text-xl font-black">{{ $category->name }}</h2>
                        @if ($category->description)
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $category->description }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Por día</p>
                        <p class="mt-1 text-xl font-black">RD$ {{ number_format((float) $category->daily_rate, 2) }}</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-950/60">
                    <div>
                        <p class="text-xs text-slate-500">Subtotal {{ $days }} {{ $days === 1 ? 'día' : 'días' }}</p>
                        <p class="mt-1 font-black">RD$ {{ number_format((float) $category->getAttribute('quote_subtotal'), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Depósito</p>
                        <p class="mt-1 font-black">RD$ {{ number_format((float) $category->deposit_amount, 2) }}</p>
                    </div>
                </div>

                <div class="mt-5">
                    @if ($available > 0)
                        <a href="{{ route('public-booking.create', [
                            'company' => $company->slug,
                            'branch_id' => $branch->id,
                            'vehicle_category_id' => $category->id,
                            'start_at' => $startAt->format('Y-m-d\TH:i'),
                            'end_at' => $endAt->format('Y-m-d\TH:i'),
                        ]) }}" class="btn-primary w-full justify-center">Cotizar y reservar</a>
                    @else
                        <button class="btn-secondary w-full justify-center" disabled aria-disabled="true">No disponible</button>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900 lg:col-span-2">
                <h2 class="text-xl font-black">No encontramos categorías disponibles</h2>
                <p class="mt-2 text-sm text-slate-500">Prueba con otra sucursal o un período diferente.</p>
            </div>
        @endforelse
    </div>
@endsection
