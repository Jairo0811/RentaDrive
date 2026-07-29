<x-app-layout>
    <x-slot name="header">
        <div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $vehicle->display_name }}</p><p class="text-xs text-slate-500">Ficha de flota</p></div>
    </x-slot>

    <x-page-header :title="$vehicle->model->display_name" :subtitle="$vehicle->code.' · '.$vehicle->plate">
        <x-slot name="actions">
            @can('manage vehicles')<a href="{{ route('vehicles.edit', $vehicle) }}" class="btn-secondary">Editar vehículo</a>@endcan
        </x-slot>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[.75fr_1.25fr]">
        <section class="panel p-5 sm:p-6">
            <div class="flex items-center justify-between"><h2 class="font-black text-slate-950 dark:text-white">Resumen</h2><x-status-badge :status="$vehicle->status" /></div>
            <dl class="mt-6 space-y-4 text-sm">
                @foreach ([
                    'Categoría' => $vehicle->category->name,
                    'Color' => $vehicle->color,
                    'Transmisión' => $vehicle->transmission === 'automatic' ? 'Automática' : 'Manual',
                    'Combustible' => ucfirst($vehicle->fuel_type),
                    'Asientos' => $vehicle->seats,
                    'Kilometraje' => number_format($vehicle->mileage).' km',
                    'Tarifa diaria' => 'RD$ '.number_format($vehicle->effective_daily_rate, 2),
                    'VIN' => $vehicle->vin ?: 'No indicado',
                    'Próximo mantenimiento' => $vehicle->next_maintenance_at ? number_format($vehicle->next_maintenance_at).' km' : 'No programado',
                ] as $label => $value)
                    <div class="flex justify-between gap-5 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800"><dt class="font-semibold text-slate-500">{{ $label }}</dt><dd class="text-right text-slate-800 dark:text-slate-200">{{ $value }}</dd></div>
                @endforeach
            </dl>
        </section>

        <div class="space-y-6">
            <section class="panel overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-black text-slate-950 dark:text-white">Historial de alquileres</h2></div>
                @if ($vehicle->rentals->isEmpty())
                    <x-empty-state title="Sin alquileres" message="La actividad de esta unidad aparecerá aquí." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Código</th><th>Cliente</th><th>Inicio</th><th>Total</th><th>Estado</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($vehicle->rentals as $rental)
                                    <tr>
                                        <td class="font-bold">{{ $rental->code }}</td><td>{{ $rental->customer->full_name }}</td><td>{{ $rental->start_at->format('d/m/Y') }}</td><td>RD$ {{ number_format((float) $rental->total, 2) }}</td><td><x-status-badge :status="$rental->status" /></td><td><a href="{{ route('rentals.show', $rental) }}" class="font-bold text-blue-600">Ver</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @can('manage vehicles')
                <section class="panel p-5 sm:p-6">
                    <h2 class="font-black text-slate-950 dark:text-white">Programar mantenimiento</h2>
                    <form method="POST" action="{{ route('maintenances.store') }}" class="mt-5 grid gap-4 md:grid-cols-2">
                        @csrf
                        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                        <div><label class="form-label" for="maintenance_type">Tipo</label><input id="maintenance_type" name="maintenance_type" class="form-input" placeholder="Cambio de aceite" required></div>
                        <div><label class="form-label" for="scheduled_at">Fecha programada</label><input id="scheduled_at" type="datetime-local" name="scheduled_at" class="form-input" required></div>
                        <div><label class="form-label" for="mileage">Kilometraje</label><input id="mileage" type="number" name="mileage" value="{{ $vehicle->mileage }}" class="form-input"></div>
                        <div><label class="form-label" for="provider">Proveedor</label><input id="provider" name="provider" class="form-input"></div>
                        <div><label class="form-label" for="cost">Costo</label><input id="cost" type="number" step="0.01" name="cost" value="0" class="form-input"></div>
                        <div><label class="form-label" for="status">Estado</label><select id="status" name="status" class="form-input"><option value="scheduled">Programado</option><option value="in_progress">En proceso</option><option value="completed">Completado</option><option value="cancelled">Cancelado</option></select></div>
                        <div class="md:col-span-2"><label class="form-label" for="description">Descripción</label><textarea id="description" name="description" rows="2" class="form-input"></textarea></div>
                        <div class="md:col-span-2"><button class="btn-primary">Guardar mantenimiento</button></div>
                    </form>
                </section>
            @endcan

            <section class="table-shell">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-black text-slate-950 dark:text-white">Mantenimientos</h2></div>
                @if ($vehicle->maintenances->isEmpty())
                    <x-empty-state title="Sin mantenimientos" message="No hay intervenciones registradas." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Tipo</th><th>Programado</th><th>Proveedor</th><th>Costo</th><th>Estado</th></tr></thead>
                            <tbody>
                                @foreach ($vehicle->maintenances as $maintenance)
                                    <tr><td class="font-bold">{{ $maintenance->maintenance_type }}</td><td>{{ $maintenance->scheduled_at->format('d/m/Y h:i A') }}</td><td>{{ $maintenance->provider ?: '—' }}</td><td>RD$ {{ number_format((float) $maintenance->cost, 2) }}</td><td><x-status-badge :status="$maintenance->status" /></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
