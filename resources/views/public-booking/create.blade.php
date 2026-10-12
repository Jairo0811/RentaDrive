@extends('layouts.public-booking')

@section('title', 'Cotizar y reservar')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Cotización</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">Completa tu reserva</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $branch->name }} · {{ $category->name }} · {{ $startAt->format('d/m/Y h:i A') }} → {{ $endAt->format('d/m/Y h:i A') }}</p>
    </div>

    <form method="POST" action="{{ route('public-booking.store', $company->slug) }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
        <input type="hidden" name="vehicle_category_id" value="{{ $category->id }}">
        <input type="hidden" name="start_at" value="{{ $startAt->format('Y-m-d H:i:s') }}">
        <input type="hidden" name="end_at" value="{{ $endAt->format('Y-m-d H:i:s') }}">

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-black">Extras y seguros</h2>
                <p class="mt-1 text-sm text-slate-500">Opcionales. El total se actualiza al seleccionarlos.</p>

                <div class="mt-5 space-y-3">
                    @forelse ($options as $option)
                        @php($optionTotal = $option->pricing_mode === 'per_day' ? (float) $option->price * $quote['days'] : (float) $option->price)
                        <label class="flex cursor-pointer items-start gap-4 rounded-2xl border border-slate-200 p-4 transition hover:border-blue-300 dark:border-slate-700 dark:hover:border-blue-700">
                            <input type="checkbox" name="booking_option_ids[]" value="{{ $option->id }}" data-booking-option data-total="{{ number_format($optionTotal, 2, '.', '') }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(in_array($option->id, old('booking_option_ids', [])))>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <strong>{{ $option->name }}</strong>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-800">{{ $option->type === 'insurance' ? 'Seguro' : 'Extra' }}</span>
                                </span>
                                @if ($option->description)
                                    <span class="mt-1 block text-sm text-slate-500">{{ $option->description }}</span>
                                @endif
                                <span class="mt-2 block text-sm font-bold text-blue-600 dark:text-blue-400">
                                    RD$ {{ number_format((float) $option->price, 2) }} {{ $option->pricing_mode === 'per_day' ? '/ día' : 'único' }}
                                </span>
                            </span>
                        </label>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-950/60">Esta empresa no tiene extras o seguros opcionales configurados.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-black">Tus datos</h2>
                <p class="mt-1 text-sm text-slate-500">Usaremos estos datos para identificar y gestionar la reserva.</p>

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="form-label" for="document_type">Documento</label>
                        <select id="document_type" name="document_type" class="form-input" required>
                            <option value="cedula" @selected(old('document_type', 'cedula') === 'cedula')>Cédula</option>
                            <option value="passport" @selected(old('document_type') === 'passport')>Pasaporte</option>
                        </select>
                    </div>
                    <div><label class="form-label" for="document_number">Número</label><input id="document_number" name="document_number" value="{{ old('document_number') }}" class="form-input" required autocomplete="off"></div>
                    <div><label class="form-label" for="first_name">Nombre</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" class="form-input" required autocomplete="given-name"></div>
                    <div><label class="form-label" for="last_name">Apellido</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" class="form-input" required autocomplete="family-name"></div>
                    <div><label class="form-label" for="email">Correo</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="form-input" required autocomplete="email"></div>
                    <div><label class="form-label" for="phone">Teléfono</label><input id="phone" name="phone" value="{{ old('phone') }}" class="form-input" required autocomplete="tel"></div>
                    <div class="md:col-span-2"><label class="form-label" for="notes">Notas para la empresa</label><textarea id="notes" name="notes" rows="3" class="form-input" maxlength="1000">{{ old('notes') }}</textarea></div>
                </div>
            </section>
        </div>

        <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-6">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-slate-400">Resumen</p>
            <h2 class="mt-2 text-xl font-black">{{ $category->name }}</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Duración</dt><dd class="font-bold">{{ $quote['days'] }} {{ $quote['days'] === 1 ? 'día' : 'días' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Tarifa diaria</dt><dd class="font-bold">RD$ {{ number_format($quote['daily_rate'], 2) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Alquiler</dt><dd class="font-bold">RD$ {{ number_format($quote['subtotal'], 2) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Extras/seguros</dt><dd id="extras-total" class="font-bold">RD$ 0.00</dd></div>
                <div class="border-t border-slate-200 pt-3 dark:border-slate-700"><div class="flex justify-between gap-4"><dt class="font-black">Total estimado</dt><dd id="estimated-total" class="text-lg font-black text-blue-600 dark:text-blue-400">RD$ {{ number_format($quote['subtotal'], 2) }}</dd></div></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Depósito requerido</dt><dd class="font-bold">RD$ {{ number_format($quote['deposit_amount'], 2) }}</dd></div>
            </dl>

            <p class="mt-4 rounded-xl bg-blue-50 p-3 text-xs leading-relaxed text-blue-800 dark:bg-blue-950/40 dark:text-blue-200">El depósito se muestra como referencia y no se cobra en esta fase. Los pagos online se incorporan en la Fase 3.</p>

            <button class="btn-primary mt-5 w-full justify-center">Confirmar reserva</button>
            <a href="{{ route('public-booking.availability', [
                'company' => $company->slug,
                'branch_id' => $branch->id,
                'start_at' => $startAt->format('Y-m-d\TH:i'),
                'end_at' => $endAt->format('Y-m-d\TH:i'),
            ]) }}" class="mt-3 block text-center text-sm font-bold text-slate-500">Volver a categorías</a>
        </aside>
    </form>

    <script>
        (() => {
            const base = {{ json_encode((float) $quote['subtotal']) }};
            const options = Array.from(document.querySelectorAll('[data-booking-option]'));
            const extrasElement = document.getElementById('extras-total');
            const totalElement = document.getElementById('estimated-total');
            const money = new Intl.NumberFormat('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const render = () => {
                const extras = options
                    .filter(option => option.checked)
                    .reduce((sum, option) => sum + Number(option.dataset.total || 0), 0);

                extrasElement.textContent = `RD$ ${money.format(extras)}`;
                totalElement.textContent = `RD$ ${money.format(base + extras)}`;
            };

            options.forEach(option => option.addEventListener('change', render));
            render();
        })();
    </script>
@endsection
