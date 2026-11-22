# Checklist del operador antes de publicar

- [ ] Rotar la contraseña del login SQL antiguo (`docs/sql-password-rotation.md`).
- [ ] Generar secretos nuevos fuera del repositorio (`.env`, `HEALTH_TOKEN` ≥ 32, `BACKUP_PASSPHRASE_FILE` modo 600).
- [ ] `php artisan rentadrive:production-check` sin errores.
- [ ] Activar `MFA_ENFORCE=true` y enrolar a cada administrador; guardar códigos de recuperación.
- [ ] Mantener `CSP_MODE=report-only` una semana en staging, revisar `/csp-report`, luego `enforce`.
- [ ] Configurar `RENTADRIVE_ALERT_EMAIL` y comprobar la alerta del monitor.
- [ ] Programar respaldos, destino externo y retención; **ensayar la restauración en staging real**.
- [ ] Validación manual: zoom 200/400 %, iOS/Android, VoiceOver/TalkBack/NVDA, Safari y Firefox.
- [ ] Decidir la regla de visibilidad por sucursal.
- [ ] Decidir sobre la PR #46 del tráiler (voz real, términos de uso de clips).
- [x] Etiqueta `v1.0.0-rc.1` autorizada (22/11/2026). - [ ] Crearla en `94254f9` (ver `docs/rc1/README.md`).
