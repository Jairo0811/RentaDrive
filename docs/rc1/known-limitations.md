# Límites conocidos

- **No hay certificación fiscal**: no se emiten NCF ni e-CF; la facturación es interna.
- **No hay cobros reales**: pagos manuales; no se guardan datos de tarjeta.
- **Acuse de contrato interno**: no es firma digital certificada del cliente ni sustituye asesoría legal.
- **Sucursales**: `branch_id` se asigna al crear; todos los usuarios de una empresa ven los registros de todas sus sucursales (decisión de negocio pendiente).
- **CSP**: `'unsafe-eval'` necesario por Alpine estándar; `style-src-attr 'unsafe-inline'`.
- **MFA** sin QR; restablecimiento solo por consola (`rentadrive:mfa-reset`).
- **Pruebas**: solo Chromium en Playwright; sin iOS/Android reales, sin lectores de pantalla, sin zoom real.
- **Rendimiento**: medido con ~250 filas por entidad en CI, no con carga concurrente ni volumen de producción.
- **Restauración** ensayada en runner descartable; falta el ensayo en el staging real.
- **Dependencias diferidas por decisión**: PHPUnit 13 (#55) y Node 26 (#52) no se actualizan solo por novedad.
- **PHP**: la imagen exige PHP ≥ 8.4.27; el entorno local de desarrollo puede diferir.
- **Contraseña SQL antigua** en el historial de git: rotación pendiente del operador (`docs/sql-password-rotation.md`).
