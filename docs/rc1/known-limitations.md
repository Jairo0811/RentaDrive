# Límites conocidos

- **No hay certificación fiscal**: no se emiten NCF ni e-CF; la facturación es interna.
- **No hay cobros reales**: pagos manuales; no se guardan datos de tarjeta.
- **Acuse de contrato interno**: no es firma digital certificada del cliente ni sustituye asesoría legal.
- **Sucursales**: Agente e Inspector ven solo los registros de su sucursal (vehículos, reservas, alquileres, facturas, pagos, inspecciones y selectores). Los clientes son de toda la empresa, la disponibilidad cuenta toda la empresa (por diseño, para no sobrevender) (el dashboard del Agente e Inspector también se limita a su sucursal).
- **CSP**: sin `'unsafe-eval'` (Alpine build CSP); persiste `style-src-attr 'unsafe-inline'` y la dependencia de cdnjs (Font Awesome).
- **MFA**: restablecimiento solo por consola (`rentadrive:mfa-reset`); sin WebAuthn.
- **Pruebas**: solo Chromium en Playwright; sin iOS/Android reales, sin lectores de pantalla, sin zoom real.
- **Rendimiento**: medido con ~250 filas por entidad en CI, no con carga concurrente ni volumen de producción.
- **Restauración** ensayada en runner descartable; falta el ensayo en el staging real.
- **Dependencias diferidas por decisión**: PHPUnit 13 (#55) y Node 26 (#52) no se actualizan solo por novedad.
- **PHP**: la imagen exige PHP ≥ 8.4.27; el entorno local de desarrollo puede diferir.
- **Contraseña SQL antigua** en el historial de git: rotación pendiente del operador (`docs/sql-password-rotation.md`).
