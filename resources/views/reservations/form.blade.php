<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $reservation->exists ? 'Editar reserva' : 'Nueva reserva' }}</p><p class="text-xs text-slate-400">Programación de disponibilidad</p></div></x-slot>

    <x-page-header :title="$reservation->exists ? 'Editar reserva' : 'Crear reserva'" subtitle="Selecciona cliente, categoría, vehículo y período.">
        <x-slot name="actions"><a href="{{ $reservation->exists ? route('reservations.show', $reservation) : route('reservations.index') }}" class="btn-secondary">Cancelar</a></x-slot>
    </x-page-header>

    <form method="POST" action="{{ $reservation->exists ? route('reservations.update', $reservation) : route('reservations.store') }}">
        @csrf
        @if ($reservation->exists) @method('PUT') @endif
        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-700/70 bg-red-950/40 p-4 text-sm text-red-200" role="alert" aria-labelledby="reservation-errors-title">
                <h2 id="reservation-errors-title" class="font-bold text-red-100">Revisa los campos indicados antes de guardar la reserva.</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <section class="panel p-5 sm:p-6">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="customer_id">Cliente</label>
                    <x-lookup-select id="customer_id" name="customer_id" type="customers" :value="old('customer_id', $reservation->customer_id ?: request('customer'))" placeholder="Busca por nombre o documento" required />
                </div>
                <div>
                    <label class="form-label" for="vehicle_category_id">Categoría</label>
                    <select id="vehicle_category_id" name="vehicle_category_id" class="form-input" required @error('vehicle_category_id') aria-invalid="true" aria-describedby="vehicle_category_id-error" @enderror><option value="">Selecciona</option>@foreach ($categories as $category)<option value="{{ $category->id }}" data-rate="{{ $category->daily_rate }}" @selected((string) old('vehicle_category_id', $reservation->vehicle_category_id) === (string) $category->id)>{{ $category->name }} · RD$ {{ number_format((float) $category->daily_rate, 2) }}</option>@endforeach</select>
                    <x-input-error id="vehicle_category_id-error" :messages="$errors->get('vehicle_category_id')" class="mt-2" />
                </div>
                <div>
                    <label class="form-label" for="vehicle_id">Vehículo específico</label>
                    <x-lookup-select id="vehicle_id" name="vehicle_id" type="vehicles" scope="reservation" :value="old('vehicle_id', $reservation->vehicle_id)" placeholder="Asignar después (busca por placa o modelo)" clearable />
                    <p class="mt-2 text-xs text-slate-400">La disponibilidad se valida antes de guardar.</p>
                </div>
                <div><label class="form-label" for="start_at">Inicio</label><input id="start_at" type="datetime-local" name="start_at" value="{{ old('start_at', $reservation->start_at?->format('Y-m-d\TH:i') ?? now()->addDay()->format('Y-m-d\T09:00')) }}" class="form-input" required @error('start_at') aria-invalid="true" aria-describedby="start_at-error" @enderror><x-input-error id="start_at-error" :messages="$errors->get('start_at')" class="mt-2" /></div>
                <div><label class="form-label" for="end_at">Fin</label><input id="end_at" type="datetime-local" name="end_at" value="{{ old('end_at', $reservation->end_at?->format('Y-m-d\TH:i') ?? now()->addDays(2)->format('Y-m-d\T09:00')) }}" class="form-input" required @error('end_at') aria-invalid="true" aria-describedby="end_at-error" @enderror><x-input-error id="end_at-error" :messages="$errors->get('end_at')" class="mt-2" /></div>
                <div><label class="form-label" for="daily_rate">Tarifa diaria</label><input id="daily_rate" type="number" step="0.01" name="daily_rate" value="{{ old('daily_rate', $reservation->daily_rate) }}" class="form-input" required @error('daily_rate') aria-invalid="true" aria-describedby="daily_rate-error" @enderror><x-input-error id="daily_rate-error" :messages="$errors->get('daily_rate')" class="mt-2" /></div>
                <div><label class="form-label" for="pickup_location">Lugar de entrega</label><input id="pickup_location" name="pickup_location" value="{{ old('pickup_location', $reservation->pickup_location ?: 'Oficina principal') }}" class="form-input" required @error('pickup_location') aria-invalid="true" aria-describedby="pickup_location-error" @enderror><x-input-error id="pickup_location-error" :messages="$errors->get('pickup_location')" class="mt-2" /></div>
                <div><label class="form-label" for="return_location">Lugar de devolución</label><input id="return_location" name="return_location" value="{{ old('return_location', $reservation->return_location ?: 'Oficina principal') }}" class="form-input" required @error('return_location') aria-invalid="true" aria-describedby="return_location-error" @enderror><x-input-error id="return_location-error" :messages="$errors->get('return_location')" class="mt-2" /></div>
                <div>
                    <label class="form-label" for="status">Estado</label>
                    <select id="status" name="status" class="form-input" @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>@foreach (['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $reservation->status ?: 'pending') === $value)>{{ $label }}</option>@endforeach</select><x-input-error id="status-error" :messages="$errors->get('status')" class="mt-2" />
                </div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label" for="notes">Notas</label><textarea id="notes" name="notes" rows="3" class="form-input" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ old('notes', $reservation->notes) }}</textarea><x-input-error id="notes-error" :messages="$errors->get('notes')" class="mt-2" /></div>
            </div>
        </section>
        <div class="mt-6 flex justify-end"><button class="btn-primary">{{ $reservation->exists ? 'Guardar cambios' : 'Crear reserva' }}</button></div>
    </form>
</x-app-layout>
