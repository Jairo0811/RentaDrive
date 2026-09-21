<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">RentaDrive SaaS</p>
            <p class="text-xs text-slate-500">Administración global de plataforma</p>
        </div>
    </x-slot>

    <x-page-header title="Panel de plataforma" subtitle="Control global de empresas, sucursales, planes y cuentas tenant.">
        <x-slot name="actions">
            <a href="{{ route('platform.companies.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Nueva empresa
            </a>
        </x-slot>
    </x-page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6" aria-label="Métricas de plataforma">
        @foreach ([
            ['label' => 'Empresas', 'value' => $metrics['companies'], 'icon' => 'fa-building'],
            ['label' => 'En prueba', 'value' => $metrics['trial_companies'], 'icon' => 'fa-hourglass-half'],
            ['label' => 'Activas', 'value' => $metrics['active_companies'], 'icon' => 'fa-circle-check'],
            ['label' => 'Bloqueadas', 'value' => $metrics['blocked_companies'], 'icon' => 'fa-ban'],
            ['label' => 'Sucursales activas', 'value' => $metrics['active_branches'], 'icon' => 'fa-location-dot'],
            ['label' => 'Usuarios tenant', 'value' => $metrics['tenant_users'], 'icon' => 'fa-users'],
        ] as $card)
            <article class="panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                    </div>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300">
                        <i class="fa-solid {{ $card['icon'] }}" aria-hidden="true"></i>
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="panel mt-6 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-950 dark:text-white">Empresas recientes</h2>
                <p class="mt-1 text-sm text-slate-500">Últimos tenants incorporados a RentaDrive.</p>
            </div>
            <a href="{{ route('platform.companies.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400">Ver todas</a>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Estado</th>
                        <th>Plan</th>
                        <th>Sucursales</th>
                        <th>Usuarios</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestCompanies as $company)
                        @php
                            $statusLabel = match ($company->status) {
                                'trial' => 'Prueba',
                                'active' => 'Activa',
                                'suspended' => 'Suspendida',
                                'cancelled' => 'Cancelada',
                                default => $company->status,
                            };
                            $statusClass = match ($company->status) {
                                'trial' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
                                'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
                                'suspended' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
                                'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            };
                            $plan = config('rentadrive.plans.'.$company->plan_code);
                        @endphp
                        <tr>
                            <td>
                                <p class="font-bold text-slate-900 dark:text-white">{{ $company->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $company->slug }}</p>
                            </td>
                            <td><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $plan['name'] ?? $company->plan_code }}</td>
                            <td>{{ $company->branches_count }}</td>
                            <td>{{ $company->users_count }}</td>
                            <td><a href="{{ route('platform.companies.edit', $company) }}" class="font-bold text-blue-600">Administrar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="Sin empresas" message="Crea el primer tenant comercial de RentaDrive." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
