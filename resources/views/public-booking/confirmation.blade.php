@extends('layouts.public-booking')

@section('title', 'Reserva '.$reservation->code)

@section('content')
    <section class="mx-auto max-w-4xl">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900 sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $reservation->status === 'cancelled' ? 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                        {{ $reservation->status === 'cancelled' ? 'Cancelada' : ($reservation->status === 'confirmed' ? 'Confirmada' : 'Recibida') }}
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Reserva {{ $reservation->code }}</h1>
                    <p class="mt-2 text-sm text-slate-500">Guarda este enlace privado para consultar el estado de tu reserva.</p>
                </div>
                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right dark:bg-slate-950/60">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total estimado</p>
                    <p class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400">RD$ {{ number_format((float) $reservation->estimated_total, 2) }}</p>
                </div>
            </div>

            <div class="mt-7 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Vehículo</p>
                    <p class="mt-2 font-black">{{ $reservation->category->name }}</p>
                    <p class="mt-1 text-sm text-slate-500">Asignación de unidad pendiente de la empresa.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Sucursal</p>
                    <p class="mt-2 font-black">{{ $reservation->branch?->name ?? $reservation->pickup_location }}</p>
                    @if ($reservation->branch?->city)<p class="mt-1 text-sm text-slate-500">{{ $reservation->branch->city }}</p>@endif
                </div>
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Retiro</p>
                    <p class="mt-2 font-black">{{ $reservation->start_at->format('d/m/Y') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $reservation->start_at->format('h:i A') }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Devolución</p>
                    <p class="mt-2 font-black">{{ $reservation->end_at->format('d/m/Y') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $reservation->end_at->format('h:i A') }}</p>
                </div>
            </div>

            @if ($reservation->bookingOptions->isNotEmpty())
                <div class="mt-6">
                    <h2 class="font-black">Extras y seguros</h2>
                    <div class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                        @foreach ($reservation->bookingOptions as $line)
                            <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                <span>{{ $line->option->name }}</span>
                                <strong>RD$ {{ number_format((float) $line->total, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <dl class="mt-6 space-y-2 rounded-2xl bg-slate-50 p-5 text-sm dark:bg-slate-950/60">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Alquiler</dt><dd class="font-bold">RD$ {{ number_format((float) ($reservation->subtotal ?? $reservation->estimated_total), 2) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Extras/seguros</dt><dd class="font-bold">RD$ {{ number_format((float) $reservation->extras_total, 2) }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-slate-200 pt-2 dark:border-slate-800"><dt class="font-black">Total estimado</dt><dd class="font-black">RD$ {{ number_format((float) $reservation->estimated_total, 2) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Depósito requerido</dt><dd class="font-bold">RD$ {{ number_format((float) $reservation->deposit_amount, 2) }}</dd></div>
            </dl>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('public-booking.show', $company->slug) }}" class="btn-secondary justify-center">Nueva búsqueda</a>

                @if (in_array($reservation->status, ['pending', 'confirmed'], true) && $reservation->start_at->isFuture())
                    <form method="POST" action="{{ route('public-booking.cancel', ['company' => $company->slug, 'token' => $reservation->public_token]) }}" onsubmit="return confirm('¿Seguro que deseas cancelar esta reserva?')">
                        @csrf
                        <button class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 px-4 py-2.5 text-sm font-bold text-red-600 transition hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40">Cancelar reserva</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
