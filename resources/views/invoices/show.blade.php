<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-slate-950 dark:text-white">{{ $invoice->number }}</p><p class="text-xs text-slate-500">Detalle de factura</p></div></x-slot>

    <x-page-header :title="'Factura '.$invoice->number" :subtitle="$invoice->customer->full_name">
        <x-slot name="actions"><a href="{{ route('invoices.download', $invoice) }}" class="btn-primary">Descargar PDF</a><a href="{{ route('rentals.show', $invoice->rental) }}" class="btn-secondary">Ver alquiler</a></x-slot>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
        <div class="space-y-6">
            <section class="panel p-5 sm:p-6">
                <div class="flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-[.16em] text-blue-600">RentaDrive</p><h2 class="mt-2 text-xl font-black text-slate-950 dark:text-white">{{ $invoice->number }}</h2></div><x-status-badge :status="$invoice->status" /></div>
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div><p class="text-xs font-bold uppercase text-slate-400">Facturado a</p><p class="mt-2 font-bold text-slate-900 dark:text-white">{{ $invoice->customer->full_name }}</p><p class="mt-1 text-sm text-slate-500">{{ $invoice->customer->document_number }}</p></div>
                    <div class="sm:text-right"><p class="text-sm text-slate-500">Emitida: <strong class="text-slate-800 dark:text-slate-200">{{ $invoice->issued_at->format('d/m/Y') }}</strong></p><p class="mt-1 text-sm text-slate-500">Vence: <strong class="text-slate-800 dark:text-slate-200">{{ $invoice->due_at?->format('d/m/Y') ?: 'Sin vencimiento' }}</strong></p></div>
                </div>
                <div class="mt-6 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="flex justify-between gap-4"><div><p class="font-bold text-slate-900 dark:text-white">Alquiler {{ $invoice->rental->code }}</p><p class="mt-1 text-sm text-slate-500">{{ $invoice->rental->vehicle->display_name }} · {{ $invoice->rental->start_at->format('d/m/Y') }} a {{ $invoice->rental->expected_return_at->format('d/m/Y') }}</p></div><p class="font-bold">RD$ {{ number_format((float) $invoice->subtotal, 2) }}</p></div>
                </div>
                <dl class="ml-auto mt-6 max-w-sm space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>RD$ {{ number_format((float) $invoice->subtotal, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Impuesto</dt><dd>RD$ {{ number_format((float) $invoice->tax, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Descuento</dt><dd>- RD$ {{ number_format((float) $invoice->discount, 2) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-4 text-lg font-black dark:border-slate-800"><dt>Total</dt><dd>RD$ {{ number_format((float) $invoice->total, 2) }}</dd></div>
                    <div class="flex justify-between text-emerald-600"><dt>Pagado</dt><dd>RD$ {{ number_format((float) $invoice->paid_amount, 2) }}</dd></div>
                    <div class="flex justify-between text-xl font-black text-blue-600"><dt>Balance</dt><dd>RD$ {{ number_format((float) $invoice->balance, 2) }}</dd></div>
                </dl>
            </section>

            <section class="table-shell">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 class="font-black text-slate-950 dark:text-white">Pagos aplicados</h2></div>
                @if ($invoice->payments->isEmpty())<x-empty-state title="Sin pagos" message="La factura todavía no ha recibido pagos." />
                @else<div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Recibo</th><th>Fecha</th><th>Método</th><th>Referencia</th><th>Monto</th></tr></thead><tbody>@foreach ($invoice->payments as $payment)<tr><td class="font-bold">{{ $payment->receipt_number }}</td><td>{{ $payment->paid_at->format('d/m/Y h:i A') }}</td><td>{{ ucfirst($payment->method) }}</td><td>{{ $payment->reference ?: '—' }}</td><td>RD$ {{ number_format((float) $payment->amount, 2) }}</td></tr>@endforeach</tbody></table></div>@endif
            </section>
        </div>

        <aside class="space-y-6">
            @can('manage payments')
                @if ((float) $invoice->balance > 0)
                    <section class="panel p-5 sm:p-6">
                        <h2 class="font-black text-slate-950 dark:text-white">Aplicar pago</h2>
                        <form method="POST" action="{{ route('payments.store') }}" class="mt-5 space-y-4">
                            @csrf<input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                            <div><label class="form-label" for="paid_at">Fecha</label><input id="paid_at" type="datetime-local" name="paid_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-input" required></div>
                            <div><label class="form-label" for="method">Método</label><select id="method" name="method" class="form-input"><option value="cash">Efectivo</option><option value="card">Tarjeta</option><option value="transfer">Transferencia</option><option value="other">Otro</option></select></div>
                            <div><label class="form-label" for="amount">Monto</label><input id="amount" type="number" step="0.01" max="{{ $invoice->balance }}" name="amount" value="{{ $invoice->balance }}" class="form-input" required></div>
                            <div><label class="form-label" for="reference">Referencia</label><input id="reference" name="reference" class="form-input"></div>
                            <div><label class="form-label" for="payment_notes">Notas</label><textarea id="payment_notes" name="notes" rows="2" class="form-input"></textarea></div>
                            <button class="btn-primary w-full">Registrar pago</button>
                        </form>
                    </section>
                @endif
            @endcan
            @can('manage invoices')
                <section class="panel p-5 sm:p-6">
                    <h2 class="font-black text-slate-950 dark:text-white">Ajustes de factura</h2>
                    <form method="POST" action="{{ route('invoices.update', $invoice) }}" class="mt-5 space-y-4">
                        @csrf @method('PUT')
                        <div><label class="form-label" for="due_at">Vencimiento</label><input id="due_at" type="date" name="due_at" value="{{ $invoice->due_at?->format('Y-m-d') }}" class="form-input"></div>
                        <div><label class="form-label" for="discount">Descuento</label><input id="discount" type="number" step="0.01" min="0" name="discount" value="{{ $invoice->discount }}" class="form-input"></div>
                        <div><label class="form-label" for="notes">Notas</label><textarea id="notes" name="notes" rows="3" class="form-input">{{ $invoice->notes }}</textarea></div>
                        <button class="btn-secondary w-full">Guardar ajustes</button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-app-layout>
