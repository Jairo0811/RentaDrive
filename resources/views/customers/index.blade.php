<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Clientes</p>
            <p class="text-xs text-slate-500">Expedientes y actividad comercial</p>
        </div>
    </x-slot>

    <x-page-header title="Clientes" subtitle="Administra documentos, licencias y operaciones de cada cliente.">
        <x-slot name="actions">
            @can('manage customers')
                <a href="{{ route('customers.create') }}" class="btn-primary">Nuevo cliente</a>
            @endcan
        </x-slot>
    </x-page-header>

    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_180px_auto]">
        <input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Nombre, documento, correo o teléfono">
        <select name="status" class="form-input">
            <option value="">Todos los estados</option>
            <option value="active" @selected(request('status') === 'active')>Activos</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Suspendidos</option>
        </select>
        <button class="btn-secondary">Filtrar</button>
    </form>

    <div class="table-shell">
        @if ($customers->isEmpty())
            <x-empty-state title="Aún no hay clientes" message="Registra el primer cliente para comenzar una reserva." action="Nuevo cliente" :href="route('customers.create')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th>Contacto</th>
                            <th>Operaciones</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $customer->full_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $customer->city ?: 'Ciudad no indicada' }}</p>
                                </td>
                                <td>{{ $customer->document_number }}</td>
                                <td>
                                    <p>{{ $customer->phone }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $customer->email ?: 'Sin correo' }}</p>
                                </td>
                                <td>{{ $customer->rentals_count }} alquileres · {{ $customer->reservations_count }} reservas</td>
                                <td><x-status-badge :status="$customer->status" /></td>
                                <td class="text-right"><a href="{{ route('customers.show', $customer) }}" class="font-bold text-blue-600 hover:text-blue-500">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $customers->links() }}</div>
        @endif
    </div>
</x-app-layout>
