<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Inspecciones</p><p class="text-xs text-slate-500">Estado de entrega y devolución</p></div></x-slot>

    <x-page-header title="Inspecciones" subtitle="Documenta kilometraje, combustible, condiciones, daños y evidencias.">
        <x-slot name="actions"><a href="{{ route('inspections.create') }}" class="btn-primary">Nueva inspección</a></x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 flex flex-col gap-3 p-4 sm:flex-row">
        <select name="type" class="form-input sm:max-w-56"><option value="">Todos los tipos</option><option value="delivery" @selected(request('type') === 'delivery')>Entrega</option><option value="return" @selected(request('type') === 'return')>Devolución</option></select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="table-shell">
        @if ($inspections->isEmpty())
            <x-empty-state title="No hay inspecciones" message="Registra el estado de una unidad asociada a un alquiler." action="Nueva inspección" :href="route('inspections.create')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Tipo</th><th>Alquiler</th><th>Vehículo</th><th>Cliente</th><th>Fecha</th><th>Condición</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($inspections as $inspection)
                            <tr><td><x-status-badge :status="$inspection->type" /></td><td class="font-bold">{{ $inspection->rental->code }}</td><td>{{ $inspection->vehicle->display_name }}</td><td>{{ $inspection->rental->customer->full_name }}</td><td>{{ $inspection->inspected_at->format('d/m/Y h:i A') }}</td><td>{{ ucfirst($inspection->body_condition) }}</td><td><a href="{{ route('inspections.show', $inspection) }}" class="font-bold text-blue-600">Ver</a></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $inspections->links() }}</div>
        @endif
    </div>
</x-app-layout>
