<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Empresas</p><p class="text-xs text-slate-500">Tenants de RentaDrive</p></div></x-slot>

    <x-page-header title="Empresas" subtitle="Administra clientes SaaS, estado de servicio y sucursales.">
        <x-slot name="actions"><a href="{{ route('platform.companies.create') }}" class="btn-primary">Nueva empresa</a></x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_220px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Empresa, RNC o slug">
        <select name="status" class="form-input">
            <option value="">Todos los estados</option>
            <option value="active" @selected(request('status') === 'active')>Activas</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Suspendidas</option>
        </select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="table-shell">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Empresa</th><th>RNC</th><th>Estado</th><th>Sucursales</th><th>Usuarios</th><th>Moneda</th><th></th></tr></thead>
                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            <td><p class="font-bold text-slate-900 dark:text-white">{{ $company->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $company->slug }}</p></td>
                            <td>{{ $company->rnc ?: '—' }}</td>
                            <td><x-status-badge :status="$company->status === 'active' ? 'active' : 'inactive'" /></td>
                            <td>{{ $company->branches_count }}</td>
                            <td>{{ $company->users_count }}</td>
                            <td>{{ $company->currency }}</td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a href="{{ route('platform.companies.branches.index', $company) }}" class="font-bold text-slate-600 dark:text-slate-300">Sucursales</a>
                                    <a href="{{ route('platform.companies.edit', $company) }}" class="font-bold text-blue-600">Editar</a>
                                    @if ($company->status === 'active')
                                        <form method="POST" action="{{ route('platform.companies.suspend', $company) }}">@csrf @method('PATCH')<button class="font-bold text-amber-600">Suspender</button></form>
                                    @else
                                        <form method="POST" action="{{ route('platform.companies.activate', $company) }}">@csrf @method('PATCH')<button class="font-bold text-emerald-600">Reactivar</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="Sin empresas" message="No hay tenants que coincidan con el filtro." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $companies->links() }}</div>
    </div>
</x-app-layout>
