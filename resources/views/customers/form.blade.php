<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">{{ $customer->exists ? 'Editar cliente' : 'Nuevo cliente' }}</p>
            <p class="text-xs text-slate-500">Expediente del arrendatario</p>
        </div>
    </x-slot>

    <x-page-header :title="$customer->exists ? 'Editar cliente' : 'Registrar cliente'" subtitle="Completa la identidad, contacto y licencia de conducir.">
        <x-slot name="actions">
            <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="btn-secondary">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                Cancelar
            </a>
        </x-slot>
    </x-page-header>

    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="space-y-6">
        @csrf
        @if ($customer->exists) @method('PUT') @endif

        <section class="panel p-5 sm:p-6">
            <h2 class="flex items-center gap-2 font-black text-slate-950 dark:text-white">
                <i class="fa-solid fa-id-card text-blue-500" aria-hidden="true"></i>
                Identificación
            </h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="document_type">Tipo de documento</label>
                    <select id="document_type" name="document_type" class="form-input" required>
                        @foreach (['cedula' => 'Cédula', 'passport' => 'Pasaporte', 'rnc' => 'RNC', 'other' => 'Otro'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('document_type', $customer->document_type ?: 'cedula') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="document_number">Número</label>
                    <input
                        id="document_number"
                        name="document_number"
                        value="{{ old('document_number', $customer->document_number) }}"
                        class="form-input"
                        required
                        autocomplete="off"
                        aria-describedby="document_number_help"
                    >
                    <p id="document_number_help" class="mt-2 text-xs text-slate-500 dark:text-slate-400"></p>
                    <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
                </div>
                <div>
                    <label class="form-label" for="first_name">Nombres</label>
                    <input id="first_name" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="last_name">Apellidos</label>
                    <input id="last_name" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="birth_date">Fecha de nacimiento</label>
                    <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $customer->birth_date?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="status">Estado</label>
                    <select id="status" name="status" class="form-input" required>
                        <option value="active" @selected(old('status', $customer->status ?: 'active') === 'active')>Activo</option>
                        <option value="suspended" @selected(old('status', $customer->status) === 'suspended')>Suspendido</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="flex items-center gap-2 font-black text-slate-950 dark:text-white">
                <i class="fa-solid fa-address-book text-blue-500" aria-hidden="true"></i>
                Contacto y licencia
            </h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="phone">Teléfono</label>
                    <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="email">Correo electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="city">Ciudad</label>
                    <input id="city" name="city" value="{{ old('city', $customer->city) }}" class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="address">Dirección</label>
                    <input id="address" name="address" value="{{ old('address', $customer->address) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="license_number">Licencia</label>
                    <input id="license_number" name="license_number" value="{{ old('license_number', $customer->license_number) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="license_expiry">Vencimiento de licencia</label>
                    <input id="license_expiry" type="date" name="license_expiry" value="{{ old('license_expiry', $customer->license_expiry?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <label class="form-label" for="notes">Notas</label>
                    <textarea id="notes" name="notes" rows="3" class="form-input">{{ old('notes', $customer->notes) }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button class="btn-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                {{ $customer->exists ? 'Guardar cambios' : 'Registrar cliente' }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const typeField = document.querySelector('#document_type');
                const numberField = document.querySelector('#document_number');
                const helpText = document.querySelector('#document_number_help');

                if (! typeField || ! numberField || ! helpText) {
                    return;
                }

                const settings = {
                    cedula: {
                        minLength: 11,
                        maxLength: 11,
                        inputMode: 'numeric',
                        pattern: '[0-9]{11}',
                        placeholder: '00112345678',
                        help: 'Debe contener exactamente 11 dígitos, sin guiones.',
                    },
                    rnc: {
                        minLength: 9,
                        maxLength: 9,
                        inputMode: 'numeric',
                        pattern: '[0-9]{9}',
                        placeholder: '101234567',
                        help: 'Debe contener exactamente 9 dígitos.',
                    },
                    passport: {
                        minLength: 6,
                        maxLength: 20,
                        inputMode: 'text',
                        pattern: '[A-Za-z0-9]{6,20}',
                        placeholder: 'PA1234567',
                        help: 'Entre 6 y 20 caracteres alfanuméricos, sin espacios.',
                    },
                    other: {
                        minLength: 3,
                        maxLength: 30,
                        inputMode: 'text',
                        pattern: '.{3,30}',
                        placeholder: 'Número de documento',
                        help: 'Entre 3 y 30 caracteres.',
                    },
                };

                const applyDocumentRules = () => {
                    const rule = settings[typeField.value] ?? settings.other;

                    numberField.minLength = rule.minLength;
                    numberField.maxLength = rule.maxLength;
                    numberField.inputMode = rule.inputMode;
                    numberField.pattern = rule.pattern;
                    numberField.placeholder = rule.placeholder;
                    helpText.textContent = rule.help;

                    if (['cedula', 'rnc'].includes(typeField.value)) {
                        numberField.value = numberField.value.replace(/\D/g, '').slice(0, rule.maxLength);
                    } else if (typeField.value === 'passport') {
                        numberField.value = numberField.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, rule.maxLength);
                    } else {
                        numberField.value = numberField.value.slice(0, rule.maxLength);
                    }
                };

                typeField.addEventListener('change', applyDocumentRules);
                numberField.addEventListener('input', applyDocumentRules);
                applyDocumentRules();
            });
        </script>
    @endpush
</x-app-layout>
