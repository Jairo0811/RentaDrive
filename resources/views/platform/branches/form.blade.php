<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $branch->exists ? 'Editar sucursal' : 'Nueva sucursal' }}</p><p class="text-xs text-slate-500">{{ $company->name }}</p></div></x-slot>

    <x-page-header :title="$branch->exists ? 'Editar sucursal' : 'Crear sucursal'" :subtitle="'Ubicación operativa de '.$company->name.'.'">
        <x-slot name="actions"><a href="{{ route('platform.companies.branches.index', $company) }}" class="btn-secondary">Cancelar</a></x-slot>
    </x-page-header>

    <form method="POST" action="{{ $branch->exists ? route('platform.companies.branches.update', [$company, $branch]) : route('platform.companies.branches.store', $company) }}" class="panel mx-auto max-w-4xl p-5 sm:p-6">
        @csrf
        @if ($branch->exists) @method('PUT') @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div><label class="form-label" for="name">Nombre</label><input id="name" name="name" value="{{ old('name', $branch->name) }}" class="form-input" required></div>
            <div><label class="form-label" for="code">Código</label><input id="code" name="code" value="{{ old('code', $branch->code) }}" class="form-input" required></div>
            <div class="md:col-span-2"><label class="form-label" for="address">Dirección</label><input id="address" name="address" value="{{ old('address', $branch->address) }}" class="form-input"></div>
            <div><label class="form-label" for="city">Ciudad</label><input id="city" name="city" value="{{ old('city', $branch->city) }}" class="form-input"></div>
            <div><label class="form-label" for="phone">Teléfono</label><input id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-input"></div>
            <div class="md:col-span-2"><label class="form-label" for="email">Correo</label><input id="email" type="email" name="email" value="{{ old('email', $branch->email) }}" class="form-input"></div>
            <div class="md:col-span-2 flex justify-end"><button class="btn-primary">{{ $branch->exists ? 'Guardar cambios' : 'Crear sucursal' }}</button></div>
        </div>
    </form>
</x-app-layout>
