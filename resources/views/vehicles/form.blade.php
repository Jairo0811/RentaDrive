<x-app-layout>
    <x-slot name="header">
        <div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $vehicle->exists ? 'Editar vehículo' : 'Nuevo vehículo' }}</p><p class="text-xs text-slate-500">Ficha técnica de la unidad</p></div>
    </x-slot>

    <x-page-header :title="$vehicle->exists ? 'Editar vehículo' : 'Registrar vehículo'" subtitle="Define identificación, especificaciones, tarifa y disponibilidad.">
        <x-slot name="actions"><a href="{{ $vehicle->exists ? route('vehicles.show', $vehicle) : route('vehicles.index') }}" class="btn-secondary">Cancelar</a></x-slot>
    </x-page-header>

    <form method="POST" action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}">
        @csrf
        @if ($vehicle->exists) @method('PUT') @endif
        <section class="panel p-5 sm:p-6">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label class="form-label" for="vehicle_model_id">Marca, modelo y año</label>
                    <select id="vehicle_model_id" name="vehicle_model_id" class="form-input" required>
                        <option value="">Selecciona</option>
                        @foreach ($models as $model)<option value="{{ $model->id }}" @selected((string) old('vehicle_model_id', $vehicle->vehicle_model_id) === (string) $model->id)>{{ $model->display_name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="vehicle_category_id">Categoría</label>
                    <select id="vehicle_category_id" name="vehicle_category_id" class="form-input" required>
                        <option value="">Selecciona</option>
                        @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('vehicle_category_id', $vehicle->vehicle_category_id) === (string) $category->id)>{{ $category->name }} — RD$ {{ number_format((float) $category->daily_rate, 2) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="status">Estado</label>
                    <select id="status" name="status" class="form-input" required>
                        @foreach (['available' => 'Disponible', 'reserved' => 'Reservado', 'rented' => 'Alquilado', 'maintenance' => 'Mantenimiento', 'inactive' => 'Inactivo'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $vehicle->status ?: 'available') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach (['code' => 'Código interno', 'plate' => 'Placa', 'vin' => 'VIN', 'color' => 'Color'] as $field => $label)
                    <div><label class="form-label" for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $vehicle->{$field}) }}" class="form-input" @required($field !== 'vin')></div>
                @endforeach
                <div>
                    <label class="form-label" for="transmission">Transmisión</label>
                    <select id="transmission" name="transmission" class="form-input"><option value="automatic" @selected(old('transmission', $vehicle->transmission ?: 'automatic') === 'automatic')>Automática</option><option value="manual" @selected(old('transmission', $vehicle->transmission) === 'manual')>Manual</option></select>
                </div>
                <div>
                    <label class="form-label" for="fuel_type">Combustible</label>
                    <select id="fuel_type" name="fuel_type" class="form-input">
                        @foreach (['gasoline' => 'Gasolina', 'diesel' => 'Diésel', 'hybrid' => 'Híbrido', 'electric' => 'Eléctrico'] as $value => $label)<option value="{{ $value }}" @selected(old('fuel_type', $vehicle->fuel_type ?: 'gasoline') === $value)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div><label class="form-label" for="seats">Asientos</label><input id="seats" type="number" name="seats" min="1" value="{{ old('seats', $vehicle->seats ?: 5) }}" class="form-input" required></div>
                <div><label class="form-label" for="mileage">Kilometraje</label><input id="mileage" type="number" name="mileage" min="0" value="{{ old('mileage', $vehicle->mileage ?: 0) }}" class="form-input" required></div>
                <div><label class="form-label" for="daily_rate_override">Tarifa especial</label><input id="daily_rate_override" type="number" step="0.01" name="daily_rate_override" value="{{ old('daily_rate_override', $vehicle->daily_rate_override) }}" class="form-input" placeholder="Usa la tarifa de categoría"></div>
                <div><label class="form-label" for="acquisition_date">Fecha de adquisición</label><input id="acquisition_date" type="date" name="acquisition_date" value="{{ old('acquisition_date', $vehicle->acquisition_date?->format('Y-m-d')) }}" class="form-input"></div>
                <div><label class="form-label" for="next_maintenance_at">Próximo mantenimiento (km)</label><input id="next_maintenance_at" type="number" name="next_maintenance_at" value="{{ old('next_maintenance_at', $vehicle->next_maintenance_at) }}" class="form-input"></div>
                <div class="md:col-span-2 xl:col-span-4"><label class="form-label" for="notes">Notas</label><textarea id="notes" name="notes" rows="3" class="form-input">{{ old('notes', $vehicle->notes) }}</textarea></div>
            </div>
        </section>
        <div class="mt-6 flex justify-end"><button class="btn-primary">{{ $vehicle->exists ? 'Guardar cambios' : 'Registrar vehículo' }}</button></div>
    </form>
</x-app-layout>
