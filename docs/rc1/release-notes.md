# Notas de la versión 1.0.0-rc.1 (candidata)

## Seguridad
- MFA TOTP para administradores de empresa y de plataforma, con códigos de recuperación, limitación de intentos y auditoría (PR #58).
- CSP progresiva con nonce por petición, modos `enforce`/`report-only`/`off` y endpoint de reportes (PR #59).
- Matriz de pruebas negativas entre empresas y guarda estructural de `company_id`; `UserRequest` responde 404 antes de validar sobre usuarios ajenos (PR #63).
- Hardening previo a staging: sin cuentas predecibles, CORS cerrado, proxies de confianza, contraseñas fuertes (PR #47).
- Dependencias: eliminado `maatwebsite/excel` sin uso (PR #62); parches de Laravel/Composer (#54), npm (#57), `upload-artifact` (#53).

## Operación
- Reintentos con backoff e idempotencia en automatizaciones; monitor y alertas; salud (`/api/health`); logs JSON y `X-Request-Id` (PR #60).
- Respaldos cifrados con huella, retención y restauración verificada en entorno descartable (PR #61).
- Staging privado reproducible con Docker (PR #48); procedimiento de rotación de credenciales SQL (PR #64).

## Producto
- Acuse de contrato con instantánea versionada y detección de cambios posteriores; ITBIS y totales en centavos enteros (PR #65). Sigue siendo un acuse interno, **no** firma digital certificada.
- QA móvil y reflujo de zoom (PR #49).

## Calidad
- Guardas de N+1 y tiempo con datos ficticios (PR #66).

## Sin cambios deliberados
Diseño solo oscuro, Laravel 13, PHP ≥ 8.4.27, SQL Server 2022. Sin cobros reales ni e-CF.

## Versiones previas
Ver `docs/commercial-roadmap.md` y el historial de PR del repositorio.
