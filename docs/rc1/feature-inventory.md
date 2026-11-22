# Inventario de funcionalidades y clasificación

Clasificación: **A** completo y probado automáticamente · **B** completo, pendiente de validación manual · **C** depende de terceros (bloqueado externamente) · **D** pendiente de desarrollo.

| Módulo | Clase | Evidencia / observaciones |
|---|:-:|---|
| Acceso, recuperación de contraseña, sesiones | A | PHPUnit + Playwright (escritorio y móvil). Contraseñas 12+ en producción/staging |
| MFA TOTP (admin de empresa y plataforma), códigos de recuperación, `rentadrive:mfa-reset` | A | `TwoFactorAuthenticationTest`, `TotpTest`. Con código QR generado en el servidor y clave manual alternativa |
| Aislamiento multiempresa | A | `CrossTenantAuthorizationMatrixTest` (matriz negativa + guarda estructural `company_id`) |
| Roles y permisos | A | [Matriz](../permission-matrix.md) |
| Clientes | A | CRUD, validación de cédula dominicana, selectores inteligentes |
| Flota (vehículos, marcas, modelos, categorías) | A | CRUD, estados, fotos |
| Reservas (internas y portal público) | A | Disponibilidad, tokens públicos por empresa, depósitos |
| Alquileres (apertura/cierre) | A | Transacciones con bloqueo, cálculo en centavos (`Money`) |
| Contratos y acuse interno | A | Huella versionada con instantánea, detección de cambios posteriores. **No es firma digital certificada** |
| Historial completo de versiones del contrato / descarga PDF firmada | D | Hoy: instantánea al acuse + vista imprimible del contrato |
| Inspecciones (con fotos) | A | Descarga autorizada por empresa |
| Mantenimientos | A | CRUD y estados |
| Facturación interna | A | Facturas internas, saldo, borrador fiscal separado |
| Pagos manuales | A | Registro, anulación con motivo y auditoría. **Sin cobro real ni datos de tarjeta** |
| Reportes y exportaciones | A | Pruebas de feature; 108 ms con ~250 filas por entidad |
| Usuarios y configuración | A | ITBIS fijo 18 %; continuidad administrativa (no se puede quitar el último admin) |
| Auditoría | A | Eventos de negocio y seguridad (`mfa_*`) |
| Portal público de reservas | A/B | Probado en CI; validación en dispositivo físico pendiente |
| Admin SaaS (empresas, ciclo de vida, planes) | A | Pruebas de ciclo de vida y límites de plan |
| Fiscal RD (preparación) | C | Borrador/validadores y base de proveedor e-CF (sandbox). Sin NCF/e-CF reales, sin certificación |
| Seguridad HTTP (CSP, cabeceras, CORS cerrado, proxies) | A | `ContentSecurityPolicyTest`, Playwright `csp`. `unsafe-eval` temporal por Alpine estándar (D) |
| Cola, planificador, reintentos, idempotencia, alertas | A | `OperationalResilienceTest`, `DigitalRentalAutomationTest` |
| Salud y observabilidad (`/api/health`, logs JSON, `X-Request-Id`) | A | Pruebas de feature |
| Respaldos cifrados, huella, retención, restauración | A (runner descartable) / B (staging real) | Ejecutado en `staging-verify`; pendiente ensayo en el staging real |
| Staging privado (Docker) | A (CI) / B | Verificado en runner; sin despliegue real |
| Accesibilidad y responsive | A (automatizado) / B | Lectores de pantalla, zoom real, iOS/Android, Safari/Firefox pendientes |
| Notificaciones externas (correo transaccional, WhatsApp/SMS) | C | Requiere proveedor/credenciales del operador |
| Dominio, Cloudflare Access, túnel | C | Cuenta y dominio del operador |
| Pasarela de pago | C | Proveedor externo; no activado |

## Elementos D pendientes (no dependen de terceros)
1. Build CSP de Alpine para retirar `'unsafe-eval'` (`docs/csp.md`).
2. MFA: WebAuthn y flujo web de restablecimiento (hoy solo comando de consola).
3. Historial de versiones editable del contrato y descarga PDF con huella.

5. Lectura restringida por sucursal dentro de una empresa — **requiere decisión de negocio**.
