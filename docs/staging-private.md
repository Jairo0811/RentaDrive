# RentaDrive — staging privado reproducible

**Estado (21/11/2026):** preparado y verificado en un entorno **descartable de CI**; **no desplegado**. No existe dominio, túnel permanente, aplicación Access ni servidor de staging real. Este documento complementa `docs/cloudflare-private-staging.md` (Access/túnel) y `docs/production-operations.md` (operación general); no los sustituye.

## Qué incluye

| Pieza | Archivo | Notas |
| --- | --- | --- |
| Imagen de runtime | `deploy/Dockerfile` | PHP 8.4 FPM, ODBC 18, `pdo_sqlsrv`, sin dev ni secretos, usuario `www-data`, comprueba PHP ≥ 8.4.27. Objetivos `runtime` y `web` (nginx sin privilegios). |
| Composición aislada | `deploy/staging/docker-compose.yml` | SQL Server 2022 **sin puertos publicados**, app, web (solo `127.0.0.1:8080`), cola, scheduler y `cloudflared` opcional (perfil `tunnel`). Contenedores con `no-new-privileges`, `cap_drop: ALL` y sistema de archivos de solo lectura. |
| TLS interno | `scripts/gen-sql-tls.sh` | CA propia + certificado de SQL Server; permite `DB_ENCRYPT=yes` con `DB_TRUST_SERVER_CERTIFICATE=false`. |
| Secretos | `scripts/gen-env.sh` | Genera `.env.staging` y `.env.app` aleatorios (600, ignorados por Git). `.env.app` no contiene la contraseña de SA ni la de migraciones. |
| Base y cuentas | `sql/01-init.sql`, `scripts/init-db.sh` | `RentaDriveStaging`; cuenta de aplicación (lectura/escritura) y cuenta de migración separadas. Rechaza `RentaDriveDb`. |
| Datos ficticios | `php artisan rentadrive:staging-seed` | Empresa y datos inventados; protegido por `StagingGuard` (rechaza `RentaDriveDb`, exige `RENTADRIVE_ALLOW_STAGING_SEED=true`). |
| Respaldo/restauración | `scripts/backup.sh`, `scripts/restore-test.sh` | `BACKUP … WITH CHECKSUM`, `RESTORE VERIFYONLY`, restauración en otra base, `DBCC CHECKDB` y comparación de conteos. |
| Humo y secretos | `scripts/smoke.sh`, `scripts/scan-secrets.sh` | Salud, cabeceras, rutas ocultas; busca secretos en logs, imagen y archivos `.env`. |
| Verificación automática | `.github/workflows/staging-verify.yml` | Levanta todo en un runner descartable y ejecuta los scripts anteriores. |

El empaquetado de releases existente (`.github/workflows/release-package.yml`) **no se duplica**: sigue produciendo el artefacto auditado con SHA-256. La imagen Docker es una vía alternativa de construcción del mismo commit; no publica ni despliega.

## Instalación (equipo de staging dedicado)

Requisitos: Docker Engine con Compose v2, `openssl`, acceso de salida a Docker Hub, MCR y Microsoft. **No** usar el equipo de desarrollo ni `RentaDriveDb`.

```bash
cd deploy/staging
STAGING_HOSTNAME=staging.<tu-dominio> ./scripts/gen-env.sh   # crea .env.staging y .env.app
./scripts/gen-sql-tls.sh                                     # CA y certificado de SQL Server
docker compose --env-file .env.staging build app web
docker compose --env-file .env.staging up -d --wait sqlserver
./scripts/init-db.sh
./scripts/migrate.sh                                         # migrate --force con la cuenta de migración
docker compose --env-file .env.staging up -d --wait web
docker compose --env-file .env.staging up -d queue scheduler
docker compose --env-file .env.staging exec app php artisan rentadrive:production-check
docker compose --env-file .env.staging exec app php artisan rentadrive:staging-seed
./scripts/smoke.sh
```

Guarda una copia de `.env.staging` en el gestor de secretos del operador. La contraseña del administrador ficticio está en `RENTADRIVE_STAGING_ADMIN_PASSWORD` (`.env.app`); cámbiala tras la primera entrada si el staging se comparte.

### Acceso (Cloudflare Access + túnel con nombre)

