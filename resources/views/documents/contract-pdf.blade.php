<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contrato {{ $rental->code }}</title>
    <style>
        body { color:#0f172a; font-family:DejaVu Sans,Arial,sans-serif; font-size:11px; }
        h1 { color:#0568f5; font-size:20px; margin:0 0 4px; }
        h2 { border-bottom:1px solid #cbd5e1; font-size:12px; margin:18px 0 6px; padding-bottom:3px; text-transform:uppercase; }
        table { border-collapse:collapse; width:100%; }
        td { border:1px solid #e2e8f0; padding:6px 8px; vertical-align:top; width:50%; }
        small { color:#64748b; display:block; font-size:8px; text-transform:uppercase; }
        .hash { font-family:DejaVu Sans Mono,monospace; font-size:8px; word-break:break-all; }
        .sign td { border:0; padding-top:50px; text-align:center; }
        .sign .line { border-top:1px solid #0f172a; padding-top:4px; }
        .note { color:#475569; font-size:9px; margin-top:14px; }
    </style>
</head>
<body>
    <h1>CONTRATO DE ALQUILER</h1>
    <p><strong>{{ $rental->code }}</strong> · Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <h2>Arrendatario</h2>
    <table>
        <tr><td><small>Nombre</small>{{ $rental->customer->full_name }}</td><td><small>Documento</small>{{ $rental->customer->document_number }}</td></tr>
        <tr><td><small>Licencia</small>{{ $rental->customer->license_number ?: 'No indicada' }}</td><td><small>Teléfono</small>{{ $rental->customer->phone }}</td></tr>
    </table>

    <h2>Vehículo y período</h2>
    <table>
        <tr><td><small>Vehículo</small>{{ $rental->vehicle->display_name }}</td><td><small>Placa / VIN</small>{{ $rental->vehicle->plate }} / {{ $rental->vehicle->vin ?: 'N/D' }}</td></tr>
        <tr><td><small>Entrega</small>{{ $rental->start_at->format('d/m/Y h:i A') }}</td><td><small>Retorno esperado</small>{{ $rental->expected_return_at->format('d/m/Y h:i A') }}</td></tr>
        <tr><td><small>Kilometraje</small>{{ number_format($rental->opening_mileage) }} km</td><td><small>Combustible</small>{{ $rental->fuel_out }}%</td></tr>
    </table>

    <h2>Condiciones económicas</h2>
    <table>
        <tr><td><small>Tarifa diaria</small>RD$ {{ number_format((float) $rental->daily_rate, 2) }}</td><td><small>Depósito</small>RD$ {{ number_format((float) $rental->deposit_amount, 2) }}</td></tr>
        <tr><td><small>ITBIS</small>RD$ {{ number_format((float) $rental->taxes, 2) }}</td><td><small>Total</small><strong>RD$ {{ number_format((float) $rental->total, 2) }}</strong></td></tr>
    </table>

    <h2>Términos</h2>
    <ol>
        <li>El arrendatario declara recibir el vehículo en las condiciones documentadas en la inspección de entrega.</li>
        <li>El vehículo debe devolverse en la fecha, ubicación y nivel de combustible acordados.</li>
        <li>Los días adicionales, faltantes de combustible, daños, multas y otros cargos comprobables se agregarán a la factura final.</li>
        <li>El arrendatario se compromete a utilizar el vehículo de forma responsable y conforme a las leyes de tránsito vigentes.</li>
    </ol>
    @if ($rental->notes)<p><strong>Notas:</strong> {{ $rental->notes }}</p>@endif

    <table class="sign">
        <tr>
            <td><div class="line">Firma del arrendatario<br><strong>{{ $rental->customer->full_name }}</strong></div></td>
            <td><div class="line">Representante<br><strong>{{ $rental->opener?->name ?: '________________' }}</strong></div></td>
        </tr>
    </table>

    <h2>Evidencia de integridad</h2>
    @forelse ($rental->acceptances as $acceptance)
        @php($status = \App\Support\Commercial\ContractIntegrity::status($acceptance))
        <p>Acuse interno de {{ $acceptance->signer_name }} el {{ $acceptance->accepted_at->format('d/m/Y H:i') }}.
            Estado: {{ ['valid' => 'términos idénticos a los del acuse', 'closed' => 'alquiler cerrado, condiciones pactadas intactas', 'modified' => 'términos modificados después del acuse', 'legacy' => 'versión anterior, verificación parcial'][$status] }}.</p>
        <p class="hash">SHA-256 del acuse: {{ $acceptance->document_hash }}</p>
    @empty
        <p>Sin acuse interno registrado.</p>
    @endforelse
    @foreach ($rental->contractVersions as $version)
        <p class="hash">v{{ $version->version }} ({{ $version->reason }}, {{ $version->created_at->format('d/m/Y H:i') }}): {{ $version->document_hash }}</p>
    @endforeach
    <p class="note">El acuse es un registro interno auditable de RentaDrive. No es una firma digital certificada del cliente ni sustituye asesoría legal.</p>
</body>
</html>
