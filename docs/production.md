# RentaDrive — Runbook de producción

Este runbook define el mínimo operativo para desplegar RentaDrive como SaaS.

## Artefacto reproducible

El repositorio incluye Dockerfile con PHP 8.4, Apache y Microsoft ODBC Driver 18, build de Vite en Node 24, dependencias Composer fijadas por composer.lock, compose.production.yml con procesos separados para web/queue/scheduler y un workflow Release que publica imágenes inmutables en GHCR para tags v*.

La misma imagen se usa para web, workers y scheduler.

## Variables críticas

En producción:

~~~dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rentadrive.example.com

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

DB_CONNECTION=sqlsrv
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=false

QUEUE_CONNECTION=database
DB_QUEUE_AFTER_COMMIT=true

RENTADRIVE_PUBLIC_DISK=rentadrive_external_public
RENTADRIVE_PRIVATE_DISK=rentadrive_external_private
RENTADRIVE_BACKUP_DISK=rentadrive_external_private

RENTADRIVE_EXTERNAL_PUBLIC_PATH=/mnt/rentadrive/public
RENTADRIVE_EXTERNAL_PUBLIC_URL=https://cdn.example.com
RENTADRIVE_EXTERNAL_PRIVATE_PATH=/mnt/rentadrive/private

RENTADRIVE_BACKUP_RETENTION_DAYS=14
RENTADRIVE_BACKUP_REQUIRE_EXTERNAL=true
RENTADRIVE_OPS_EMAIL=ops@example.com
~~~

No guardar .env.production, claves, tokens, certificados ni credenciales en Git.

Los volúmenes externos deben mapearse a almacenamiento persistente/compartido del proveedor: NFS, SMB, EFS, Azure Files u otro volumen administrado. El adaptador s3 puede utilizarse cuando la instalación incorpora el adapter Flysystem correspondiente.

## Despliegue

Antes del cambio:

~~~bash
docker compose -f compose.production.yml pull
docker compose -f compose.production.yml run --rm app php artisan rentadrive:backup:create
~~~

Aplicar migraciones una sola vez:

~~~bash
docker compose -f compose.production.yml run --rm app php artisan migrate --force
docker compose -f compose.production.yml run --rm app php artisan optimize
docker compose -f compose.production.yml up -d
~~~

Verificar:

~~~bash
curl -f https://rentadrive.example.com/health/live
curl -f https://rentadrive.example.com/health/ready
~~~

## Backups y recuperación

RentaDrive crea diariamente un backup portátil a las 02:30. El snapshot está cifrado con la clave de aplicación, comprimido, protegido con checksum SHA-256, verificado automáticamente, registrado en backup_snapshots y sometido a retención configurable.

La clave APP_KEY debe custodiarse fuera del servidor junto con las credenciales de infraestructura. Sin ella no es posible descifrar los backups.

Operación:

~~~bash
php artisan rentadrive:backup:create
php artisan rentadrive:backup:verify <id>
php artisan rentadrive:backup:restore <id> --force
~~~

En producción, restore exige además --allow-production.

Antes de restaurar: poner la aplicación en mantenimiento, detener workers y scheduler, tomar un snapshot adicional de infraestructura, restaurar, validar /health/ready, iniciar procesos y retirar mantenimiento.

## Suscripciones

Los tenants pueden estar trialing, active, past_due dentro de gracia, expired, suspended o cancelled.

El middleware tenant valida tanto el estado de empresa como el entitlement de la suscripción. EnforceSubscriptionLifecycleJob se ejecuta cada hora.

El ledger es provider-neutral: manual permite operaciones administradas y external conserva la referencia de un sistema de cobro externo.

## Observabilidad

Endpoints:

~~~text
GET /health/live
GET /health/ready
~~~

Readiness valida SQL Server, cache, storage privado, tablas/estado de queue y antigüedad del último backup verificado.

MonitorPlatformHealthJob se ejecuta cada cinco minutos. Cuando el estado está degradado registra un evento critical, opcionalmente envía correo a RENTADRIVE_OPS_EMAIL y deduplica alertas equivalentes durante 30 minutos.

Cada respuesta incluye X-Request-Id para correlación.

## Hardening

RentaDrive aplica cookies de sesión cifradas, login rate-limited, rate limits separados para portal público/tenant/plataforma/webhooks, CSRF, URLs firmadas, HMAC para webhooks de pago, headers anti-sniff/anti-frame, referrer policy, permissions policy y COOP. En producción añade CSP y HSTS.

APP_DEBUG debe permanecer false y SQL Server debe usar cifrado TLS con un certificado confiable.

## Queue y scheduler

Procesos permanentes:

~~~bash
php artisan queue:work --queue=notifications,maintenance,default --tries=3 --backoff=60 --timeout=120
php artisan schedule:work
~~~

## Rollback

El rollback preferido es desplegar la imagen inmutable anterior. Si el cambio alteró datos de forma incompatible, desplegar la imagen anterior, restaurar el backup previo, verificar readiness y reabrir tráfico.

## Gate de release

Antes de publicar una versión deben pasar Composer audit, npm audit high, Vite production build, SQL Server 2022, migraciones y seed, Pint, PHPUnit, caches de configuración/rutas/vistas, tests de seguridad multi-tenant, creación/verificación/restore de backup y health readiness.
