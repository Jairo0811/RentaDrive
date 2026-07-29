<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $reservation->exists ? 'Editar reserva' : 'Nueva reserva' }}</p><p class="text-xs text-slate-500">Programación de disponibilidad</p></div></x-slot>

    <x-page-header :title="$reservation->exists ? 'Editar reserva' : 'Crear reserva'" subtitle="Selecciona cliente, categoría, vehículo y período.">
        <x-slot name="actions"><a href="{{ $reservation->exists ? route('reservations.show', $reservation) : route('reservations.index') }}" class="btn-secondary">Cancelar</a></x-slot>
    </x-page-header>

    <form method="POST" action="{{ $reservation->exists ? route('reservations.update', $reservation) : route('reservations.store') }}">
        @csrf
        @if ($reservation->exists) @method('PUT') @endif
        <section class="panel p-5 sm:p-6">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="customer_id">Cliente</label>
                    <select id="customer_id" name="customer_id" class="form-input" required><option value="">Selecciona</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((string) old('customer_id', $reservation->customer_id ?: request('customer')) === (string) $customer->id)>{{ $customer->full_name }} · {{ $customer->document_number }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="form-label" for="vehicle_category_id">Categoría</label>
                    <select id="vehicle_category_id" name="vehicle_category_id" class="form-input" required><option value="">Selecciona</option>@foreach ($categories as $category)<option value="{{ $category->id }}" data-rate="{{ $category->daily_rate }}" @selected((string) old('vehicle_category_id', $reservation->vehicle_category_id) === (string) $category->id)>{{ $category->name }} · RD$ {{ number_format((float) $category->daily_rate, 2) }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="form-label" for="vehicle_id">Vehículo específico</label>
                    <select id="vehicle_id" name="vehicle_id" class="form-input"><option value="">Asignar después</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}" data-category="{{ $vehicle->vehicle_category_id }}" data-rate="{{ $vehicle->effective_daily_rate }}" @selected((string) old('vehicle_id', $reservation->vehicle_id) === (string) $vehicle->id)>{{ $vehicle->display_name }} · {{ $vehicle->category->name }}</option>@endforeach</select>
                    <p class="mt-2 text-xs text-slate-500">La disponibilidad se valida antes de guardar.</p>
                </div>
                <div><label class="form-label" for="start_at">Inicio</label><input id="start_at" type="datetime-local" name="start_at" value="{{ old('start_at', $reservation->start_at?->format('Y-m-d\TH:i') ?? now()->addDay()->format('Y-m-d\T09:00')) }}" class="form-input" required></div>
                <div><label class="form-label" for="end_at">Fin</label><input id="end_at" type="datetime-local" name="end_at" value="{{ old('end_at', $reservation->end_at?->format('Y-m-d\TH:i') ?? now()->addDays(2)->format('Y-m-d\T09:00')) }}" class="form-input" required></div>
                <div><label class="form-label" for="daily_rate">Tarifa diaria</label><input id="daily_rate" type="number" step="0.01" name="daily_rate" value="{{ old('daily_rate', $reservation->daily_rate) }}" class="form-input" required></div>
                <div><label class="form-label" for="pickup_location">Lugar de entrega</label><input id="pickup_location" name="pickup_location" value="{{ old('pickup_location', $reservation->pickup_location ?: 'Oficina principal') }}" class="form-input" required></div>
                <div><label class="form-label" for="return_location">Lugar de devolución</label><input id="return_location" name="return_location" value="{{ old('return_location', $reservation->return_location ?: 'Oficina principal') }}" class="form-input" required></div>
                <div>
                    <label class="form-label" for="status">Estado</label>
                    <select id="status" name="status" class="form-input">@foreach (['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $reservation->status ?: 'pending') === $value)>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label" for="notes">Notas</label><textarea id="notes" name="notes" rows="3" class="form-input">{{ old('notes', $reservation->notes) }}</textarea></div>
            </div>
        </section>
        <div class="mt-6 flex justify-end"><button class="btn-primary">{{ $reservation->exists ? 'Guardar cambios' : 'Crear reserva' }}</button></div>
    </form>
</x-app-layout>
