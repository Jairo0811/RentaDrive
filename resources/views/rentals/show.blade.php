<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $rental->code }}</p><p class="text-xs text-slate-500">Expediente de alquiler</p></div></x-slot>

    <x-page-header :title="'Alquiler '.$rental->code" :subtitle="$rental->customer->full_name">
        <x-slot name="actions">
            @can('manage contracts')<a href="{{ route('rentals.contract', $rental) }}" target="_blank" class="btn-secondary">Contrato imprimible</a>@endcan
            @if ($rental->invoice)<a href="{{ route('invoices.show', $rental->invoice) }}" class="btn-secondary">Ver factura</a>@endif
            @if ($rental->status === 'open')
                @can('manage inspections')<a href="{{ route('inspections.create', ['rental' => $rental->id]) }}" class="btn-primary">Nueva inspección</a>@endcan
            @endif
        </x-slot>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
        <div class="space-y-6">
            <section class="panel p-5 sm:p-6">
                <div class="flex items-center justify-between"><h2 class="font-black text-slate-950 dark:text-white">Operación</h2><x-status-badge :status="$rental->status" /></div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'Cliente' => $rental->customer->full_name,
                        'Vehículo' => $rental->vehicle->display_name,
                        'Categoría' => $rental->vehicle->category->name,
                        'Entrega' => $rental->start_at->format('d/m/Y h:i A'),
                        'Retorno esperado' => $rental->expected_return_at->format('d/m/Y h:i A'),
                        'Retorno real' => $rental->returned_at?->format('d/m/Y h:i A') ?: 'Pendiente',
                        'Kilometraje salida' => number_format($rental->opening_mileage).' km',
                        'Kilometraje retorno' => $rental->closing_mileage ? number_format($rental->closing_mileage).' km' : 'Pendiente',
                        'Combustible' => $rental->fuel_out.'%'.($rental->fuel_in !== null ? ' → '.$rental->fuel_in.'%' : ''),
                    ] as $label => $value)
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-950/50"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-2 font-semibold text-slate-800 dark:text-slate-200">{{ $value }}</p></div>
                    @endforeach
                </div>
            </section>

            <section class="table-shell">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-black text-slate-950 dark:text-white">Inspecciones</h2>@if ($rental->status === 'open')<span class="text-xs text-slate-500">Entrega y devolución</span>@endif</div>
                @if ($rental->inspections->isEmpty())
                    <x-empty-state title="Sin inspecciones" message="Documenta el estado del vehículo al entregar y devolver." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Tipo</th><th>Fecha</th><th>Kilometraje</th><th>Carrocería</th><th>Inspector</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($rental->inspections as $inspection)
                                    <tr><td><x-status-badge :status="$inspection->type" /></td><td>{{ $inspection->inspected_at->format('d/m/Y h:i A') }}</td><td>{{ number_format($inspection->mileage) }} km</td><td>{{ ucfirst($inspection->body_condition) }}</td><td>{{ $inspection->inspector?->name ?: 'Sistema' }}</td><td><a href="{{ route('inspections.show', $inspection) }}" class="font-bold text-blue-600">Ver</a></td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @if ($rental->status === 'open')
                @can('manage returns')
                    <section class="panel p-5 sm:p-6">
                        <h2 class="font-black text-slate-950 dark:text-white">Cerrar alquiler</h2>
                        <p class="mt-1 text-sm text-slate-500">Registra la devolución; la factura se recalculará con los días y cargos reales.</p>
                        <form method="POST" action="{{ route('rentals.close', $rental) }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @csrf @method('PATCH')
                            <div><label class="form-label" for="returned_at">Fecha de devolución</label><input id="returned_at" type="datetime-local" name="returned_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-input" required></div>
                            <div><label class="form-label" for="closing_mileage">Kilometraje final</label><input id="closing_mileage" type="number" min="{{ $rental->opening_mileage }}" name="closing_mileage" value="{{ $rental->vehicle->mileage }}" class="form-input" required></div>
                            <div><label class="form-label" for="fuel_in">Combustible final (%)</label><input id="fuel_in" type="number" step="0.01" min="0" max="100" name="fuel_in" value="{{ $rental->fuel_out }}" class="form-input" required></div>
                            <div><label class="form-label" for="fees">Cargos adicionales</label><input id="fees" type="number" step="0.01" min="0" name="fees" value="{{ $rental->fees }}" class="form-input"></div>
                            <div><label class="form-label" for="vehicle_status">Estado de la unidad</label><select id="vehicle_status" name="vehicle_status" class="form-input"><option value="available">Disponible</option><option value="maintenance">Enviar a mantenimiento</option></select></div>
                            <div class="md:col-span-2 xl:col-span-3"><label class="form-label" for="notes">Notas del cierre</label><textarea id="notes" name="notes" rows="2" class="form-input">{{ $rental->notes }}</textarea></div>
                            <div class="md:col-span-2 xl:col-span-3"><button class="btn-primary">Cerrar alquiler</button></div>
                        </form>
                    </section>
                @endcan
            @endif
        </div>

        <aside class="space-y-6">
            <section class="panel p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600">Resumen financiero</p>
                <dl class="mt-5 space-y-3 text-sm">
                    @foreach (['Subtotal' => $rental->subtotal, 'Cargos' => $rental->fees, 'Impuestos' => $rental->taxes] as $label => $value)
                        <div class="flex justify-between"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold text-slate-800 dark:text-slate-200">RD$ {{ number_format((float) $value, 2) }}</dd></div>
                    @endforeach
                    <div class="flex justify-between border-t border-slate-200 pt-4 dark:border-slate-800"><dt class="font-black text-slate-950 dark:text-white">Total</dt><dd class="text-xl font-black text-blue-600">RD$ {{ number_format((float) $rental->total, 2) }}</dd></div>
                </dl>
            </section>
            <section class="panel p-5 sm:p-6">
                <h2 class="font-black text-slate-950 dark:text-white">Responsables</h2>
                <div class="mt-4 space-y-3 text-sm"><p><span class="text-slate-500">Abierto por:</span> <strong>{{ $rental->opener?->name ?: 'Sistema' }}</strong></p><p><span class="text-slate-500">Cerrado por:</span> <strong>{{ $rental->closer?->name ?: 'Pendiente' }}</strong></p>@if ($rental->reservation)<p><span class="text-slate-500">Reserva:</span> <a href="{{ route('reservations.show', $rental->reservation) }}" class="font-bold text-blue-600">{{ $rental->reservation->code }}</a></p>@endif</div>
            </section>
        </aside>
    </div>
</x-app-layout>
