<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $company->exists ? 'Editar empresa' : 'Nueva empresa' }}</p><p class="text-xs text-slate-500">{{ $company->exists ? 'Configuración del tenant' : 'Onboarding comercial' }}</p></div></x-slot>

    <x-page-header :title="$company->exists ? 'Editar empresa' : 'Crear empresa'" :subtitle="$company->exists ? 'Actualiza la información comercial del tenant.' : 'Provisiona empresa, sucursal principal y administrador en una sola operación.'">
        <x-slot name="actions">
            @if ($company->exists)
                <a href="{{ route('platform.companies.branches.index', $company) }}" class="btn-secondary">Sucursales</a>
            @endif
            <a href="{{ route('platform.companies.index') }}" class="btn-secondary">Volver</a>
        </x-slot>
    </x-page-header>

    <form method="POST" action="{{ $company->exists ? route('platform.companies.update', $company) : route('platform.companies.store') }}" class="space-y-6">
        @csrf
        @if ($company->exists) @method('PUT') @endif

        <section class="panel p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="font-black text-slate-950 dark:text-white">Empresa</h2>
                <p class="mt-1 text-sm text-slate-500">Identidad y configuración base.</p>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="form-label" for="name">Nombre comercial</label><input id="name" name="name" value="{{ old('name', $company->name) }}" class="form-input" required></div>
                <div><label class="form-label" for="legal_name">Razón social</label><input id="legal_name" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="form-input"></div>
                <div><label class="form-label" for="rnc">RNC</label><input id="rnc" name="rnc" value="{{ old('rnc', $company->rnc) }}" class="form-input"></div>
                <div><label class="form-label" for="slug">Slug</label><input id="slug" name="slug" value="{{ old('slug', $company->slug) }}" class="form-input" placeholder="mi-rent-a-car"></div>
                <div><label class="form-label" for="email">Correo comercial</label><input id="email" type="email" name="email" value="{{ old('email', $company->email) }}" class="form-input"></div>
                <div><label class="form-label" for="phone">Teléfono</label><input id="phone" name="phone" value="{{ old('phone', $company->phone) }}" class="form-input"></div>
                <div><label class="form-label" for="currency">Moneda</label><input id="currency" name="currency" value="{{ old('currency', $company->currency ?: 'DOP') }}" class="form-input" maxlength="3" required></div>
                <div><label class="form-label" for="timezone">Zona horaria</label><input id="timezone" name="timezone" value="{{ old('timezone', $company->timezone ?: 'America/Santo_Domingo') }}" class="form-input" required></div>
            </div>
        </section>

        @unless ($company->exists)
            <section class="panel p-5 sm:p-6">
                <div class="mb-5"><h2 class="font-black text-slate-950 dark:text-white">Sucursal principal</h2><p class="mt-1 text-sm text-slate-500">Punto operativo inicial del nuevo tenant.</p></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div><label class="form-label" for="branch_name">Nombre</label><input id="branch_name" name="branch_name" value="{{ old('branch_name', 'Sucursal Principal') }}" class="form-input" required></div>
                    <div><label class="form-label" for="branch_code">Código</label><input id="branch_code" name="branch_code" value="{{ old('branch_code', 'PRINCIPAL') }}" class="form-input" required></div>
                    <div class="md:col-span-2"><label class="form-label" for="branch_address">Dirección</label><input id="branch_address" name="branch_address" value="{{ old('branch_address') }}" class="form-input"></div>
                    <div><label class="form-label" for="branch_city">Ciudad</label><input id="branch_city" name="branch_city" value="{{ old('branch_city') }}" class="form-input"></div>
                    <div><label class="form-label" for="branch_phone">Teléfono</label><input id="branch_phone" name="branch_phone" value="{{ old('branch_phone') }}" class="form-input"></div>
                    <div class="md:col-span-2"><label class="form-label" for="branch_email">Correo</label><input id="branch_email" type="email" name="branch_email" value="{{ old('branch_email') }}" class="form-input"></div>
                </div>
            </section>

            <section class="panel p-5 sm:p-6">
                <div class="mb-5"><h2 class="font-black text-slate-950 dark:text-white">Administrador inicial</h2><p class="mt-1 text-sm text-slate-500">Primera cuenta administradora de la empresa.</p></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div><label class="form-label" for="admin_name">Nombre</label><input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" class="form-input" required></div>
                    <div><label class="form-label" for="admin_email">Correo</label><input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" class="form-input" required></div>
                    <div><label class="form-label" for="admin_password">Contraseña temporal</label><input id="admin_password" type="password" name="admin_password" class="form-input" required></div>
                    <div><label class="form-label" for="admin_password_confirmation">Confirmar contraseña</label><input id="admin_password_confirmation" type="password" name="admin_password_confirmation" class="form-input" required></div>
                </div>
            </section>
        @endunless

        <div class="flex justify-end">
            <button class="btn-primary">{{ $company->exists ? 'Guardar cambios' : 'Provisionar empresa' }}</button>
        </div>
    </form>
</x-app-layout>
