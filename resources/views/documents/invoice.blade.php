<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        @page { margin: 34px; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { border-bottom: 3px solid #0568f5; padding-bottom: 18px; }
        .brand-mark { display: block; height: 64px; width: 64px; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .title { color: #0568f5; font-size: 25px; font-weight: 800; margin: 0; }
        .grid { display: table; margin-top: 28px; table-layout: fixed; width: 100%; }
        .cell { display: table-cell; vertical-align: top; width: 50%; }
        table { border-collapse: collapse; margin-top: 26px; width: 100%; }
        th { background: #071a38; color: white; font-size: 10px; letter-spacing: 1px; padding: 10px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e2e8f0; padding: 11px 10px; }
        .totals { margin-left: 52%; margin-top: 20px; width: 48%; }
        .totals td { border: 0; padding: 5px 0; }
        .total { border-top: 2px solid #0f172a !important; color: #0568f5; font-size: 17px; font-weight: 800; padding-top: 10px !important; }
        .footer { border-top: 1px solid #cbd5e1; bottom: 0; color: #64748b; font-size: 9px; left: 0; padding-top: 10px; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table style="margin:0">
            <tr>
                <td style="border:0;padding:0"><img class="brand-mark" src="{{ public_path('images/rentadrive-logo-transparent.png') }}" alt="RentaDrive"></td>
                <td class="right" style="border:0;padding:0">
                    <p class="title">FACTURA</p>
                    <p style="font-size:16px;font-weight:bold;margin:5px 0 0">{{ $invoice->number }}</p>
                    <p class="muted">Emitida: {{ $invoice->issued_at->format('d/m/Y') }}<br>Vence: {{ $invoice->due_at?->format('d/m/Y') ?: 'No definido' }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="grid">
        <div class="cell">
            <p class="muted" style="font-size:10px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Facturado a</p>
            <p style="font-size:15px;font-weight:bold;margin:5px 0">{{ $invoice->customer->full_name }}</p>
            <p class="muted" style="margin:0">{{ $invoice->customer->document_number }}<br>{{ $invoice->customer->phone }}<br>{{ $invoice->customer->email }}</p>
        </div>
        <div class="cell right">
            <p class="muted" style="font-size:10px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">Operación</p>
            <p style="font-size:15px;font-weight:bold;margin:5px 0">{{ $invoice->rental->code }}</p>
            <p class="muted" style="margin:0">{{ $invoice->rental->vehicle->display_name }}<br>{{ $invoice->rental->start_at->format('d/m/Y') }} — {{ $invoice->rental->expected_return_at->format('d/m/Y') }}</p>
        </div>
    </div>

    <table>
        <thead><tr><th>Descripción</th><th>Cantidad</th><th>Precio</th><th class="right">Importe</th></tr></thead>
        <tbody>
            <tr>
                <td><strong>Alquiler de vehículo</strong><br><span class="muted">{{ $invoice->rental->vehicle->display_name }}</span></td>
                <td>1</td>
                <td>RD$ {{ number_format((float) $invoice->subtotal, 2) }}</td>
                <td class="right">RD$ {{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="right">RD$ {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        <tr><td class="muted">Impuesto</td><td class="right">RD$ {{ number_format((float) $invoice->tax, 2) }}</td></tr>
        <tr><td class="muted">Descuento</td><td class="right">- RD$ {{ number_format((float) $invoice->discount, 2) }}</td></tr>
        <tr><td class="total">TOTAL</td><td class="total right">RD$ {{ number_format((float) $invoice->total, 2) }}</td></tr>
        <tr><td class="muted">Pagado</td><td class="right">RD$ {{ number_format((float) $invoice->paid_amount, 2) }}</td></tr>
        <tr><td style="font-weight:bold">Balance</td><td class="right" style="font-weight:bold">RD$ {{ number_format((float) $invoice->balance, 2) }}</td></tr>
    </table>

    @if ($invoice->notes)<p style="margin-top:28px"><strong>Notas:</strong> {{ $invoice->notes }}</p>@endif
    <div class="footer">RentaDrive · Gestiona tu flota. Impulsa tu negocio.</div>
</body>
</html>
