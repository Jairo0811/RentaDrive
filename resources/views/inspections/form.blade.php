<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Nueva inspección</p><p class="text-xs text-slate-500">Evidencia del estado vehicular</p></div></x-slot>

    <x-page-header title="Registrar inspección" subtitle="Completa la revisión física y adjunta fotografías cuando sea necesario.">
        <x-slot name="actions"><a href="{{ route('inspections.index') }}" class="btn-secondary">Cancelar</a></x-slot>
    </x-page-header>

    <form method="POST" action="{{ route('inspections.store') }}" enctype="multipart/form-data">
        @csrf
        <section class="panel p-5 sm:p-6">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="form-label" for="rental_id">Alquiler</label>
                    <select id="rental_id" name="rental_id" class="form-input" required><option value="">Selecciona</option>@foreach ($rentals as $rental)<option value="{{ $rental->id }}" @selected((string) old('rental_id', $selectedRental) === (string) $rental->id)>{{ $rental->code }} · {{ $rental->customer->full_name }} · {{ $rental->vehicle->display_name }}</option>@endforeach</select>
                </div>
                <div><label class="form-label" for="type">Tipo</label><select id="type" name="type" class="form-input"><option value="delivery" @selected(old('type') === 'delivery')>Entrega</option><option value="return" @selected(old('type') === 'return')>Devolución</option></select></div>
                <div><label class="form-label" for="inspected_at">Fecha</label><input id="inspected_at" type="datetime-local" name="inspected_at" value="{{ old('inspected_at', now()->format('Y-m-d\TH:i')) }}" class="form-input" required></div>
                <div><label class="form-label" for="mileage">Kilometraje</label><input id="mileage" type="number" min="0" name="mileage" value="{{ old('mileage') }}" class="form-input" required></div>
                <div><label class="form-label" for="fuel_level">Combustible (%)</label><input id="fuel_level" type="number" min="0" max="100" step="0.01" name="fuel_level" value="{{ old('fuel_level', 100) }}" class="form-input" required></div>
                @foreach (['body_condition' => 'Carrocería', 'interior_condition' => 'Interior'] as $field => $label)
                    <div><label class="form-label" for="{{ $field }}">{{ $label }}</label><select id="{{ $field }}" name="{{ $field }}" class="form-input">@foreach (['excellent' => 'Excelente', 'good' => 'Bueno', 'fair' => 'Regular', 'damaged' => 'Con daños'] as $value => $text)<option value="{{ $value }}" @selected(old($field, 'good') === $value)>{{ $text }}</option>@endforeach</select></div>
                @endforeach
                <div><label class="form-label" for="tires_condition">Neumáticos</label><select id="tires_condition" name="tires_condition" class="form-input">@foreach (['excellent' => 'Excelente', 'good' => 'Bueno', 'fair' => 'Regular', 'replace' => 'Reemplazar'] as $value => $text)<option value="{{ $value }}" @selected(old('tires_condition', 'good') === $value)>{{ $text }}</option>@endforeach</select></div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label" for="accessories">Accesorios entregados</label><textarea id="accessories" name="accessories" rows="2" class="form-input" placeholder="Llanta de repuesto, gato, documentos...">{{ old('accessories') }}</textarea></div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label" for="damages">Daños y observaciones</label><textarea id="damages" name="damages" rows="3" class="form-input">{{ old('damages') }}</textarea></div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label" for="photos">Fotografías</label><input id="photos" type="file" name="photos[]" accept="image/*" multiple class="form-input"><p class="mt-2 text-xs text-slate-500">Hasta 5 MB por imagen. Requiere ejecutar <code>php artisan storage:link</code>.</p></div>
            </div>
        </section>
        <div class="mt-6 flex justify-end"><button class="btn-primary">Guardar inspección</button></div>
    </form>
</x-app-layout>
