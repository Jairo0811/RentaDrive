<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Usuarios</p><p class="text-xs text-slate-500">Acceso y roles</p></div></x-slot>
    <x-page-header title="Usuarios" subtitle="Administra cuentas, roles y estado de acceso."><x-slot name="actions"><a href="{{ route('users.create') }}" class="btn-primary">Nuevo usuario</a></x-slot></x-page-header>
    <form method="GET" class="panel mb-5 flex gap-3 p-4"><input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Nombre o correo"><button class="btn-secondary">Buscar</button></form>
    <div class="table-shell">
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th>Verificación</th><th>Creado</th><th></th></tr></thead><tbody>@foreach ($users as $user)<tr><td><p class="font-bold text-slate-900 dark:text-white">{{ $user->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $user->email }}</p></td><td>{{ $user->getRoleNames()->first() ?: 'Sin rol' }}</td><td><x-status-badge :status="$user->is_active ? 'active' : 'inactive'" /></td><td>{{ $user->email_verified_at ? 'Verificado' : 'Pendiente' }}</td><td>{{ $user->created_at->format('d/m/Y') }}</td><td><a href="{{ route('users.edit', $user) }}" class="font-bold text-blue-600">Editar</a></td></tr>@endforeach</tbody></table></div><div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $users->links() }}</div>
    </div>
</x-app-layout>
