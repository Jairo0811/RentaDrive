# Reversión y dependencias externas

## Reversión
1. Respaldo verificado previo (`docs/backup-recovery.md`).
2. Código: volver a la imagen/commit anterior (`docs/staging-private.md`, sección rollback).
3. Base de datos: las migraciones del ciclo RC (MFA, reintentos de automatización, versión de acuse) solo **añaden** columnas con valores por defecto; `down()` las elimina. Si hay que volver atrás, restaurar el respaldo es más seguro que ejecutar `down()` con datos nuevos.
4. Verificar `/up`, `/api/health`, inicio de sesión y `rentadrive:production-check`.

## BLOQUEADA POR DEPENDENCIA EXTERNA
| Elemento | Qué falta |
|---|---|
| Dominio y Cloudflare Access / túnel | Cuenta y dominio del operador |
| Pasarela de pago | Proveedor, contrato y credenciales |
| e-CF / NCF | Proveedor autorizado y certificación DGII |
| Correo/WhatsApp/SMS transaccional | Proveedor y credenciales |
| Almacenamiento externo de respaldos | Destino (`BACKUP_UPLOAD_COMMAND`) |
| Playwright contra staging real | Nombre de host del staging |
