<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Opciones de reserva</p><p class="text-xs text-slate-500">Extras y seguros del portal público</p></div></x-slot>

    <x-page-header title="Extras y seguros" subtitle="Configura los productos opcionales que el cliente puede añadir a una reserva online.">
        <x-slot name="actions">
            <a href="{{ route('public-booking.show', auth()->user()->company->slug) }}" target="_blank" rel="noopener noreferrer" class="btn-secondary">Ver portal público</a>
        </x-slot>
    </x-page-header>

    <section class="grid gap-6 xl:grid-cols-[380px_1fr]">
        <form method="POST" action="{{ route('booking-options.store') }}" class="panel h-fit p-5">
            @csrf
            <h2 class="font-black text-slate-950 dark:text-white">Nueva opción</h2>
            <div class="mt-5 space-y-4">
                <div><label class="form-label" for="code">Código</label><input id="code" name="code" value="{{ old('code') }}" class="form-input" placeholder="GPS" required></div>
                <div><label class="form-label" for="name">Nombre</label><input id="name" name="name" value="{{ old('name') }}" class="form-input" placeholder="GPS portátil" required></div>
                <div><label class="form-label" for="type">Tipo</label><select id="type" name="type" class="form-input" required><option value="extra">Extra</option><option value="insurance">Seguro</option></select></div>
                <div><label class="form-label" for="pricing_mode">Cobro</label><select id="pricing_mode" name="pricing_mode" class="form-input" required><option value="flat">Precio único</option><option value="per_day">Por día</option></select></div>
                <div><label class="form-label" for="price">Precio RD$</label><input id="price" type="number" name="price" value="{{ old('price', 0) }}" min="0" step="0.01" class="form-input" required></div>
                <div><label class="form-label" for="description">Descripción</label><textarea id="description" name="description" rows="3" class="form-input">{{ old('description') }}</textarea></div>
                <input type="hidden" name="is_active" value="1">
                <button class="btn-primary w-full justify-center">Crear opción</button>
            </div>
        </form>

        <div class="space-y-4">
            @forelse ($options as $option)
                <article class="panel p-5">
                    <form method="POST" action="{{ route('booking-options.update', $option) }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                        @csrf @method('PUT')
                        <div><label class="form-label" for="code-{{ $option->id }}">Código</label><input id="code-{{ $option->id }}" name="code" value="{{ $option->code }}" class="form-input" required></div>
                        <div class="xl:col-span-2"><label class="form-label" for="name-{{ $option->id }}">Nombre</label><input id="name-{{ $option->id }}" name="name" value="{{ $option->name }}" class="form-input" required></div>
                        <div><label class="form-label" for="type-{{ $option->id }}">Tipo</label><select id="type-{{ $option->id }}" name="type" class="form-input"><option value="extra" @selected($option->type === 'extra')>Extra</option><option value="insurance" @selected($option->type === 'insurance')>Seguro</option></select></div>
                        <div><label class="form-label" for="mode-{{ $option->id }}">Cobro</label><select id="mode-{{ $option->id }}" name="pricing_mode" class="form-input"><option value="flat" @selected($option->pricing_mode === 'flat')>Único</option><option value="per_day" @selected($option->pricing_mode === 'per_day')>Por día</option></select></div>
                        <div><label class="form-label" for="price-{{ $option->id }}">Precio</label><input id="price-{{ $option->id }}" type="number" name="price" value="{{ $option->price }}" min="0" step="0.01" class="form-input" required></div>
                        <div class="md:col-span-2 xl:col-span-4"><label class="form-label" for="description-{{ $option->id }}">Descripción</label><input id="description-{{ $option->id }}" name="description" value="{{ $option->description }}" class="form-input"></div>
                        <div><label class="form-label" for="active-{{ $option->id }}">Estado</label><select id="active-{{ $option->id }}" name="is_active" class="form-input"><option value="1" @selected($option->is_active)>Activa</option><option value="0" @selected(! $option->is_active)>Inactiva</option></select></div>
                        <div class="flex items-end gap-2">
                            <button class="btn-primary flex-1 justify-center">Guardar</button>
                    </form>
                            <form method="POST" action="{{ route('booking-options.destroy', $option) }}" onsubmit="return confirm('¿Eliminar esta opción?')">
                                @csrf @method('DELETE')
                                <button class="btn-secondary text-red-600">Eliminar</button>
                            </form>
                        </div>
                </article>
            @empty
                <div class="panel p-10 text-center"><h2 class="font-black">Sin opciones configuradas</h2><p class="mt-2 text-sm text-slate-500">Crea extras o seguros para enriquecer la cotización pública.</p></div>
            @endforelse
        </div>
    </section>
</x-app-layout>
