<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Configuración</p>
            <p class="text-xs text-slate-500">Negocio, booking, pagos y fiscalidad</p>
        </div>
    </x-slot>

    <x-page-header title="Configuración comercial" subtitle="Personaliza la marca pública, reglas de cotización y canales de confirmación." />

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Datos del negocio</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div><label class="form-label" for="business_name">Nombre comercial</label><input id="business_name" name="business_name" value="{{ old('business_name', $settings['business.name'] ?? $company->name) }}" class="form-input" required></div>
                <div><label class="form-label" for="business_rnc">RNC</label><input id="business_rnc" name="business_rnc" value="{{ old('business_rnc', $settings['business.rnc'] ?? $company->rnc) }}" class="form-input"></div>
                <div><label class="form-label" for="business_phone">Teléfono</label><input id="business_phone" name="business_phone" value="{{ old('business_phone', $settings['business.phone'] ?? $company->phone) }}" class="form-input"></div>
                <div><label class="form-label" for="business_email">Correo</label><input id="business_email" type="email" name="business_email" value="{{ old('business_email', $settings['business.email'] ?? $company->email) }}" class="form-input"></div>
                <div class="md:col-span-2"><label class="form-label" for="business_address">Dirección</label><input id="business_address" name="business_address" value="{{ old('business_address', $settings['business.address'] ?? '') }}" class="form-input"></div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">White-label público</h2>
            <p class="mt-1 text-sm text-slate-500">Estos datos se aplican al portal de reservas de tu empresa.</p>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="brand_primary_color">Color principal</label>
                    <input id="brand_primary_color" name="brand_primary_color" type="color" value="{{ old('brand_primary_color', $company->setting('branding.primary_color', '#0568f5')) }}" class="h-11 w-full rounded-xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900">
                </div>
                <div>
                    <label class="form-label" for="brand_accent_color">Color secundario</label>
                    <input id="brand_accent_color" name="brand_accent_color" type="color" value="{{ old('brand_accent_color', $company->setting('branding.accent_color', '#e2232e')) }}" class="h-11 w-full rounded-xl border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900">
                </div>
                <div>
                    <label class="form-label" for="brand_logo">Logo</label>
                    <input id="brand_logo" name="brand_logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="block w-full text-sm">
                    @if ($company->brandLogoUrl())
                        <img src="{{ $company->brandLogoUrl() }}" alt="Logo actual" class="mt-3 h-16 max-w-56 rounded-xl border border-slate-200 bg-white object-contain p-2 dark:border-slate-700">
                        <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_brand_logo" value="1"> Eliminar logo actual</label>
                    @endif
                </div>
                <div>
                    <label class="form-label" for="public_domain">Dominio público</label>
                    <input id="public_domain" name="public_domain" value="{{ old('public_domain', $company->public_domain) }}" class="form-input" placeholder="reservas.miempresa.com">
                    <p class="mt-2 text-xs text-slate-500">Sin https://. El DNS/TLS se configura al desplegar el SaaS.</p>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Tarifas y políticas</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                <div><label class="form-label" for="weekly_discount_percent">Descuento semanal (%)</label><input id="weekly_discount_percent" name="weekly_discount_percent" type="number" min="0" max="100" step="0.01" class="form-input" value="{{ old('weekly_discount_percent', $company->setting('booking.weekly_discount_percent', 5)) }}" required></div>
                <div><label class="form-label" for="monthly_discount_percent">Descuento mensual (%)</label><input id="monthly_discount_percent" name="monthly_discount_percent" type="number" min="0" max="100" step="0.01" class="form-input" value="{{ old('monthly_discount_percent', $company->setting('booking.monthly_discount_percent', 12)) }}" required></div>
                <div><label class="form-label" for="cancellation_hours">Límite de cancelación (horas)</label><input id="cancellation_hours" name="cancellation_hours" type="number" min="0" max="720" class="form-input" value="{{ old('cancellation_hours', $company->setting('booking.cancellation_hours', 24)) }}" required></div>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="seasonal_rules">Temporadas</label>
                    <textarea id="seasonal_rules" name="seasonal_rules" rows="6" class="form-input font-mono text-xs" placeholder="Navidad|2026-12-15|2027-01-10|1.20">{{ old('seasonal_rules', $company->setting('booking.seasonal_rules', '')) }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Formato: Nombre|inicio|fin|multiplicador.</p>
                </div>
                <div>
                    <label class="form-label" for="booking_extras">Extras y seguros</label>
                    <textarea id="booking_extras" name="booking_extras" rows="6" class="form-input font-mono text-xs" placeholder="GPS|GPS|300|per_day|extra&#10;FULL|Seguro Full|900|per_day|insurance">{{ old('booking_extras', $company->setting('booking.extras', '')) }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Código|Nombre|Precio|per_day/flat|extra/insurance.</p>
                </div>
                <div>
                    <label class="form-label" for="promo_codes">Promociones</label>
                    <textarea id="promo_codes" name="promo_codes" rows="6" class="form-input font-mono text-xs" placeholder="WELCOME10|percent|10|2026-01-01|2026-12-31">{{ old('promo_codes', $company->setting('booking.promo_codes', '')) }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Código|percent/fixed|valor|inicio|fin. Fechas opcionales.</p>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Pagos y depósitos</h2>
            <p class="mt-1 text-sm text-slate-500">Define cuánto se exige al reservar y cómo se procesa el checkout.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                <div>
                    <label class="form-label" for="payment_gateway">Pasarela</label>
                    <select id="payment_gateway" name="payment_gateway" class="form-input">
                        <option value="manual" @selected(old('payment_gateway', $company->setting('payments.gateway', 'manual')) === 'manual')>Manual</option>
                        <option value="hosted" @selected(old('payment_gateway', $company->setting('payments.gateway', 'manual')) === 'hosted')>Hosted adapter</option>
                    </select>
                    <p class="mt-2 text-xs text-slate-500">Hosted usa credenciales del entorno; nunca se guardan secretos aquí.</p>
                </div>
                <div>
                    <label class="form-label" for="deposit_type">Tipo de depósito</label>
                    <select id="deposit_type" name="deposit_type" class="form-input">
                        <option value="percent" @selected(old('deposit_type', $company->setting('payments.deposit_type', 'percent')) === 'percent')>Porcentaje</option>
                        <option value="fixed" @selected(old('deposit_type', $company->setting('payments.deposit_type', 'percent')) === 'fixed')>Monto fijo</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="deposit_value">Valor del depósito</label>
                    <input id="deposit_value" name="deposit_value" type="number" min="0" step="0.01" class="form-input" value="{{ old('deposit_value', $company->setting('payments.deposit_value', 20)) }}" required>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Dominican Edition · Fiscal</h2>
            <p class="mt-1 text-sm text-slate-500">NCF/e-CF por tenant. El ITBIS general permanece protegido en 18%.</p>

            <div class="mt-5 flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="fiscal_enabled" value="1" @checked(old('fiscal_enabled', $company->setting('fiscal.enabled', false)))>
                    Habilitar facturación fiscal
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="fiscal_authorized_electronic_issuer" value="1" @checked(old('fiscal_authorized_electronic_issuer', $company->setting('fiscal.authorized_electronic_issuer', false)))>
                    Emisor electrónico autorizado por DGII
                </label>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="fiscal_mode">Modalidad</label>
                    <select id="fiscal_mode" name="fiscal_mode" class="form-input">
                        <option value="electronic" @selected(old('fiscal_mode', $company->setting('fiscal.mode', 'electronic')) === 'electronic')>e-CF electrónico</option>
                        <option value="paper" @selected(old('fiscal_mode', $company->setting('fiscal.mode', 'electronic')) === 'paper')>NCF no electrónico</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="fiscal_rnc">RNC emisor</label>
                    <input id="fiscal_rnc" name="fiscal_rnc" inputmode="numeric" class="form-input" value="{{ old('fiscal_rnc', $company->setting('fiscal.rnc', $company->rnc)) }}" placeholder="9 dígitos">
                </div>
                <div>
                    <label class="form-label" for="fiscal_legal_name">Razón social</label>
                    <input id="fiscal_legal_name" name="fiscal_legal_name" class="form-input" value="{{ old('fiscal_legal_name', $company->setting('fiscal.legal_name', $company->legal_name ?: $company->name)) }}">
                </div>
                <div>
                    <label class="form-label" for="fiscal_address">Domicilio fiscal</label>
                    <input id="fiscal_address" name="fiscal_address" class="form-input" value="{{ old('fiscal_address', $company->setting('fiscal.address', '')) }}">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="fiscal_sequences">Secuencias autorizadas</label>
                    <textarea id="fiscal_sequences" name="fiscal_sequences" rows="6" class="form-input font-mono text-xs" placeholder="E31|1|9999999999|2027-12-31&#10;E32|1|9999999999|2027-12-31">{{ old('fiscal_sequences', $fiscalSequences) }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Formato: Tipo|próximo|final|vencimiento. Soporta B01, B02, E31 y E32. Solo carga secuencias realmente autorizadas.</p>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Automatizaciones</h2>
            <p class="mt-1 text-sm text-slate-500">Recordatorios operativos procesados por colas, con deduplicación por evento.</p>

            <div class="mt-5 flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="automation_email_enabled" value="1" @checked(old('automation_email_enabled', $company->setting('automation.email_enabled', true)))>
                    Email automático
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="automation_whatsapp_enabled" value="1" @checked(old('automation_whatsapp_enabled', $company->setting('automation.whatsapp_enabled', false)))>
                    WhatsApp automático
                </label>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="reservation_reminder_hours">Reserva antes de iniciar (horas)</label>
                    <input id="reservation_reminder_hours" name="reservation_reminder_hours" type="number" min="1" max="168" class="form-input" value="{{ old('reservation_reminder_hours', $company->setting('automation.reservation_reminder_hours', 24)) }}" required>
                </div>
                <div>
                    <label class="form-label" for="return_reminder_hours">Devolución próxima (horas)</label>
                    <input id="return_reminder_hours" name="return_reminder_hours" type="number" min="1" max="72" class="form-input" value="{{ old('return_reminder_hours', $company->setting('automation.return_reminder_hours', 4)) }}" required>
                </div>
                <div>
                    <label class="form-label" for="maintenance_reminder_days">Mantenimiento (días)</label>
                    <input id="maintenance_reminder_days" name="maintenance_reminder_days" type="number" min="1" max="90" class="form-input" value="{{ old('maintenance_reminder_days', $company->setting('automation.maintenance_reminder_days', 7)) }}" required>
                </div>
                <div>
                    <label class="form-label" for="document_reminder_days">Documentos (días)</label>
                    <input id="document_reminder_days" name="document_reminder_days" type="number" min="1" max="180" class="form-input" value="{{ old('document_reminder_days', $company->setting('automation.document_reminder_days', 30)) }}" required>
                </div>
            </div>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Operación y confirmaciones</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                <div>
                    <label class="form-label" for="currency">Moneda</label>
                    <select id="currency" name="currency" class="form-input">
                        <option value="DOP" @selected(old('currency', $company->currency) === 'DOP')>DOP — Peso dominicano</option>
                        <option value="USD" @selected(old('currency', $company->currency) === 'USD')>USD — Dólar estadounidense</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="tax_rate_display">ITBIS</label>
                    <input id="tax_rate_display" type="text" value="18%" class="form-input cursor-not-allowed bg-slate-100 text-slate-500 dark:bg-slate-800" readonly>
                </div>
                <div><label class="form-label" for="default_pickup_location">Ubicación predeterminada</label><input id="default_pickup_location" name="default_pickup_location" value="{{ old('default_pickup_location', $settings['operations.default_pickup_location'] ?? 'Oficina principal') }}" class="form-input" required></div>
            </div>

            <div class="mt-5 flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="email_confirmation_enabled" value="1" @checked(old('email_confirmation_enabled', $company->setting('booking.email_confirmation_enabled', true)))> Confirmación automática por email</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="whatsapp_confirmation_enabled" value="1" @checked(old('whatsapp_confirmation_enabled', $company->setting('booking.whatsapp_confirmation_enabled', false)))> Confirmación automática por WhatsApp</label>
            </div>
        </section>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                Revisa los campos marcados antes de guardar.
            </div>
        @endif

        <div class="flex justify-end"><button class="btn-primary">Guardar configuración</button></div>
    </form>
</x-app-layout>
