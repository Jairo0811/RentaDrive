# RentaDrive — Operación y salida SaaS (Fase 7)

**Estado:** preparación técnica para despliegue. Este repositorio no provisiona ni publica un entorno real. La facturación recurrente de suscripciones, cobros online y emisión e-CF siguen pendientes.

## Arquitectura de referencia

- PHP 8.4.27+ y Laravel 13 con PHP-FPM detrás de Nginx/Apache y TLS en el balanceador.
- SQL Server 2022 con conexión cifrada y certificado validado, cuenta de mínimos privilegios, backups y retención externa.
- Cache/colas persistentes (Redis o SQL Server), workers supervisados y scheduler.
- Evidencias privadas compartidas: RENTA_EVIDENCE_DISK=s3, bucket privado, cifrado, IAM mínimo, versionado y retención.
- SMTP autenticado; RENTA_AUTOMATION_ENABLED=false hasta validar remitente y consentimiento por empresa.
- No publicar storage/app/private ni el bucket. Acceso a evidencias mediante rutas autenticadas y scopes multiempresa.

## Preparación de configuración

Usar .env.production.example como referencia, **nunca** como archivo de secretos real. Configurar APP_KEY desde un gestor de secretos, APP_ENV=production, APP_DEBUG=false, APP_URL=https://..., SESSION_SECURE_COOKIE=true, SESSION_ENCRYPT=true, DB_ENCRYPT=yes, DB_TRUST_SERVER_CERTIFICATE=false, QUEUE_CONNECTION=database/redis y CACHE_STORE=database/redis.

Antes de abrir tráfico:

    php artisan rentadrive:production-check
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

El preflight comprueba configuración, SQL Server, cache y lectura/escritura en el disco de evidencias. **No equivale** a auditoría, test de carga ni recuperación real.

Si existe proxy inverso, configurar y restringir proxies de confianza para que HTTPS sea detectado correctamente antes de habilitar HSTS. Publicar exclusivamente el directorio public/.

## Operación continua

Ejecutar php artisan schedule:run cada minuto desde un scheduler controlado. Supervisar php artisan queue:work --tries=3 --timeout=60 con systemd/Supervisor, reinicios y alertas. El comando rentadrive:expire-trials suspende trials vencidos de forma idempotente y deja historial. rentadrive:automations requiere opt-in de entorno y de empresa.

Vigilar: disponibilidad HTTP, errores de Laravel, jobs fallidos, colas, tiempos de respuesta, bloqueos y espacio SQL Server, resultados de backup, correo, evidencias, TLS y accesos denegados. Evitar enviar datos personales o secretos a sistemas de observabilidad.

## Backup y recuperación

Definir RPO/RTO, backups completos, diferenciales y de log de SQL Server según el modelo de recuperación, con cifrado, CHECKSUM, retención externa y control de acceso. Ejemplo ilustrativo para un DBA (reemplazar la ruta por una ubicación real y segura):

    BACKUP DATABASE [RentaDriveDb]
    TO DISK = N'<RUTA_DE_BACKUP_SEGURA>'
    WITH COPY_ONLY, CHECKSUM, COMPRESSION, INIT;

    RESTORE VERIFYONLY
    FROM DISK = N'<RUTA_DE_BACKUP_SEGURA>'
    WITH CHECKSUM;

RESTORE VERIFYONLY **no prueba** que se pueda recuperar todo. Ensayar restauración completa en un entorno aislado, incluyendo evidencias privadas, secretos, configuraciones y dependencias. Nunca restaurar sobre producción como parte de un simulacro.

## Entrega reproducible y rollback

.github/workflows/release-package.yml genera manualmente o en tags v* un artefacto inmutable de runtime y assets con checksum SHA-256, solamente desde el HEAD actual de main. No despliega ni inyecta secretos.

Para desplegar: verificar checksum, hacer backup y probar restauración, activar mantenimiento, publicar release en directorio versionado, aplicar migraciones compatibles, ejecutar preflight y smoke tests de login, reservas y aislamiento multiempresa. Cambiar tráfico tras validación. Mantener la release anterior para rollback de código. No revertir migraciones con pérdida de datos: usar estrategia expand/contract y recuperación por DBA.

## Pendientes de comercialización

- Contratar e integrar pasarela real, conciliación, cobros recurrentes y gestión de suscripciones. Los planes/estados actuales son manuales y su historial no demuestra pagos.
- Integrar firma electrónica de cliente y e-CF certificado ante DGII.
- Migrar fotos legacy públicas al almacenamiento privado compartido y retirar las copias accesibles.
- Provisionar DNS, TLS, secretos, SQL Server, S3, correo, alertas, backups y respuesta a incidentes.
- Ejecutar pruebas de seguridad, carga, restauración y E2E en infraestructura real.
- Definir SLA, soporte, privacidad, consentimiento y retención antes de pilotos pagados.
