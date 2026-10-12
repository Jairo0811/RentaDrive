@extends('layouts.public-booking')

@section('title', 'Reserva en línea')

@section('content')
    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-blue-700 p-6 text-white shadow-2xl sm:p-10">
        <div class="max-w-3xl">
            <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[.18em] text-blue-100">Reserva en línea</span>
            <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-5xl">Encuentra tu vehículo en pocos pasos.</h1>
            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-blue-100 sm:text-base">Selecciona la sucursal y las fechas. Te mostraremos las categorías con disponibilidad real y el precio estimado antes de pedir tus datos.</p>
        </div>
    </section>

    <section class="mx-auto -mt-6 max-w-4xl rounded-3xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-800 dark:bg-slate-900 sm:p-7">
        <form method="GET" action="{{ route('public-booking.availability', $company->slug) }}" class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="branch_id" class="mb-2 block text-sm font-bold">Sucursal de retiro y devolución</label>
                <select id="branch_id" name="branch_id" class="form-input" required>
                    <option value="">Selecciona una sucursal</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', request('branch_id')) === (string) $branch->id)>
                            {{ $branch->name }}{{ $branch->city ? ' · '.$branch->city : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="start_at" class="mb-2 block text-sm font-bold">Retiro</label>
                <input id="start_at" type="datetime-local" name="start_at" class="form-input" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('start_at', request('start_at', now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i'))) }}" required>
            </div>

            <div>
                <label for="end_at" class="mb-2 block text-sm font-bold">Devolución</label>
                <input id="end_at" type="datetime-local" name="end_at" class="form-input" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('end_at', request('end_at', now()->addDays(3)->setTime(9, 0)->format('Y-m-d\TH:i'))) }}" required>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button class="btn-primary w-full justify-center sm:w-auto">Buscar disponibilidad</button>
            </div>
        </form>
    </section>

    <section class="mt-10 grid gap-4 md:grid-cols-3">
        @foreach ([
            ['icon' => '🚗', 'title' => 'Disponibilidad real', 'text' => 'Consultamos la flota y las reservas existentes antes de mostrar opciones.'],
            ['icon' => '🧾', 'title' => 'Precio claro', 'text' => 'Verás días, tarifa, depósito y extras antes de confirmar.'],
            ['icon' => '🔒', 'title' => 'Reserva segura', 'text' => 'Tu reserva queda identificada con un enlace privado para consultarla o cancelarla.'],
        ] as $item)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <span class="text-2xl" aria-hidden="true">{{ $item['icon'] }}</span>
                <h2 class="mt-3 font-black">{{ $item['title'] }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $item['text'] }}</p>
            </article>
        @endforeach
    </section>
@endsection
