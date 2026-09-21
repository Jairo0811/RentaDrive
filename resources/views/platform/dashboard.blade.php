<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">RentaDrive SaaS</p>
            <p class="text-xs text-slate-500">Administración global de plataforma</p>
        </div>
    </x-slot>

    <x-page-header title="Panel de plataforma" subtitle="Control global de empresas, sucursales y cuentas tenant.">
        <x-slot name="actions">
            <a href="{{ route('platform.companies.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Nueva empresa
            </a>
        </x-slot>
    </x-page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Métricas de plataforma">
        @foreach ([
            ['label' => 'Empresas', 'value' => $metrics['companies'], 'icon' => 'fa-building'],
            ['label' => 'Activas', 'value' => $metrics['active_companies'], 'icon' => 'fa-circle-check'],
            ['label' => 'Suspendidas', 'value' => $metrics['suspended_companies'], 'icon' => 'fa-circle-pause'],
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
                        <th>Sucursales</th>
                        <th>Usuarios</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestCompanies as $company)
                        <tr>
                            <td>
                                <p class="font-bold text-slate-900 dark:text-white">{{ $company->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $company->slug }}</p>
                            </td>
                            <td><x-status-badge :status="$company->status === 'active' ? 'active' : 'inactive'" /></td>
                            <td>{{ $company->branches_count }}</td>
                            <td>{{ $company->users_count }}</td>
                            <td><a href="{{ route('platform.companies.edit', $company) }}" class="font-bold text-blue-600">Administrar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="Sin empresas" message="Crea el primer tenant comercial de RentaDrive." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
