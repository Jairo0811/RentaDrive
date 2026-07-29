<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Facturación</p><p class="text-xs text-slate-500">Cargos y cuentas por cobrar</p></div></x-slot>
    <x-page-header title="Facturas" subtitle="Consulta totales, pagos aplicados y balances pendientes." />
    <form method="GET" class="panel mb-5 grid gap-3 p-4 sm:grid-cols-[1fr_190px_auto]"><input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Número o cliente"><select name="status" class="form-input"><option value="">Todos los estados</option>@foreach (['pending' => 'Pendiente', 'partial' => 'Pago parcial', 'paid' => 'Pagada', 'cancelled' => 'Cancelada'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form>
    <div class="table-shell">
        @if ($invoices->isEmpty())<x-empty-state title="No hay facturas" message="Las facturas se generan automáticamente al abrir alquileres." />
        @else
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Factura</th><th>Cliente</th><th>Emisión</th><th>Total</th><th>Pagado</th><th>Balance</th><th>Estado</th><th></th></tr></thead><tbody>@foreach ($invoices as $invoice)<tr><td class="font-bold">{{ $invoice->number }}</td><td>{{ $invoice->customer->full_name }}</td><td>{{ $invoice->issued_at->format('d/m/Y') }}</td><td>RD$ {{ number_format((float) $invoice->total, 2) }}</td><td>RD$ {{ number_format((float) $invoice->paid_amount, 2) }}</td><td class="font-bold">RD$ {{ number_format((float) $invoice->balance, 2) }}</td><td><x-status-badge :status="$invoice->status" /></td><td><a href="{{ route('invoices.show', $invoice) }}" class="font-bold text-blue-600">Ver</a></td></tr>@endforeach</tbody></table></div>
            <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $invoices->links() }}</div>
        @endif
    </div>
</x-app-layout>
