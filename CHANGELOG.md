# Changelog

## [1.0.0-rc.1] — candidata
Commit candidato: `94254f9` (CI de `main` en verde). Etiqueta `v1.0.0-rc.1` **autorizada por el responsable el 22/11/2026**; debe crearla el operador (este entorno no puede crear etiquetas en GitHub). Ver `docs/rc1/release-notes.md`.

### Añadido
MFA TOTP, CSP con nonce, reintentos/alertas/salud/observabilidad, respaldos cifrados, staging Docker, integridad versionada del acuse de contrato, pruebas de autorización entre empresas y de rendimiento.

### Cambiado
ITBIS y totales calculados en centavos enteros; `UserRequest` responde 404 ante usuarios de otra empresa.

### Eliminado
`maatwebsite/excel` (sin uso).

### Seguridad
Ver `docs/security-audit.md`. Pendiente del operador: rotar la contraseña SQL antigua.
