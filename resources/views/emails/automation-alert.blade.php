<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>{{ $alertTitle }}</title></head>
<body style="font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;padding:24px">
    <div style="max-width:640px;margin:auto;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px">
        <p style="color:#2563eb;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase">{{ $companyName }}</p>
        <h1 style="margin:8px 0 16px;font-size:24px">{{ $alertTitle }}</h1>
        <p style="line-height:1.7">{{ $alertMessage }}</p>
        <p style="margin-top:28px;color:#64748b;font-size:12px">Aviso automático generado por RentaDrive.</p>
    </div>
</body>
</html>
