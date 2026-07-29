<x-app-layout>
    <x-slot name="header">
        <div><p class="text-lg font-black text-slate-950 dark:text-white">Catálogos de flota</p><p class="text-xs text-slate-500">Marcas, modelos y tarifas</p></div>
    </x-slot>

    <x-page-header title="Catálogos de flota" subtitle="Mantén centralizadas las opciones utilizadas por los vehículos.">
        <x-slot name="actions"><a href="{{ route('vehicles.index') }}" class="btn-secondary">Volver a flota</a></x-slot>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="panel p-5">
            <h2 class="font-black text-slate-950 dark:text-white">Marcas</h2>
            @can('manage vehicles')
                <form method="POST" action="{{ route('fleet.brands.store') }}" class="mt-4 flex gap-2">@csrf<input name="name" class="form-input" placeholder="Nueva marca" required><input type="hidden" name="is_active" value="1"><button class="btn-primary">Agregar</button></form>
            @endcan
            <div class="mt-5 space-y-3">
                @foreach ($brands as $brand)
                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                        @can('manage vehicles')
                            <form method="POST" action="{{ route('fleet.brands.update', $brand) }}" class="flex items-center gap-2">@csrf @method('PUT')<input name="name" value="{{ $brand->name }}" class="form-input"><select name="is_active" class="form-input max-w-28"><option value="1" @selected($brand->is_active)>Activo</option><option value="0" @selected(!$brand->is_active)>Inactivo</option></select><button class="font-bold text-blue-600">Guardar</button></form>
                            @if ($brand->models_count === 0)
                                <form method="POST" action="{{ route('fleet.brands.destroy', $brand) }}" class="mt-2 text-right" onsubmit="return confirm('¿Eliminar esta marca?')">@csrf @method('DELETE')<button class="text-xs font-bold text-red-600">Eliminar</button></form>
                            @endif
                        @else
                            <div class="flex justify-between"><span class="font-bold">{{ $brand->name }}</span><span class="text-xs text-slate-500">{{ $brand->models_count }} modelos</span></div>
                        @endcan
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel p-5 xl:col-span-2">
            <h2 class="font-black text-slate-950 dark:text-white">Categorías y tarifas</h2>
            @can('manage vehicles')
                <form method="POST" action="{{ route('fleet.categories.store') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <input name="code" class="form-input" placeholder="Código" required>
                    <input name="name" class="form-input" placeholder="Categoría" required>
                    <input type="number" step="0.01" name="daily_rate" class="form-input" placeholder="Tarifa diaria" required>
                    <input type="number" step="0.01" name="deposit_amount" class="form-input" placeholder="Depósito" required>
                    <textarea name="description" class="form-input md:col-span-2 xl:col-span-3" placeholder="Descripción"></textarea>
                    <button class="btn-primary">Agregar categoría</button>
                </form>
            @endcan
            <div class="mt-5 overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Código</th><th>Categoría</th><th>Tarifa</th><th>Depósito</th><th>Vehículos</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="font-bold">{{ $category->code }}</td><td>{{ $category->name }}</td><td>RD$ {{ number_format((float) $category->daily_rate, 2) }}</td><td>RD$ {{ number_format((float) $category->deposit_amount, 2) }}</td><td>{{ $category->vehicles_count }}</td><td><x-status-badge :status="$category->is_active ? 'active' : 'inactive'" /></td>
                                <td>
                                    @can('manage vehicles')
                                        <details class="relative">
                                            <summary class="cursor-pointer font-bold text-blue-600">Editar</summary>
                                            <form method="POST" action="{{ route('fleet.categories.update', $category) }}" class="absolute right-0 z-20 mt-2 w-80 space-y-2 rounded-xl border border-slate-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                                @csrf @method('PUT')
                                                <input name="code" value="{{ $category->code }}" class="form-input" required>
                                                <input name="name" value="{{ $category->name }}" class="form-input" required>
                                                <input type="number" step="0.01" name="daily_rate" value="{{ $category->daily_rate }}" class="form-input" required>
                                                <input type="number" step="0.01" name="deposit_amount" value="{{ $category->deposit_amount }}" class="form-input" required>
                                                <textarea name="description" class="form-input">{{ $category->description }}</textarea>
                                                <select name="is_active" class="form-input"><option value="1" @selected($category->is_active)>Activo</option><option value="0" @selected(!$category->is_active)>Inactivo</option></select>
                                                <button class="btn-primary w-full">Guardar</button>
                                            </form>
                                            @if ($category->vehicles_count === 0)
                                                <form method="POST" action="{{ route('fleet.categories.destroy', $category) }}" class="mt-2" onsubmit="return confirm('¿Eliminar esta categoría?')">@csrf @method('DELETE')<button class="text-xs font-bold text-red-600">Eliminar</button></form>
                                            @endif
                                        </details>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="panel mt-6 p-5">
        <h2 class="font-black text-slate-950 dark:text-white">Modelos</h2>
        @can('manage vehicles')
            <form method="POST" action="{{ route('fleet.models.store') }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_140px_auto]">
                @csrf
                <select name="vehicle_brand_id" class="form-input" required><option value="">Marca</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name }}</option>@endforeach</select>
                <input name="name" class="form-input" placeholder="Modelo" required>
                <input type="number" name="year" value="{{ now()->year }}" class="form-input" required>
                <button class="btn-primary">Agregar modelo</button>
            </form>
        @endcan
        <div class="mt-5 overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Marca</th><th>Modelo</th><th>Año</th><th>Vehículos</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @foreach ($models as $model)
                        <tr>
                            <td>{{ $model->brand->name }}</td><td class="font-bold">{{ $model->name }}</td><td>{{ $model->year }}</td><td>{{ $model->vehicles_count }}</td><td><x-status-badge :status="$model->is_active ? 'active' : 'inactive'" /></td>
                            <td>
                                @can('manage vehicles')
                                    <details class="relative">
                                        <summary class="cursor-pointer font-bold text-blue-600">Editar</summary>
                                        <form method="POST" action="{{ route('fleet.models.update', $model) }}" class="absolute right-0 z-20 mt-2 w-72 space-y-2 rounded-xl border border-slate-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                                            @csrf @method('PUT')
                                            <select name="vehicle_brand_id" class="form-input">@foreach ($brands as $brand)<option value="{{ $brand->id }}" @selected($model->vehicle_brand_id === $brand->id)>{{ $brand->name }}</option>@endforeach</select>
                                            <input name="name" value="{{ $model->name }}" class="form-input" required>
                                            <input type="number" name="year" value="{{ $model->year }}" class="form-input" required>
                                            <select name="is_active" class="form-input"><option value="1" @selected($model->is_active)>Activo</option><option value="0" @selected(!$model->is_active)>Inactivo</option></select>
                                            <button class="btn-primary w-full">Guardar</button>
                                        </form>
                                        @if ($model->vehicles_count === 0)
                                            <form method="POST" action="{{ route('fleet.models.destroy', $model) }}" class="mt-2" onsubmit="return confirm('¿Eliminar este modelo?')">@csrf @method('DELETE')<button class="text-xs font-bold text-red-600">Eliminar</button></form>
                                        @endif
                                    </details>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
