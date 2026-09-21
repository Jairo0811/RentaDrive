<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Sucursales</p><p class="text-xs text-slate-500">{{ $company->name }}</p></div></x-slot>

    <x-page-header title="Sucursales" :subtitle="'Administra ubicaciones operativas de '.$company->name.'.'">
        <x-slot name="actions">
            <a href="{{ route('platform.companies.edit', $company) }}" class="btn-secondary">Empresa</a>
            <a href="{{ route('platform.companies.branches.create', $company) }}" class="btn-primary">Nueva sucursal</a>
        </x-slot>
    </x-page-header>

    <div class="table-shell">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Sucursal</th><th>Código</th><th>Ciudad</th><th>Usuarios</th><th>Tipo</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @foreach ($branches as $branch)
                        <tr>
                            <td><p class="font-bold text-slate-900 dark:text-white">{{ $branch->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $branch->address ?: 'Sin dirección' }}</p></td>
                            <td>{{ $branch->code }}</td>
                            <td>{{ $branch->city ?: '—' }}</td>
                            <td>{{ $branch->users_count }}</td>
                            <td>{{ $branch->is_primary ? 'Principal' : 'Secundaria' }}</td>
                            <td><x-status-badge :status="$branch->is_active ? 'active' : 'inactive'" /></td>
                            <td>
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('platform.companies.branches.edit', [$company, $branch]) }}" class="font-bold text-blue-600">Editar</a>
                                    <form method="POST" action="{{ route('platform.companies.branches.status', [$company, $branch]) }}">@csrf @method('PATCH')
                                        <button class="font-bold {{ $branch->is_active ? 'text-amber-600' : 'text-emerald-600' }}">{{ $branch->is_active ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                    @unless ($branch->is_primary)
                                        <form method="POST" action="{{ route('platform.companies.branches.destroy', [$company, $branch]) }}" onsubmit="return confirm('¿Eliminar esta sucursal? Solo es posible si no tiene información relacionada.')">
                                            @csrf @method('DELETE')
                                            <button class="font-bold text-red-600">Eliminar</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
