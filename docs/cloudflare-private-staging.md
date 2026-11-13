# RentaDrive — staging privado con Cloudflare Tunnel + Access

**Estado (13/11/2026):**

- **Ruta A, demo temporal (Quick Tunnel protegido):** según el registro del operador, el 12/11/2026 se instaló `cloudflared 2026.11.0` y un Quick Tunnel con `--allowed-mail` obtuvo diagnóstico de conectividad satisfactorio (DNS, QUIC, API) hacia `http://127.0.0.1:8080`. **No hay evidencia** de que existiera un servidor HTTP en ese puerto, de que se completara el OTP desde un navegador externo, de que se sirviera una página, ni de que se desplegara Laravel.
- **Ruta B, staging estable (dominio + Access + túnel administrado):** **pendiente**. No existe dominio propio en la cuenta, ni instancia de staging, ni aplicación Access, ni túnel permanente.

La URL `*.trycloudflare.com` de un Quick Tunnel es efímera: no debe publicarse ni citarse como dirección de RentaDrive.

**Objetivo:** demo privada y comprobable de la aplicación Laravel 13, sin exponer el desarrollo local ni la base real `RentaDriveDb`.

## Elegir ruta

| | Ruta A: Quick Tunnel protegido | Ruta B: staging estable |
| --- | --- | --- |
| Para qué sirve | Demostración corta a personas concretas, con datos sintéticos. | Staging privado repetible, base de un piloto. |
| Requiere | `cloudflared` actualizado, un origen aislado y los correos autorizados. | Dominio en Cloudflare, aplicación Access, túnel administrado, instancia dedicada. |
| Dirección | Cambia en cada ejecución. | Hostname fijo. |
| Disponibilidad | Sin garantía; termina al detener `cloudflared`. | Depende del servidor de origen y de la operación. |
| Límites (según la documentación oficial) | 200 solicitudes en vuelo por túnel (las demás reciben `429`); sin Server-Sent Events; el OTP exige un navegador interactivo. | Los que fije la configuración propia. |
| Apto para producción | **No.** Solo pruebas y desarrollo. | Es la base; producción exige además los criterios de la sección 4. |

