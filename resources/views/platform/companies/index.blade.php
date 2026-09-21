<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Empresas</p><p class="text-xs text-slate-500">Tenants de RentaDrive</p></div></x-slot>

    <x-page-header title="Empresas" subtitle="Administra clientes SaaS, planes, estado de servicio y sucursales.">
        <x-slot name="actions"><a href="{{ route('platform.companies.create') }}" class="btn-primary">Nueva empresa</a></x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_220px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Empresa, RNC o slug">
        <select name="status" class="form-input">
            <option value="">Todos los estados</option>
            <option value="trial" @selected(request('status') === 'trial')>Prueba</option>
            <option value="active" @selected(request('status') === 'active')>Activas</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Suspendidas</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Canceladas</option>
        </select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="table-shell">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Empresa</th><th>RNC</th><th>Estado</th><th>Plan</th><th>Sucursales</th><th>Usuarios</th><th>Moneda</th><th></th></tr></thead>
                <tbody>
                    @forelse ($companies as $company)
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
                            <td><p class="font-bold text-slate-900 dark:text-white">{{ $company->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $company->slug }}</p></td>
                            <td>{{ $company->rnc ?: '—' }}</td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                                @if ($company->status === 'trial' && $company->trial_ends_at)
                                    <p class="mt-1 text-[11px] text-slate-500">Hasta {{ $company->trial_ends_at->format('d/m/Y') }}</p>
                                @endif
                            </td>
                            <td>{{ $plan['name'] ?? $company->plan_code }}</td>
                            <td>{{ $company->branches_count }}</td>
                            <td>{{ $company->users_count }}</td>
                            <td>{{ $company->currency }}</td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a href="{{ route('platform.companies.branches.index', $company) }}" class="font-bold text-slate-600 dark:text-slate-300">Sucursales</a>
                                    <a href="{{ route('platform.companies.edit', $company) }}" class="font-bold text-blue-600">Editar</a>
                                    @if (in_array($company->status, ['active', 'trial'], true))
                                        <form method="POST" action="{{ route('platform.companies.suspend', $company) }}">@csrf @method('PATCH')<button class="font-bold text-amber-600">Suspender</button></form>
                                    @elseif ($company->status === 'suspended')
                                        <form method="POST" action="{{ route('platform.companies.activate', $company) }}">@csrf @method('PATCH')<button class="font-bold text-emerald-600">Reactivar</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state title="Sin empresas" message="No hay tenants que coincidan con el filtro." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $companies->links() }}</div>
    </div>
</x-app-layout>
