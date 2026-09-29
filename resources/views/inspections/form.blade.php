<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">
                {{ $mode === 'mobile' ? 'Check-in / Check-out móvil' : 'Nueva inspección' }}
            </p>
            <p class="text-xs text-slate-500">Evidencia digital del estado vehicular</p>
        </div>
    </x-slot>

    <x-page-header
        :title="$fixedType === 'delivery' ? 'Check-in de entrega' : ($fixedType === 'return' ? 'Check-out de devolución' : 'Registrar inspección')"
        subtitle="La evidencia quedará sellada y formará parte permanente del expediente del alquiler."
    >
        <x-slot name="actions"><a href="{{ route('inspections.index') }}" class="btn-secondary">Cancelar</a></x-slot>
    </x-page-header>

    <form method="POST" action="{{ route('inspections.store') }}" enctype="multipart/form-data" x-data="inspectionMobile()">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="latitude" x-ref="latitude" value="{{ old('latitude') }}">
        <input type="hidden" name="longitude" x-ref="longitude" value="{{ old('longitude') }}">

        <div class="space-y-6">
            <section class="panel p-5 sm:p-6">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="form-label" for="rental_id">Alquiler</label>
                        <select id="rental_id" name="rental_id" class="form-input" required @disabled($fixedType && $selectedRental)>
                            <option value="">Selecciona</option>
                            @foreach ($rentals as $rental)
                                <option value="{{ $rental->id }}" @selected((string) old('rental_id', $selectedRental) === (string) $rental->id)>
                                    {{ $rental->code }} · {{ $rental->customer->full_name }} · {{ $rental->vehicle->display_name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($fixedType && $selectedRental)
                            <input type="hidden" name="rental_id" value="{{ $selectedRental }}">
                        @endif
                    </div>

                    <div>
                        <label class="form-label" for="type">Tipo</label>
                        @if ($fixedType)
                            <input type="hidden" name="type" value="{{ $fixedType }}">
                            <input class="form-input cursor-not-allowed bg-slate-100 dark:bg-slate-800" value="{{ $fixedType === 'delivery' ? 'Entrega' : 'Devolución' }}" readonly>
                        @else
                            <select id="type" name="type" class="form-input">
                                <option value="delivery" @selected(old('type') === 'delivery')>Entrega</option>
                                <option value="return" @selected(old('type') === 'return')>Devolución</option>
                            </select>
                        @endif
                    </div>

                    <div><label class="form-label" for="inspected_at">Fecha</label><input id="inspected_at" type="datetime-local" name="inspected_at" value="{{ old('inspected_at', now()->format('Y-m-d\TH:i')) }}" class="form-input" required></div>
                    <div><label class="form-label" for="mileage">Kilometraje</label><input id="mileage" type="number" min="0" name="mileage" value="{{ old('mileage') }}" class="form-input" inputmode="numeric" required></div>
                    <div><label class="form-label" for="fuel_level">Combustible (%)</label><input id="fuel_level" type="number" min="0" max="100" step="0.01" name="fuel_level" value="{{ old('fuel_level', 100) }}" class="form-input" inputmode="decimal" required></div>

                    @foreach (['body_condition' => 'Carrocería', 'interior_condition' => 'Interior'] as $field => $label)
                        <div>
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <select id="{{ $field }}" name="{{ $field }}" class="form-input">
                                @foreach (['excellent' => 'Excelente', 'good' => 'Bueno', 'fair' => 'Regular', 'damaged' => 'Con daños'] as $value => $text)
                                    <option value="{{ $value }}" @selected(old($field, 'good') === $value)>{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach

                    <div>
                        <label class="form-label" for="tires_condition">Neumáticos</label>
                        <select id="tires_condition" name="tires_condition" class="form-input">
                            @foreach (['excellent' => 'Excelente', 'good' => 'Bueno', 'fair' => 'Regular', 'replace' => 'Reemplazar'] as $value => $text)
                                <option value="{{ $value }}" @selected(old('tires_condition', 'good') === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="panel p-5 sm:p-6">
                <h2 class="font-black text-slate-950 dark:text-white">Accesorios entregados</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'spare_tire' => 'Llanta de repuesto',
                        'jack' => 'Gato',
                        'warning_triangle' => 'Triángulo',
                        'documents' => 'Documentos',
                        'first_aid' => 'Botiquín',
                        'tools' => 'Herramientas',
                        'floor_mats' => 'Alfombras',
                        'charger' => 'Cargador',
                    ] as $value => $label)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                            <input type="checkbox" name="accessories_checklist[]" value="{{ $value }}" @checked(in_array($value, old('accessories_checklist', []), true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-4"><label class="form-label" for="accessories">Notas de accesorios</label><textarea id="accessories" name="accessories" rows="2" class="form-input" placeholder="Detalles adicionales...">{{ old('accessories') }}</textarea></div>
            </section>

            <section class="panel p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-black text-slate-950 dark:text-white">Daños</h2>
                        <p class="mt-1 text-sm text-slate-500">Registra hasta 12 hallazgos específicos.</p>
                    </div>
                    <button type="button" class="btn-secondary" @click="addDamage()">Agregar daño</button>
                </div>

                <div class="mt-5 space-y-3">
                    <template x-for="(damage, index) in damages" :key="index">
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-800 md:grid-cols-[1fr_160px_2fr_auto]">
                            <input name="damage_area[]" class="form-input" placeholder="Área: puerta derecha">
                            <select name="damage_severity[]" class="form-input">
                                <option value="minor">Menor</option>
                                <option value="moderate">Moderado</option>
                                <option value="major">Mayor</option>
                            </select>
                            <input name="damage_description[]" class="form-input" placeholder="Descripción del daño">
                            <button type="button" class="text-sm font-bold text-red-600" @click="removeDamage(index)">Quitar</button>
                        </div>
                    </template>
                </div>

                <div class="mt-4"><label class="form-label" for="damages">Observaciones generales</label><textarea id="damages" name="damages" rows="3" class="form-input">{{ old('damages') }}</textarea></div>
            </section>

            <section class="panel p-5 sm:p-6">
                <h2 class="font-black text-slate-950 dark:text-white">Evidencia fotográfica</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $mode === 'mobile' ? 'En el flujo móvil se exige al menos una fotografía.' : 'Adjunta fotografías cuando corresponda.' }}</p>
                <input
                    id="photos"
                    type="file"
                    name="photos[]"
                    accept="image/*"
                    capture="environment"
                    multiple
                    class="form-input mt-5"
                    @required($mode === 'mobile')
                >
                <p class="mt-2 text-xs text-slate-500">Hasta 12 imágenes, máximo 5 MB cada una.</p>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="button" class="btn-secondary" @click="captureLocation()">Capturar ubicación</button>
                    <span class="text-xs text-slate-500" x-text="locationStatus">Ubicación opcional.</span>
                </div>
            </section>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                Revisa la información antes de sellar la inspección.
            </div>
        @endif

        <div class="mt-6 flex justify-end">
            <button class="btn-primary px-6 py-3">Guardar y sellar evidencia</button>
        </div>
    </form>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('inspectionMobile', () => ({
                damages: [{}],
                locationStatus: 'Ubicación opcional.',
                addDamage() {
                    if (this.damages.length < 12) this.damages.push({});
                },
                removeDamage(index) {
                    this.damages.splice(index, 1);
                    if (this.damages.length === 0) this.damages.push({});
                },
                captureLocation() {
                    if (!navigator.geolocation) {
                        this.locationStatus = 'Geolocalización no disponible.';
                        return;
                    }

                    this.locationStatus = 'Obteniendo ubicación…';
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.$refs.latitude.value = position.coords.latitude;
                            this.$refs.longitude.value = position.coords.longitude;
                            this.locationStatus = 'Ubicación capturada.';
                        },
                        () => this.locationStatus = 'No fue posible obtener la ubicación.',
                        { enableHighAccuracy: true, timeout: 10000 },
                    );
                },
            }));
        });
    </script>
</x-app-layout>
