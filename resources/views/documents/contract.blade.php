<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contrato {{ $rental->code }} | RentaDrive</title>
    <style>
        * { box-sizing: border-box; }
        body { background:#e2e8f0; color:#0f172a; font-family:Arial,sans-serif; margin:0; padding:32px; }
        .document { background:white; box-shadow:0 20px 50px rgba(15,23,42,.18); margin:auto; max-width:900px; min-height:1100px; padding:54px; }
        .header { align-items:center; border-bottom:3px solid #0568f5; display:flex; justify-content:space-between; padding-bottom:22px; }
        .logo { height:90px; object-fit:contain; width:280px; }
        h1 { color:#0568f5; font-size:24px; margin:0; text-align:right; }
        h2 { font-size:15px; margin:28px 0 10px; text-transform:uppercase; }
        p, li { font-size:13px; line-height:1.7; }
        .grid { display:grid; gap:12px; grid-template-columns:repeat(2,1fr); }
        .field { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; }
        .field small { color:#64748b; display:block; font-size:10px; font-weight:bold; letter-spacing:.08em; margin-bottom:5px; text-transform:uppercase; }
        .signatures { display:grid; gap:60px; grid-template-columns:1fr 1fr; margin-top:80px; }
        .signature { border-top:1px solid #0f172a; padding-top:8px; text-align:center; }
        .actions { margin:0 auto 18px; max-width:900px; text-align:right; }
        button { background:#0568f5; border:0; border-radius:10px; color:white; cursor:pointer; font-weight:bold; padding:12px 18px; }
        @media print { body { background:white; padding:0; } .actions { display:none; } .document { box-shadow:none; max-width:none; padding:25px 40px; } }
        @media (max-width:700px) { body { padding:10px; } .document { padding:24px; } .header { align-items:flex-start; flex-direction:column; } h1 { text-align:left; } .grid,.signatures { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Imprimir contrato</button></div>
    <main class="document">
        <header class="header">
            <img src="{{ asset('images/rentadrive-logo-transparent.png') }}" alt="RentaDrive" class="logo">
            <div><h1>CONTRATO DE ALQUILER</h1><p style="text-align:right"><strong>{{ $rental->code }}</strong></p></div>
        </header>

        <h2>Datos del arrendatario</h2>
        <div class="grid">
            <div class="field"><small>Nombre</small>{{ $rental->customer->full_name }}</div>
            <div class="field"><small>Documento</small>{{ $rental->customer->document_number }}</div>
            <div class="field"><small>Licencia</small>{{ $rental->customer->license_number ?: 'No indicada' }}</div>
            <div class="field"><small>Teléfono</small>{{ $rental->customer->phone }}</div>
        </div>

        <h2>Vehículo y período</h2>
        <div class="grid">
            <div class="field"><small>Vehículo</small>{{ $rental->vehicle->display_name }}</div>
            <div class="field"><small>Placa / VIN</small>{{ $rental->vehicle->plate }} / {{ $rental->vehicle->vin ?: 'N/D' }}</div>
            <div class="field"><small>Entrega</small>{{ $rental->start_at->format('d/m/Y h:i A') }}</div>
            <div class="field"><small>Retorno esperado</small>{{ $rental->expected_return_at->format('d/m/Y h:i A') }}</div>
            <div class="field"><small>Kilometraje</small>{{ number_format($rental->opening_mileage) }} km</div>
            <div class="field"><small>Combustible</small>{{ $rental->fuel_out }}%</div>
        </div>

        <h2>Condiciones económicas</h2>
        <div class="grid">
            <div class="field"><small>Tarifa diaria</small>RD$ {{ number_format((float) $rental->daily_rate, 2) }}</div>
            <div class="field"><small>Depósito</small>RD$ {{ number_format((float) $rental->deposit_amount, 2) }}</div>
            <div class="field"><small>Impuestos</small>RD$ {{ number_format((float) $rental->taxes, 2) }}</div>
            <div class="field"><small>Total estimado</small><strong>RD$ {{ number_format((float) $rental->total, 2) }}</strong></div>
        </div>

        <h2>Términos</h2>
        <ol>
            <li>El arrendatario declara recibir el vehículo en las condiciones documentadas en la inspección de entrega.</li>
            <li>El vehículo debe devolverse en la fecha, ubicación y nivel de combustible acordados.</li>
            <li>Los días adicionales, faltantes de combustible, daños, multas y otros cargos comprobables se agregarán a la factura final.</li>
            <li>El arrendatario se compromete a utilizar el vehículo de forma responsable y conforme a las leyes de tránsito vigentes.</li>
            <li>La firma de este documento confirma la aceptación de las condiciones registradas en RentaDrive.</li>
        </ol>

        @if ($rental->notes)<p><strong>Notas:</strong> {{ $rental->notes }}</p>@endif

        <div class="signatures">
            <div class="signature">Firma del arrendatario<br><strong>{{ $rental->customer->full_name }}</strong></div>
            <div class="signature">Representante de RentaDrive<br><strong>{{ $rental->opener?->name ?: '________________' }}</strong></div>
        </div>
    </main>
</body>
</html>