Orden obligatorio, ya descrito en `docs/cloudflare-private-staging.md` §2: **primero** la aplicación Access (política Allow solo para correos autorizados, MFA, sesión corta) y **después** el túnel. Con el túnel con nombre, el origen es `http://web:8080` dentro de la red `internal` (o `http://127.0.0.1:8080` si `cloudflared` corre en el host). El token va en `.env.staging` (`CLOUDFLARE_TUNNEL_TOKEN`), nunca en Git:

```bash
docker compose --env-file .env.staging --profile tunnel up -d cloudflared
```

`TRUSTED_PROXIES` ya apunta a la subred interna (`172.29.80.0/24`) para que Laravel reconozca HTTPS tras el túnel y nginx. **Bloqueado por dependencia externa:** dominio en Cloudflare, aplicación Access y túnel; no se crearon ni se compraron.

## Actualización

1. Construir la nueva imagen del commit aprobado de `main`: `RELEASE_TAG=<sha-corto> docker compose --env-file .env.staging build app web`.
2. **Respaldo previo:** `./scripts/backup.sh` (guarda la ruta del `.bak`).
3. Migraciones compatibles hacia adelante: `./scripts/migrate.sh --pretend` y luego `./scripts/migrate.sh`.
4. `RELEASE_TAG=<sha-corto> docker compose --env-file .env.staging up -d app web queue scheduler`.
5. `rentadrive:production-check` y `./scripts/smoke.sh`.

## Rollback

- **Código:** volver al `RELEASE_TAG` anterior (la imagen previa permanece local): `RELEASE_TAG=<anterior> docker compose … up -d app web queue scheduler`.
- **Esquema:** no se revierten migraciones con pérdida de datos. Usar migraciones expand/contract; si una migración dañó datos, restaurar el respaldo previo **en una base nueva**, verificar y recién entonces cambiar `DB_DATABASE`.

## Restauración (ensayo y real)

- **Ensayo (nunca sobre staging):** `./scripts/restore-test.sh /var/opt/mssql/backup/<archivo>.bak` restaura en `RentaDriveStagingRestoreTest`, ejecuta `DBCC CHECKDB`, compara conteos de tablas clave y elimina la base de ensayo.
- **Restauración real:** restaurar en una base nueva (`RentaDriveStagingYYYYMMDD`), validar con smoke y cambiar `DB_DATABASE` en `.env.staging`/`.env.app`; conservar la base anterior hasta confirmar. Las evidencias privadas (`storage/app/private`, volumen `appstorage`) deben respaldarse aparte.

## Respaldos, logs y monitoreo

- **Respaldos:** el volumen `sqlbackup` guarda los `.bak`. Copiarlos cifrados fuera del equipo (retención externa) es responsabilidad del operador; no está automatizado. Programar `backup.sh` (cron/systemd timer) y vigilar su resultado.
- **Logs:** `LOG_CHANNEL=stderr`, `LOG_LEVEL=warning`; consultar con `docker compose logs`. No enviar datos personales ni secretos a servicios externos. `scripts/scan-secrets.sh` comprueba que los secretos generados no aparezcan en logs ni en la imagen.
- **Monitoreo mínimo:** `GET /up` (healthcheck de `web`), estado de `queue`/`scheduler`, `jobs`/`failed_jobs`, espacio del volumen `sqldata`, caducidad de la CA interna (365 días). Alertas externas: pendiente.

## Qué verifica el CI y qué no

`staging-verify` (runner descartable, secretos aleatorios, datos ficticios) ejecuta: construcción de imágenes, PHP ≥ 8.4.27, SQL Server con TLS verificado, migraciones, `production-check`, siembra ficticia, humo HTTP, procesamiento de un trabajo por el worker, respaldo + `VERIFYONLY` + restauración en otra base con `CHECKDB` y conteos, y ausencia de secretos. El estado de cada ejecución está en la pestaña Actions; **si la última ejecución no está en verde, nada de lo anterior se considera probado**.

**No cubierto por este flujo:** Playwright contra el staging (requiere el hostname HTTPS real; las E2E corren en el job `e2e` de `ci.yml`), aislamiento multiempresa (lo cubren `TenantDataIsolationTest` y el resto de la suite en el job `test` sobre SQL Server), HTTPS real de extremo a extremo, Cloudflare Access, restauración con volumen de producción, carga y recuperación ante desastres.
