<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Configuración</p><p class="text-xs text-slate-500">Datos del negocio y facturación</p></div></x-slot>
    <x-page-header title="Configuración general" subtitle="Personaliza la empresa, moneda, impuestos y ubicación predeterminada." />
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf @method('PUT')
        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Datos del negocio</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div><label class="form-label" for="business_name">Nombre comercial</label><input id="business_name" name="business_name" value="{{ old('business_name', $settings['business.name'] ?? 'RentaDrive') }}" class="form-input" required></div>
                <div><label class="form-label" for="business_rnc">RNC</label><input id="business_rnc" name="business_rnc" value="{{ old('business_rnc', $settings['business.rnc'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label" for="business_phone">Teléfono</label><input id="business_phone" name="business_phone" value="{{ old('business_phone', $settings['business.phone'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label" for="business_email">Correo</label><input id="business_email" type="email" name="business_email" value="{{ old('business_email', $settings['business.email'] ?? '') }}" class="form-input"></div>
                <div class="md:col-span-2"><label class="form-label" for="business_address">Dirección</label><input id="business_address" name="business_address" value="{{ old('business_address', $settings['business.address'] ?? '') }}" class="form-input"></div>
            </div>
        </section>
        <section class="panel p-5 sm:p-6">
            <h2 class="font-black text-slate-950 dark:text-white">Operación y facturación</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                <div><label class="form-label" for="currency">Moneda</label><select id="currency" name="currency" class="form-input"><option value="DOP" @selected(($settings['billing.currency'] ?? 'DOP') === 'DOP')>DOP — Peso dominicano</option><option value="USD" @selected(($settings['billing.currency'] ?? '') === 'USD')>USD — Dólar estadounidense</option></select></div>
                <div><label class="form-label" for="tax_rate">Impuesto (%)</label><input id="tax_rate" type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $settings['billing.tax_rate'] ?? '18') }}" class="form-input" required></div>
                <div><label class="form-label" for="default_pickup_location">Ubicación predeterminada</label><input id="default_pickup_location" name="default_pickup_location" value="{{ old('default_pickup_location', $settings['operations.default_pickup_location'] ?? 'Oficina principal') }}" class="form-input" required></div>
            </div>
        </section>
        <div class="flex justify-end"><button class="btn-primary">Guardar configuración</button></div>
    </form>
</x-app-layout>
