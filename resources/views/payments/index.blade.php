<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">Pagos</p><p class="text-xs text-slate-500">Cobros y recibos</p></div></x-slot>

    <x-page-header title="Pagos" subtitle="Registra cobros y consulta el historial de recibos." />

    @can('manage payments')
        <section class="panel mb-6 p-5 sm:p-6" x-data="{ open: false }">
            <button type="button" class="flex w-full items-center justify-between text-left" @click="open = ! open"><span><span class="block font-black text-slate-950 dark:text-white">Registrar pago</span><span class="mt-1 block text-sm text-slate-500">{{ $openInvoices->count() }} facturas con balance</span></span><span class="btn-primary">Nuevo pago</span></button>
            <form x-cloak x-show="open" x-transition method="POST" action="{{ route('payments.store') }}" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @csrf
                <div class="md:col-span-2"><label class="form-label" for="invoice_id">Factura</label><select id="invoice_id" name="invoice_id" class="form-input" required><option value="">Selecciona</option>@foreach ($openInvoices as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->number }} · {{ $invoice->customer->full_name }} · RD$ {{ number_format((float) $invoice->balance, 2) }}</option>@endforeach</select></div>
                <div><label class="form-label" for="paid_at">Fecha</label><input id="paid_at" type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-input" required></div>
                <div><label class="form-label" for="method">Método</label><select id="method" name="method" class="form-input"><option value="cash">Efectivo</option><option value="card">Tarjeta</option><option value="transfer">Transferencia</option><option value="other">Otro</option></select></div>
                <div><label class="form-label" for="amount">Monto</label><input id="amount" type="number" step="0.01" min="0.01" name="amount" class="form-input" required></div>
                <div><label class="form-label" for="reference">Referencia</label><input id="reference" name="reference" class="form-input"></div>
                <div class="md:col-span-2"><label class="form-label" for="notes">Notas</label><input id="notes" name="notes" class="form-input"></div>
                <div class="md:col-span-2 xl:col-span-4"><button class="btn-primary">Aplicar pago</button></div>
            </form>
        </section>
    @endcan

    <form method="GET" class="panel mb-5 flex flex-col gap-3 p-4 sm:flex-row"><select name="method" class="form-input sm:max-w-56"><option value="">Todos los métodos</option>@foreach (['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'other' => 'Otro'] as $value => $label)<option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form>
    <div class="table-shell">
        @if ($payments->isEmpty())<x-empty-state title="No hay pagos" message="Los cobros registrados aparecerán aquí." />
        @else<div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Recibo</th><th>Factura</th><th>Cliente</th><th>Fecha</th><th>Método</th><th>Referencia</th><th>Monto</th><th></th></tr></thead><tbody>@foreach ($payments as $payment)<tr><td class="font-bold">{{ $payment->receipt_number }}</td><td><a href="{{ route('invoices.show', $payment->invoice) }}" class="text-blue-600">{{ $payment->invoice->number }}</a></td><td>{{ $payment->invoice->customer->full_name }}</td><td>{{ $payment->paid_at->format('d/m/Y h:i A') }}</td><td>{{ ucfirst($payment->method) }}</td><td>{{ $payment->reference ?: '—' }}</td><td class="font-bold text-emerald-600">RD$ {{ number_format((float) $payment->amount, 2) }}</td><td>@can('manage payments')<form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('¿Anular este pago?')">@csrf @method('DELETE')<button class="text-xs font-bold text-red-600">Anular</button></form>@endcan</td></tr>@endforeach</tbody></table></div><div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $payments->links() }}</div>@endif
    </div>
</x-app-layout>
