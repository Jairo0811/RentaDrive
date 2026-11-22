# Resiliencia operativa y observabilidad

## Avisos automáticos (`rentadrive:automations`, 08:00 diario)

- **Desactivados por defecto**; requieren `RENTA_AUTOMATION_ENABLED=true`, un proveedor de correo real y el opt-in de cada empresa. No se envía nada real sin esa configuración.
- **Idempotencia**: un registro único por empresa + evento + destinatario + canal (índice único). Una carrera entre procesos no duplica ni aborta la ejecución.
- **Reintentos con retroceso**: un envío fallido se reintenta hasta **3 intentos**, esperando 15 min × intentos ya realizados; un registro `queued` atascado (caída del proceso) se reclama de forma atómica tras el mismo retroceso. Un aviso ya enviado jamás se repite.
- **Aislamiento**: el fallo de una empresa se registra (`company_id`, clase de excepción, sin datos personales) y no detiene a las demás.
- `rentadrive:automations` y `rentadrive:expire-trials` usan `withoutOverlapping()`.

## Cola y planificador

- Worker (compose de staging): `queue:work --tries=3 --backoff=10,60,300 --timeout=60 --max-time=3600`.
- `queue:prune-failed --hours=720` diario (los trabajos fallidos se conservan 30 días).
- Latido del scheduler: `rentadrive:heartbeat` cada minuto (caché). Si deja de latir, el estado pasa a *degraded* (obligatorio en producción; `HEALTH_REQUIRE_SCHEDULER`).
- Hoy el sistema no encola trabajos propios (solo correo); la cola está monitoreada para cuando se añadan.

## Salud y alertas

| Endpoint / comando | Acceso | Contenido |
| --- | --- | --- |
| `GET /up` | público | Laravel: la aplicación arranca. |
| `GET /api/health` | público, 60/min | `{"status":"ok"}` o `down` (503) según SQL Server. Nada más. |
| `GET /api/health/details` | `Authorization: Bearer $HEALTH_TOKEN` (≥ 32 car.), 20/min | Base de datos (latencia), cola (pendientes, antigüedad, fallidos), scheduler (edad del latido) y códigos de problema. Sin token configurado devuelve 404. Nunca incluye hosts, credenciales ni trazas. |
| `php artisan rentadrive:monitor` | consola / scheduler cada 5 min | Evalúa lo anterior; si hay degradación registra `operations.degraded` (nivel *critical*), sale con código 1 y, **solo si `RENTADRIVE_ALERT_EMAIL` está configurado**, envía como máximo un correo por hora. |

Umbrales (variables): `HEALTH_MAX_QUEUE_AGE` (900 s), `HEALTH_MAX_FAILED_JOBS` (5), `HEALTH_MAX_SCHEDULER_AGE` (180 s).
Si el scheduler muere, `rentadrive:monitor` tampoco corre: usa un monitor externo sobre `/api/health/details` o un cron del sistema que ejecute el comando.

## Logging estructurado y correlación

- `LOG_STACK=json` escribe `storage/logs/rentadrive.json.log` (rotación diaria, 14 archivos): una línea JSON por evento con `request_id`.
- Cada petición recibe un `X-Request-Id` (se acepta el entrante solo si cumple `[A-Za-z0-9._-]{8,64}`; si no, se genera un UUID) que se devuelve en la respuesta y viaja en el *Context* de Laravel hacia logs y trabajos en cola.
- Los eventos de seguridad (`security.mfa`, `security.csp_violation`) no contienen secretos.
- Mantén `LOG_LEVEL` en `warning` o superior en producción (lo exige `rentadrive:production-check`).

## Limitaciones conocidas

- No hay un sistema de alertas multicanal (Slack, SMS, PagerDuty): solo log y correo opcional. Integrarlos requiere cuentas del operador.
- Métricas tipo Prometheus y trazas distribuidas no están implementadas.
- La verificación del scheduler depende de la caché configurada (`CACHE_STORE` persistente en producción).
