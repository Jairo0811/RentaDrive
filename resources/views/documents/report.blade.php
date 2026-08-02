<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte RentaDrive</title>
    <style>
        @page { margin: 28px 32px; }
        body { color: #0f172a; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #0568f5; padding-bottom: 14px; }
        .brand-mark { display: block; height: 54px; width: 54px; }
        .right { text-align: right; }
        .muted { color: #64748b; }
        .title { color: #0568f5; font-size: 22px; font-weight: 800; margin: 0; }
        .metrics { border-collapse: separate; border-spacing: 8px; margin: 18px -8px 0; width: calc(100% + 16px); }
        .metric { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; vertical-align: top; width: 20%; }
        .metric-label { color: #64748b; font-size: 9px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; }
        .metric-value { font-size: 16px; font-weight: 800; margin-top: 5px; }
        .section-title { color: #0f172a; font-size: 14px; font-weight: 800; margin: 20px 0 8px; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th { background: #071a38; color: #fff; font-size: 8px; letter-spacing: .5px; padding: 8px; text-align: left; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
        .status-grid { border-collapse: collapse; margin-top: 8px; width: 55%; }
        .status-grid td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; }
        .footer { border-top: 1px solid #cbd5e1; bottom: 0; color: #64748b; font-size: 8px; left: 0; padding-top: 8px; position: fixed; right: 0; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border-collapse:collapse;width:100%">
            <tr>
                <td style="padding:0"><img class="brand-mark" src="{{ public_path('images/rentadrive-mark.png') }}" alt="RentaDrive"></td>
                <td class="right" style="padding:0">
                    <p class="title">REPORTE OPERATIVO</p>
                    <p style="font-size:13px;font-weight:bold;margin:4px 0 0">{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</p>
                    <p class="muted" style="margin:4px 0 0">Generado: {{ now()->format('d/m/Y H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="metrics">
        <tr>
            <td class="metric"><div class="metric-label">Alquileres</div><div class="metric-value">{{ $metrics['rental_count'] }}</div></td>
            <td class="metric"><div class="metric-label">Facturado</div><div class="metric-value">RD$ {{ number_format((float) $metrics['billed'], 2) }}</div></td>
            <td class="metric"><div class="metric-label">Cobrado</div><div class="metric-value">RD$ {{ number_format((float) $metrics['collected'], 2) }}</div></td>
            <td class="metric"><div class="metric-label">Por cobrar</div><div class="metric-value">RD$ {{ number_format((float) $metrics['outstanding'], 2) }}</div></td>
            <td class="metric"><div class="metric-label">Utilización</div><div class="metric-value">{{ number_format((float) $metrics['utilization'], 1) }}%</div></td>
        </tr>
    </table>

    <h2 class="section-title">Operaciones del período</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Código</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th>Inicio</th>
                <th>Retorno esperado</th>
                <th>Estado</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rentals as $rental)
                <tr>
                    <td>{{ $rental->code }}</td>
                    <td>{{ $rental->customer->full_name }}</td>
                    <td>{{ $rental->vehicle->display_name }}</td>
                    <td>{{ $rental->start_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $rental->expected_return_at->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($rental->status) }}</td>
                    <td class="right">RD$ {{ number_format((float) $rental->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" style="padding:16px;text-align:center">No hay operaciones dentro del período seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 class="section-title">Flota por estado</h2>
    <table class="status-grid">
        @foreach ([
            'available' => 'Disponible',
            'reserved' => 'Reservado',
            'rented' => 'Alquilado',
            'maintenance' => 'Mantenimiento',
            'inactive' => 'Inactivo',
        ] as $status => $label)
            <tr>
                <td>{{ $label }}</td>
                <td class="right"><strong>{{ (int) ($fleetByStatus[$status] ?? 0) }}</strong></td>
            </tr>
        @endforeach
    </table>

    <div class="footer">RentaDrive · Gestiona tu flota. Impulsa tu negocio.</div>
</body>
</html>