Referencias oficiales: [Quick Tunnels](https://developers.cloudflare.com/tunnel/get-started/quick-tunnels/) y [protección con correo](https://developers.cloudflare.com/changelog/post/2026-10-02-protected-quick-tunnels/).

## Ruta A: demo temporal con Quick Tunnel protegido

**Reglas:** el origen debe ser un entorno de staging aislado con datos sintéticos (ver secciones 0 y 1). **Nunca** apuntar el túnel a la instancia local de desarrollo ni a `RentaDriveDb`. No exponer Vite (5173), `composer dev` ni `php artisan serve`.

1. Preparar el origen de staging y comprobar localmente `GET http://127.0.0.1:8080/up` con HTTP 200.
2. Actualizar `cloudflared` a la última versión y ejecutar, con los correos autorizados (se admite repetir el flag o separar por comas):

   ```bash
   cloudflared tunnel --url http://127.0.0.1:8080 --allowed-mail persona@ejemplo.com
   ```

3. Desde un navegador **externo**, abrir la URL que imprime `cloudflared`, completar el OTP con un correo autorizado y comprobar la página. Comprobar también que un correo no autorizado no obtiene acceso.
4. No compartir la URL fuera de las personas autorizadas ni el PIN recibido por correo.
5. Al terminar, detener el proceso `cloudflared`: el acceso termina para todos y la URL deja de funcionar.

Criterio de cierre de la Ruta A: HTTP 200 del origen, OTP completado desde un navegador externo, acceso denegado probado y túnel cerrado. Mientras alguno falte, **no** se considera demostrada.

## Ruta B: staging estable

Todo lo que sigue (secciones 0 a 4) describe la Ruta B y sigue **pendiente**.

## 0. Requisitos y límites

- Cuenta de Cloudflare, dominio propio activo en Cloudflare y permiso para configurar **Access** y **Tunnels**.
- Una **instancia separada** de RentaDrive, con PHP 8.4.27+, extensiones `pdo_sqlsrv` / `sqlsrv`, servidor web de staging y Node para compilar assets.
- Base **SQL Server aislada** para staging (por ejemplo `RentaDriveStaging`), usuario propio de mínimos privilegios, copia de seguridad y datos sintéticos. No hacer `migrate:fresh`, seeders demo ni pruebas destructivas sobre `RentaDriveDb`.
- Servidor HTTP de staging en loopback (ejemplo **127.0.0.1:8080**), gestionado por Nginx + PHP-FPM o una configuración equivalente de servidor web para Laravel, apuntando **solamente a `public/`**. No utilizar `php artisan serve`, `composer dev` ni el puerto Vite 5173 como origen de Internet.
- Se requieren un hostname, certificado/TLS válido hacia SQL Server y secretos proporcionados por el operador. **No colocar tokens de túnel, credenciales, certificados privados, secretos ni `.env` en Git.**
- El hostname `staging.example.com` y el puerto 8080 en este documento son **ejemplos**, no una URL creada.

## 1. Entorno de aplicación separado

Crear un directorio/copia de trabajo independiente para staging y una base SQL Server exclusiva. Mantener el entorno local actual sin alteraciones.

1. Usar `.env.production.example` únicamente como referencia; crear un archivo `.env` privado en staging con **APP_KEY distinto** del entorno local.
2. Establecer `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://staging.example.com`, `LOG_LEVEL=warning`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`.
3. Configurar `DB_CONNECTION=sqlsrv`, `DB_DATABASE=RentaDriveStaging`, `DB_ENCRYPT=yes`, `DB_TRUST_SERVER_CERTIFICATE=false` y un certificado SQL Server verificable. No desactivar la verificación TLS para pasar el preflight.
4. Usar colas y caché persistentes (`QUEUE_CONNECTION=database`, `CACHE_STORE=database`, con sus tablas); sesiones en DB o Redis. Mantener `RENTA_AUTOMATION_ENABLED=false` mientras no existan correo real, autorización y opt-in.
5. Usar almacenamiento privado `RENTA_EVIDENCE_DISK=local` **solo para una instancia de staging de un nodo**, protegido fuera de la raíz pública. Para varios nodos o entrega persistente utilizar S3 compatible/bucket privado, incluyendo R2 si se configura con permisos mínimos.
6. Generar usuarios de prueba exclusivos y credenciales únicas. **No reutilizar** `RENTADRIVE_ADMIN_PASSWORD` ni `RENTADRIVE_PLATFORM_ADMIN_PASSWORD` de `.env.example`; no exponer cuentas demo a usuarios no autorizados.
7. Configurar confianza de proxies exclusivamente para el proxy/túnel controlado. Comprobar que Laravel genera enlaces y cookies HTTPS y que `X-Forwarded-Proto` solo se acepta de proxies de confianza.

En staging, con las dependencias instaladas, efectuar las operaciones siguientes **después de verificar la conexión a la base aislada**:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate:status
php artisan migrate --force
php artisan rentadrive:production-check
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**No continuar** si el preflight informa problemas; antes de migrar debe existir copia verificable y el esquema debe ser el de `RentaDriveStaging`. Supervisar workers y scheduler de Laravel; no usar `queue:listen` interactivo como servicio permanente.

## 2. Cloudflare Access **ANTES** del túnel público

Según la documentación oficial, una ruta publicada sin la aplicación Access puede ser accesible desde Internet. Seguir este orden:

1. En Cloudflare Zero Trust/Access crear una aplicación **Self-hosted**, para el hostname exacto `staging.example.com`.
2. Crear una política **Allow** restringida a correos/identidades autorizadas, con MFA y sesiones cortas. Verificar que los usuarios no incluidos reciben denegación.
3. Configurar **Protect with Access** para que `cloudflared` valide JWT de Access, o verificar de forma equivalente el token firmado en el origen.
4. **Solo entonces** crear un túnel de tipo `cloudflared` (por ejemplo `rentadrive-staging`), instalar el conector como servicio protegido en el equipo que ejecuta el origen y registrar una ruta de hostname hacia `http://127.0.0.1:8080`.
5. No publicar SQL Server 1433, no abrir puertos de entrada de Laravel/Vite, no habilitar rutas DNS alternativas que eviten Access. El origen debe permanecer ligado a loopback o a una red privada controlada.
6. No compartir el token del túnel ni capturas que lo incluyan. Los secretos de túnel pertenecen al equipo/gestor de secretos, no al repositorio.

Referencia oficial: https://developers.cloudflare.com/cloudflare-one/access-controls/applications/http-apps/self-hosted-public-app/ y https://developers.cloudflare.com/tunnel/get-started/.

## 3. Aceptación funcional y de seguridad

### Gate A — Calidad del código

- [ ] CI de PR completo y correcto sobre **SQL Server 2022**. El `phpunit.xml` local usa SQLite en memoria y no sustituye al CI; el workflow define `EXPECT_DB_DRIVER=sqlsrv` y `TestDatabaseDriverTest` falla si la suite no corre sobre ese motor.
- [ ] `composer validate --strict`, `composer audit --locked`, `npm audit --audit-level=high`, `npm run build`, Pint y suite de tests.
- [ ] Comprobar en Chrome, Edge y Opera, desktop/móvil: campos `.form-input` oscuros y legibles en Fiscal, Clientes, Flota, Reservas, Facturación, Login y componentes compartidos; probar foco, autocompletado, selects, fechas, disabled y perfil de alto contraste.

### Gate B — Infraestructura

- [ ] Backups SQL Server y ensayo de restauración en **otra base**.
- [ ] Servidor web dedicado, workers y scheduler supervisados; `public/` única raíz web.
- [ ] HTTPS externo, cookies Secure/HttpOnly/SameSite y trusted proxies comprobados.
- [ ] `rentadrive:production-check` sin problemas en staging y `GET /up` funcional.
- [ ] `public/hot` **no existe**; Laravel usa `public/build/manifest.json`; no se requiere el puerto 5173.
- [ ] Cloudflare Access niega usuarios no autorizados, protege todo el hostname y valida JWT en túnel/origen.
- [ ] No se expone `.env`, `storage/app/private`, SQL Server 1433, panel de administración sin autenticación ni assets con tokens.
- [ ] Logs y monitoreo activos sin información personal ni secretos.

### Gate C — Pruebas de negocio

- [ ] Login/logout y rutas protegidas; panel de administración.
- [ ] CRUD Clientes/Flota, calendario y reservas públicas, disponibilidad sin sobreventa.
- [ ] Facturación y perfil fiscal; **solo borradores**: no afirmar emisión real NCF/e-CF.
- [ ] Pagos sandbox solamente; no afirmar cobros ni reembolsos reales.
- [ ] Aislamiento de dos empresas: consultas, imágenes, archivos privados y permisos.
- [ ] Pruebas 401/403 y recuperación de sesión tras reautenticación en Access.

## 4. Criterios de salida

Staging es **privado y demostrable** cuando los tres gates están completos y existe un hostname real con acceso restringido. Este documento no certifica que haya sido desplegado.

El paso a un SaaS público o pilotos pagados requiere además pasarela real y conciliación, emisión fiscal certificada cuando aplique, protección de datos, monitoreo, SLO/SLA, acuerdos comerciales y pruebas de recuperación/seguridad.
