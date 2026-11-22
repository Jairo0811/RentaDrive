# RentaDrive — auditoría de seguridad previa a staging

**Fecha:** 21/11/2026 · **Base:** `main` en `f0f56bd` · **Alcance:** repositorio y configuración. No se tocó `RentaDriveDb` ni ningún usuario existente.

Este documento separa lo corregido en esta PR de lo que sigue pendiente. No es una certificación de seguridad.

## Hallazgos y estado

| # | Hallazgo | Riesgo | Estado |
| --- | --- | --- | --- |
| 1 | Seeders de usuarios con contraseña por defecto en `config/rentadrive.php`; solo se protegían por `APP_ENV`. Un staging con `APP_ENV=local` habría creado `admin@rentadrive.com.do` con una contraseña pública. | Alto | **Corregido.** Los seeders se niegan si `APP_ENV` no es local/testing **y**, en `local`, si `APP_URL` no es un host de desarrollo (`localhost`, `127.0.0.1`, `::1`, `*.localhost`, `*.test`). Fuera de local/testing ya no existe contraseña por defecto en la configuración; sin contraseña explícita el seeder no crea nada. |
| 2 | Una base restaurada desde local podía llegar a staging con las cuentas demo y sus contraseñas conocidas. | Alto | **Corregido (detección).** `rentadrive:production-check` revisa, solo en lectura, si `admin@rentadrive.com.do` o `platform@rentadrive.test` conservan una contraseña de demostración conocida. No modifica ni borra usuarios: la acción correctiva es del operador. |
| 3 | `RentaDriveDb.sql` (versionado) contenía en claro la contraseña del login `rentadrive_app` y reseteaba la de un login existente. | Medio-alto | **Corregido en el archivo:** ahora recibe la contraseña y el nombre de la base por variables de `sqlcmd` y no altera logins existentes. **Pendiente (operador):** la contraseña anterior permanece en el historial de Git; debe considerarse expuesta y rotarse en cualquier servidor donde se haya usado. Reescribir el historial no se hizo. |
| 4 | `rentadrive:production-check` no cubría SameSite, driver de sesión, longitud de `APP_KEY`, cuenta `sa`, contraseña de BD vacía, `LOG_LEVEL=debug`, comodín de proxies, secreto de webhook sandbox ni CORS. | Medio | **Corregido** (`ProductionReadiness::hardeningProblems()`). |
| 5 | Sin configuración de proxies de confianza: tras un túnel/proxy Laravel no detectaba HTTPS (enlaces `http`, sin HSTS). | Medio | **Corregido.** `TRUSTED_PROXIES` (IP/CIDR) alimenta el middleware nativo; vacío = ninguno; el comodín se marca como problema. |
| 6 | CORS heredado del framework: `allowed_origins=['*']` en `api/*`. Solo existe el webhook sandbox (servidor a servidor, sin credenciales). | Bajo | **Corregido.** `config/cors.php` sin orígenes y sin credenciales. |
| 7 | Política de contraseñas por defecto (mínimo 8). | Medio | **Corregido para producción/staging:** mínimo 12 con mayúsculas, números y símbolos. En local/testing se mantiene el mínimo anterior. |
| 8 | Webhook de pagos sandbox. | Bajo | **Verificado sin cambios:** `SandboxGateway::enabled()` solo en local/testing y firma HMAC con `hash_equals`. El check ahora marca un `RENTA_PAYMENT_WEBHOOK_SECRET` definido en producción. |

## Verificado sin necesidad de cambios

- Cookies: `HttpOnly` por defecto, `SameSite=lax`, `Secure` y cifrado de sesión exigidos por el preflight.
- CSRF: middleware web de Laravel activo; el único endpoint sin CSRF (`api/payments/sandbox/webhook`) valida firma y está limitado a 30/min.
- Login con límite de 5 intentos por clave.
- `.env`, `.env.production` y `auth.json` ignorados por Git; ningún `.env` versionado.
- Cabeceras: `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, HSTS solo en producción sobre HTTPS, `no-store` para sesiones autenticadas.
- `composer audit --locked`: sin avisos. `npm audit`: 0 vulnerabilidades (ambos ya corren en CI).
- Una prueba automatizada falla si se versionan secretos conocidos (el literal de la contraseña anterior, claves AWS, claves privadas, `*_PASSWORD=`/`APP_KEY=` con valor).

## Pendiente o bloqueado (no resuelto aquí)

- **Rotar** la contraseña del login SQL local `rentadrive_app` (la anterior está en el historial). Acción del operador sobre su servidor; no se hizo.
- **Content-Security-Policy**: no hay CSP. Alpine.js estándar requiere `unsafe-eval`; adoptar la build CSP de Alpine y una política con nonces es trabajo separado.
- **Autenticación multifactor**: implementada como TOTP (ver `docs/mfa.md`); `MFA_ENFORCE` está apagado por defecto y debe activarse antes de cuentas SuperAdmin reales.
- **Contraseñas filtradas** (`Password::uncompromised()`): requiere salida a un servicio externo; no se activó.
- **Cuenta de migraciones separada** (el login de aplicación del script conserva `db_ddladmin` para poder migrar). Ver `docs/staging-private.md`.
- **Pasarela de pago real y e-CF**: siguen deshabilitados y **bloqueados por dependencia externa**.
- **Dependabot / escaneo de secretos en CI** (p. ej. gitleaks): no configurado; decisión del propietario del repositorio.
- Revisión manual de permisos y de la aislación multiempresa por un tercero independiente.

## Cómo verificar

    php artisan rentadrive:production-check      # exit 1 si hay problemas
    composer audit --locked && npm audit --audit-level=high
    php artisan test --filter=StagingSecurityHardeningTest
