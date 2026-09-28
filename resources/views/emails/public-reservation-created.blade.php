<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reserva {{ $reservation->code }}</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;padding:24px">
    <div style="max-width:640px;margin:auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px">
        <h1 style="margin-top:0">Reserva recibida</h1>
        <p>Hola {{ $reservation->customer->first_name }}, recibimos tu solicitud de reserva en <strong>{{ $company->name }}</strong>.</p>

        <table style="width:100%;border-collapse:collapse;margin:24px 0">
            <tr><td style="padding:8px 0;color:#64748b">Código</td><td style="padding:8px 0;text-align:right;font-weight:bold">{{ $reservation->code }}</td></tr>
            <tr><td style="padding:8px 0;color:#64748b">Vehículo</td><td style="padding:8px 0;text-align:right;font-weight:bold">{{ $reservation->vehicle->model->display_name }}</td></tr>
            <tr><td style="padding:8px 0;color:#64748b">Recogida</td><td style="padding:8px 0;text-align:right">{{ $reservation->start_at->format('d/m/Y h:i A') }}</td></tr>
            <tr><td style="padding:8px 0;color:#64748b">Devolución</td><td style="padding:8px 0;text-align:right">{{ $reservation->end_at->format('d/m/Y h:i A') }}</td></tr>
            <tr><td style="padding:8px 0;color:#64748b">Total estimado</td><td style="padding:8px 0;text-align:right;font-weight:bold">{{ $company->currency }} {{ number_format((float) $reservation->estimated_total, 2) }}</td></tr>
        </table>

        <p>La reserva está <strong>pendiente de confirmación</strong>.</p>

        @if ($cancellationUrl)
            <p style="margin-top:24px">
                <a href="{{ $cancellationUrl }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:bold">
                    Gestionar cancelación
                </a>
            </p>
        @endif
    </div>
</body>
</html>
