<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Abrir alquiler</p><p class="text-xs text-slate-500">Entrega de vehículo y facturación</p></div></x-slot>

    <x-page-header title="Abrir alquiler" subtitle="Valida cliente, unidad, kilometraje, combustible y condiciones económicas.">
        <x-slot name="actions"><a href="{{ route('rentals.index') }}" class="btn-secondary">Cancelar</a></x-slot>
    </x-page-header>

    @if ($reservation)
        <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-200">
            Convirtiendo la reserva <strong>{{ $reservation->code }}</strong> de {{ $reservation->customer->full_name }}.
        </div>
    @endif

    <form method="POST" action="{{ route('rentals.store') }}">
        @csrf
        <input type="hidden" name="reservation_id" value="{{ old('reservation_id', $reservation?->id) }}">
        <section class="panel p-5 sm:p-6">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="customer_id">Cliente</label>
                    <select id="customer_id" name="customer_id" class="form-input" required><option value="">Selecciona</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((string) old('customer_id', $reservation?->customer_id) === (string) $customer->id)>{{ $customer->full_name }} · {{ $customer->document_number }}</option>@endforeach</select>
                </div>
                <div class="xl:col-span-2">
                    <label class="form-label" for="vehicle_id">Vehículo</label>
                    <select id="vehicle_id" name="vehicle_id" class="form-input" required><option value="">Selecciona una unidad disponible</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}" data-mileage="{{ $vehicle->mileage }}" data-rate="{{ $vehicle->effective_daily_rate }}" data-deposit="{{ $vehicle->category->deposit_amount }}" @selected((string) old('vehicle_id', $reservation?->vehicle_id) === (string) $vehicle->id)>{{ $vehicle->display_name }} · {{ $vehicle->category->name }} · RD$ {{ number_format($vehicle->effective_daily_rate, 2) }}/día</option>@endforeach</select>
                </div>
                <div><label class="form-label" for="start_at">Fecha de entrega</label><input id="start_at" type="datetime-local" name="start_at" value="{{ old('start_at', $reservation?->start_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" class="form-input" required></div>
                <div><label class="form-label" for="expected_return_at">Retorno esperado</label><input id="expected_return_at" type="datetime-local" name="expected_return_at" value="{{ old('expected_return_at', $reservation?->end_at?->format('Y-m-d\TH:i') ?? now()->addDay()->format('Y-m-d\TH:i')) }}" class="form-input" required></div>
                <div><label class="form-label" for="opening_mileage">Kilometraje de salida</label><input id="opening_mileage" type="number" name="opening_mileage" value="{{ old('opening_mileage', $reservation?->vehicle?->mileage ?? 0) }}" class="form-input" required></div>
                <div><label class="form-label" for="fuel_out">Combustible de salida (%)</label><input id="fuel_out" type="number" step="0.01" min="0" max="100" name="fuel_out" value="{{ old('fuel_out', 100) }}" class="form-input" required></div>
                <div><label class="form-label" for="daily_rate">Tarifa diaria</label><input id="daily_rate" type="number" step="0.01" name="daily_rate" value="{{ old('daily_rate', $reservation?->daily_rate) }}" class="form-input" required></div>
                <div><label class="form-label" for="deposit_amount">Depósito</label><input id="deposit_amount" type="number" step="0.01" name="deposit_amount" value="{{ old('deposit_amount', $reservation?->category?->deposit_amount ?? 0) }}" class="form-input"></div>
                <div><label class="form-label" for="fees">Cargos adicionales</label><input id="fees" type="number" step="0.01" name="fees" value="{{ old('fees', 0) }}" class="form-input"></div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label" for="notes">Condiciones y notas</label><textarea id="notes" name="notes" rows="3" class="form-input">{{ old('notes') }}</textarea></div>
            </div>
        </section>
        <div class="mt-6 flex justify-end"><button class="btn-primary">Abrir alquiler y generar factura</button></div>
    </form>
</x-app-layout>
