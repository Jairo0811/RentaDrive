# Rotación de la contraseña del login SQL (procedimiento del operador)

> **Quién lo ejecuta:** el operador, a mano. Este documento no contiene contraseñas, no cambia nada solo y
> ninguna herramienta del repositorio ejecuta estos pasos. Motivo: una contraseña SQL anterior estuvo en el
> historial de git; debe considerarse comprometida aunque ya no esté en el árbol actual.

## Reglas
- Genera la contraseña nueva con un gestor de contraseñas (24+ caracteres aleatorios). No la escribas en chat, tickets, commits ni logs.
- Hazlo en una ventana de mantenimiento: la app fallará entre el cambio en SQL y la actualización del `.env`.
- Haz un respaldo verificado antes (`docs/backup-recovery.md`).
- Nunca lo pruebes primero en `RentaDriveDb` si puedes ensayar en una copia/staging.

## Pasos
1. **Respaldo** verificado de `RentaDriveDb` (`verify-backup.sh`).
2. **Identifica los logins** que usó el historial antiguo (SSMS → Security → Logins, o `SELECT name, is_disabled FROM sys.sql_logins;`).
3. **Cambia la contraseña** con una sesión interactiva, pidiendo el valor en pantalla (no en el historial de comandos):
   ```sql
   -- Ejecutar en SSMS/sqlcmd interactivo con un login administrador. Escribe la contraseña nueva donde indica.
   ALTER LOGIN [<nombre_del_login>] WITH PASSWORD = N'<NUEVA_CONTRASEÑA>';
   ```
   Si el login ya no se usa, es preferible deshabilitarlo: `ALTER LOGIN [<login>] DISABLE;`
4. **Actualiza el secreto en el servidor**: edita `.env` (`DB_PASSWORD`) o el archivo de secretos con permisos `600`, fuera del repositorio.
5. **Limpia cachés** y reinicia procesos: `php artisan config:clear && php artisan config:cache`, reiniciar php-fpm, `queue:restart`, scheduler.
6. **Verifica**: `php artisan rentadrive:production-check`, `GET /up`, `/api/health` y un inicio de sesión de prueba.
7. **Revoca accesos residuales**: cierra sesiones activas del login antiguo (`KILL <spid>`), revisa que no queden logins duplicados con la clave vieja.
8. **Registra** la fecha y el responsable en tu bitácora (sin la contraseña).

## Reversión
Si la app no conecta, vuelve a poner en `.env` la contraseña nueva correcta (el fallo suele ser un carácter especial sin
entrecomillar en `.env`: usa comillas dobles). No restaures la contraseña antigua: está comprometida.

## Qué NO hacer
- No reescribir el historial de git como sustituto de la rotación: los clones y forks existentes conservarían la clave.
- No reutilizar la contraseña en otros sistemas ni guardarla en GitHub (Secrets del repo solo para staging descartable).
