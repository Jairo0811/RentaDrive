<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $company->exists ? 'Editar empresa' : 'Nueva empresa' }}</p><p class="text-xs text-slate-500">{{ $company->exists ? 'Configuración del tenant' : 'Onboarding comercial' }}</p></div></x-slot>

    <x-page-header :title="$company->exists ? 'Editar empresa' : 'Crear empresa'" :subtitle="$company->exists ? 'Actualiza perfil, plan y ciclo de vida del tenant.' : 'Provisiona empresa, sucursal principal y administrador en una sola operación.'">
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
                <p class="mt-1 text-sm text-slate-500">Identidad, plan comercial y configuración base.</p>
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

                <div>
                    <label class="form-label" for="plan_code">Plan</label>
                    <select id="plan_code" name="plan_code" class="form-input" required>
                        @foreach (config('rentadrive.plans') as $code => $plan)
                            <option value="{{ $code }}" @selected(old('plan_code', $company->plan_code ?: 'starter') === $code)>
                                {{ $plan['name'] }} · {{ $plan['max_vehicles'] }} vehículos · {{ $plan['max_users'] }} usuarios · {{ $plan['max_branches'] }} sucursales
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="status">Estado comercial</label>
                    <select id="status" name="status" class="form-input" required>
                        @if (! $company->exists)
                            <option value="trial" @selected(old('status', 'trial') === 'trial')>Prueba</option>
                            <option value="active" @selected(old('status') === 'active')>Activa</option>
                        @else
                            @foreach (['trial' => 'Prueba', 'active' => 'Activa', 'suspended' => 'Suspendida', 'cancelled' => 'Cancelada'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $company->status) === $value)>{{ $label }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                @if ($company->exists)
                    <div class="md:col-span-2">
                        <label class="form-label" for="trial_ends_at">Vencimiento de prueba</label>
                        <input id="trial_ends_at" type="datetime-local" name="trial_ends_at" value="{{ old('trial_ends_at', $company->trial_ends_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                        <p class="mt-1 text-xs text-slate-500">Obligatorio cuando el estado sea Prueba.</p>
                    </div>
                @endif
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

    @if ($company->exists)
        @php($subscription = $company->subscriptions()->latest('started_at')->latest('id')->first())
        <section class="panel mt-6 p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600">Suscripción SaaS</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950 dark:text-white">
                        {{ $subscription ? strtoupper($subscription->status) : 'Sin suscripción' }}
                    </h2>
                    @if ($subscription)
                        <p class="mt-1 text-sm text-slate-500">
                            {{ config('rentadrive.plans.'.$subscription->plan_code.'.name', $subscription->plan_code) }}
                            · {{ $subscription->billing_cycle === 'yearly' ? 'Anual' : 'Mensual' }}
                            · {{ ucfirst($subscription->provider) }}
                        </p>
                    @endif
                </div>

                @if ($subscription?->trial_ends_at)
                    <p class="text-sm text-slate-500">Trial hasta {{ $subscription->trial_ends_at->format('d/m/Y h:i A') }}</p>
                @elseif ($subscription?->current_period_ends_at)
                    <p class="text-sm text-slate-500">Período hasta {{ $subscription->current_period_ends_at->format('d/m/Y h:i A') }}</p>
                @elseif ($subscription?->grace_ends_at)
                    <p class="text-sm text-amber-600">Gracia hasta {{ $subscription->grace_ends_at->format('d/m/Y h:i A') }}</p>
                @endif
            </div>

            <form method="POST" action="{{ route('platform.companies.subscription.activate', $company) }}" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @csrf
                <div>
                    <label class="form-label" for="subscription_plan_code">Plan</label>
                    <select id="subscription_plan_code" name="plan_code" class="form-input">
                        @foreach (config('rentadrive.plans') as $code => $plan)
                            <option value="{{ $code }}" @selected(($subscription?->plan_code ?? $company->plan_code) === $code)>{{ $plan['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="billing_cycle">Ciclo</label>
                    <select id="billing_cycle" name="billing_cycle" class="form-input">
                        <option value="monthly">Mensual</option>
                        <option value="yearly">Anual</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="provider">Proveedor</label>
                    <select id="provider" name="provider" class="form-input">
                        <option value="manual">Manual</option>
                        <option value="external">Externo</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="provider_reference">Referencia externa</label>
                    <input id="provider_reference" name="provider_reference" class="form-input" value="{{ $subscription?->provider_reference }}">
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <button class="btn-primary">Activar / renovar suscripción</button>
                </div>
            </form>

            @if ($subscription && in_array($subscription->status, ['active', 'trialing'], true))
                <div class="mt-5 flex flex-wrap gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                    <form method="POST" action="{{ route('platform.companies.subscription.past-due', $company) }}" class="flex items-end gap-3">
                        @csrf
                        <div>
                            <label class="form-label" for="grace_days">Días de gracia</label>
                            <input id="grace_days" type="number" min="1" max="30" name="grace_days" value="7" class="form-input w-28">
                        </div>
                        <button class="btn-secondary">Marcar en mora</button>
                    </form>

                    <form method="POST" action="{{ route('platform.companies.subscription.cancel', $company) }}" class="self-end">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex rounded-xl border border-red-200 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950/30">Cancelar suscripción</button>
                    </form>
                </div>
            @endif
        </section>
    @endif
</x-app-layout>
