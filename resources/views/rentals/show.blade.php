<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">{{ $rental->code }}</p>
            <p class="text-xs text-slate-500">Expediente digital de alquiler</p>
        </div>
    </x-slot>

    <x-page-header :title="'Alquiler '.$rental->code" :subtitle="$rental->customer->full_name">
        <x-slot name="actions">
            @can('manage contracts')
                @if (!$rental->renterSignature)
                    <a href="{{ route('rentals.signature.create', $rental) }}" class="btn-primary">Firmar contrato</a>
                @endif
                <a href="{{ route('rentals.contract', $rental) }}" target="_blank" class="btn-secondary">Ver contrato</a>
            @endcan

            @if ($rental->status === 'open')
                @can('manage deliveries')
                    @if (!$deliveryInspection)
                        <a href="{{ route('rentals.check-in', $rental) }}" class="btn-secondary">Check-in móvil</a>
                    @endif
                @endcan
                @can('manage returns')
                    @if ($deliveryInspection && !$returnInspection)
                        <a href="{{ route('rentals.check-out', $rental) }}" class="btn-secondary">Check-out móvil</a>
                    @endif
                @endcan
            @endif

            @if ($rental->invoice)
                <a href="{{ route('invoices.show', $rental->invoice) }}" class="btn-secondary">Ver factura</a>
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
                        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-950/50">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                            <p class="mt-2 font-semibold text-slate-800 dark:text-slate-200">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel p-5 sm:p-6">
                <h2 class="font-black text-slate-950 dark:text-white">Digital Rental</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border p-4 {{ $rental->renterSignature ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20' }}">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Contrato</p>
                        <p class="mt-2 font-black">{{ $rental->renterSignature ? 'Firmado' : 'Pendiente' }}</p>
                        @if ($rental->renterSignature)
                            <p class="mt-1 text-xs text-slate-500">{{ $rental->renterSignature->signed_at->format('d/m/Y h:i A') }}</p>
                        @endif
                    </div>
                    <div class="rounded-xl border p-4 {{ $deliveryInspection?->isSealed() ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20' }}">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Check-in</p>
                        <p class="mt-2 font-black">{{ $deliveryInspection?->isSealed() ? 'Sellado' : 'Pendiente' }}</p>
                        @if ($deliveryInspection)<p class="mt-1 text-xs text-slate-500">{{ $deliveryInspection->inspected_at->format('d/m/Y h:i A') }}</p>@endif
                    </div>
                    <div class="rounded-xl border p-4 {{ $returnInspection?->isSealed() ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20' }}">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Check-out</p>
                        <p class="mt-2 font-black">{{ $returnInspection?->isSealed() ? 'Sellado' : 'Pendiente' }}</p>
                        @if ($returnInspection)<p class="mt-1 text-xs text-slate-500">{{ $returnInspection->inspected_at->format('d/m/Y h:i A') }}</p>@endif
                    </div>
                </div>
            </section>

            <section class="table-shell">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h2 class="font-black text-slate-950 dark:text-white">Inspecciones selladas</h2>
                    <span class="text-xs text-slate-500">Entrega y devolución</span>
                </div>
                @if ($rental->inspections->isEmpty())
                    <x-empty-state title="Sin inspecciones" message="Documenta el estado del vehículo al entregar y devolver." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Tipo</th><th>Fecha</th><th>Kilometraje</th><th>Combustible</th><th>Sello</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($rental->inspections as $inspection)
                                    <tr>
                                        <td><x-status-badge :status="$inspection->type" /></td>
                                        <td>{{ $inspection->inspected_at->format('d/m/Y h:i A') }}</td>
                                        <td>{{ number_format($inspection->mileage) }} km</td>
                                        <td>{{ $inspection->fuel_level }}%</td>
                                        <td>{{ $inspection->isSealed() ? 'Sí' : 'No' }}</td>
                                        <td><a href="{{ route('inspections.show', $inspection) }}" class="font-bold text-blue-600">Ver evidencia</a></td>
                                    </tr>
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
                        @if (!$rental->renterSignature || !$deliveryInspection?->isSealed() || !$returnInspection?->isSealed())
                            <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">Para cerrar se requiere contrato firmado, check-in sellado y check-out sellado.</p>
                        @else
                            <p class="mt-2 text-sm text-slate-500">El kilometraje, combustible y hora de devolución se tomarán del check-out sellado.</p>
                            <form method="POST" action="{{ route('rentals.close', $rental) }}" class="mt-5 grid gap-4 md:grid-cols-2">
                                @csrf @method('PATCH')
                                <div><label class="form-label" for="fees">Cargos adicionales</label><input id="fees" type="number" step="0.01" min="0" name="fees" value="{{ $rental->fees }}" class="form-input"></div>
                                <div><label class="form-label" for="vehicle_status">Estado de la unidad</label><select id="vehicle_status" name="vehicle_status" class="form-input"><option value="available">Disponible</option><option value="maintenance">Enviar a mantenimiento</option></select></div>
                                <div class="md:col-span-2"><label class="form-label" for="notes">Notas del cierre</label><textarea id="notes" name="notes" rows="2" class="form-input">{{ $rental->notes }}</textarea></div>
                                <div class="md:col-span-2"><button class="btn-primary">Cerrar con evidencia digital</button></div>
                            </form>
                        @endif
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

            @if ($rental->renterSignature)
                <section class="panel p-5 sm:p-6">
                    <h2 class="font-black text-slate-950 dark:text-white">Sello contractual</h2>
                    <p class="mt-3 text-sm font-bold">{{ $rental->renterSignature->signer_name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $rental->renterSignature->signed_at->format('d/m/Y h:i A') }}</p>
                    <p class="mt-4 break-all font-mono text-[11px] text-slate-400">{{ $rental->renterSignature->contract_hash }}</p>
                </section>
            @endif

            <section class="panel p-5 sm:p-6">
                <h2 class="font-black text-slate-950 dark:text-white">Responsables</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <p><span class="text-slate-500">Abierto por:</span> <strong>{{ $rental->opener?->name ?: 'Sistema' }}</strong></p>
                    <p><span class="text-slate-500">Cerrado por:</span> <strong>{{ $rental->closer?->name ?: 'Pendiente' }}</strong></p>
                    @if ($rental->reservation)<p><span class="text-slate-500">Reserva:</span> <a href="{{ route('reservations.show', $rental->reservation) }}" class="font-bold text-blue-600">{{ $rental->reservation->code }}</a></p>@endif
                </div>
            </section>
        </aside>
    </div>
</x-app-layout>
