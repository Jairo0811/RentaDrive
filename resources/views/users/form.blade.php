<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $user->exists ? 'Editar usuario' : 'Nuevo usuario' }}</p><p class="text-xs text-slate-500">Cuenta y permisos</p></div></x-slot>
    <x-page-header :title="$user->exists ? 'Editar usuario' : 'Crear usuario'" subtitle="Asigna un rol operativo y controla el acceso."><x-slot name="actions"><a href="{{ route('users.index') }}" class="btn-secondary">Cancelar</a></x-slot></x-page-header>
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="panel mx-auto max-w-3xl p-5 sm:p-6">
        @csrf @if ($user->exists) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label" for="name">Nombre</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-input" required></div>
            <div><label class="form-label" for="email">Correo</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required></div>
            <div><label class="form-label" for="role">Rol</label><select id="role" name="role" class="form-input" required><option value="">Selecciona</option>@foreach ($roles as $role)<option value="{{ $role->name }}" @selected(old('role', $user->getRoleNames()->first()) === $role->name)>{{ $role->name }}</option>@endforeach</select></div>
            <div><label class="form-label" for="is_active">Estado</label><select id="is_active" name="is_active" class="form-input"><option value="1" @selected((string) old('is_active', (int) ($user->is_active ?? true)) === '1')>Activo</option><option value="0" @selected((string) old('is_active', (int) ($user->is_active ?? true)) === '0')>Inactivo</option></select></div>
            <div><label class="form-label" for="password">Contraseña {{ $user->exists ? '(opcional)' : '' }}</label><input id="password" type="password" name="password" class="form-input" @required(!$user->exists)></div>
            <div><label class="form-label" for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-input" @required(!$user->exists)></div>
            <div class="md:col-span-2 flex justify-end"><button class="btn-primary">{{ $user->exists ? 'Guardar cambios' : 'Crear usuario' }}</button></div>
        </div>
    </form>
</x-app-layout>
