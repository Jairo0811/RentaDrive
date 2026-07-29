<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">{{ $customer->full_name }}</p>
            <p class="text-xs text-slate-500">Expediente del cliente</p>
        </div>
    </x-slot>

    <x-page-header :title="$customer->full_name" :subtitle="$customer->document_number">
        <x-slot name="actions">
            @can('manage reservations')
                <a href="{{ route('reservations.create', ['customer' => $customer->id]) }}" class="btn-primary">Nueva reserva</a>
            @endcan
            @can('manage customers')
                <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary">Editar</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[.75fr_1.25fr]">
        <section class="panel p-5 sm:p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-black text-slate-950 dark:text-white">Datos personales</h2>
                <x-status-badge :status="$customer->status" />
            </div>
            <dl class="mt-6 space-y-4 text-sm">
                @foreach ([
                    'Documento' => $customer->document_number,
                    'Teléfono' => $customer->phone,
                    'Correo' => $customer->email ?: 'No indicado',
                    'Licencia' => $customer->license_number ?: 'No indicada',
                    'Vencimiento' => $customer->license_expiry?->format('d/m/Y') ?: 'No indicado',
                    'Dirección' => collect([$customer->address, $customer->city])->filter()->join(', ') ?: 'No indicada',
                ] as $label => $value)
                    <div class="flex justify-between gap-5 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800">
                        <dt class="font-semibold text-slate-500">{{ $label }}</dt>
                        <dd class="text-right text-slate-800 dark:text-slate-200">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="space-y-6">
            <section class="table-shell">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-black text-slate-950 dark:text-white">Alquileres recientes</h2></div>
                @if ($customer->rentals->isEmpty())
                    <x-empty-state title="Sin alquileres" message="Este cliente todavía no tiene alquileres." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Código</th><th>Vehículo</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($customer->rentals as $rental)
                                    <tr>
                                        <td class="font-bold">{{ $rental->code }}</td>
                                        <td>{{ $rental->vehicle->display_name }}</td>
                                        <td>{{ $rental->start_at->format('d/m/Y') }}</td>
                                        <td><x-status-badge :status="$rental->status" /></td>
                                        <td><a href="{{ route('rentals.show', $rental) }}" class="font-bold text-blue-600">Ver</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="table-shell">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-black text-slate-950 dark:text-white">Facturas recientes</h2></div>
                @if ($customer->invoices->isEmpty())
                    <x-empty-state title="Sin facturas" message="Las facturas se generan al abrir un alquiler." />
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead><tr><th>Número</th><th>Total</th><th>Balance</th><th>Estado</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($customer->invoices as $invoice)
                                    <tr>
                                        <td class="font-bold">{{ $invoice->number }}</td>
                                        <td>RD$ {{ number_format((float) $invoice->total, 2) }}</td>
                                        <td>RD$ {{ number_format((float) $invoice->balance, 2) }}</td>
                                        <td><x-status-badge :status="$invoice->status" /></td>
                                        <td><a href="{{ route('invoices.show', $invoice) }}" class="font-bold text-blue-600">Ver</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
