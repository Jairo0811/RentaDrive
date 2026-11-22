# Respaldos y recuperación

Alcance: SQL Server 2022 de staging/producción. Los scripts viven en `deploy/staging/scripts/` y se ejercitan en CI
(`staging-verify`) sobre un runner **descartable con datos ficticios**. Nada de esto toca `RentaDriveDb` ni restaura sobre la base en uso.

## Flujo

| Paso | Comando | Qué garantiza |
| --- | --- | --- |
| 1. Respaldo | `./scripts/backup.sh` | `BACKUP … WITH CHECKSUM, COMPRESSION`, `RESTORE VERIFYONLY` y huella `.sha256` junto al `.bak`. |
| 2. Exportar | `BACKUP_PASSPHRASE_FILE=… ./scripts/export-backup.sh <ruta .bak>` | Copia fuera del volumen, cifrado **AES-256 autenticado** (gpg, S2K SHA-512), huella SHA-256 del archivo cifrado y gancho de subida opcional. |
| 3. Verificar | `./scripts/verify-backup.sh <archivo.bak.gpg>` | SHA-256 + descifrado con comprobación de integridad (detecta clave errónea o manipulación). No restaura. |
| 4. Ensayar | `./scripts/restore-from-export.sh <archivo.bak.gpg>` | Verifica, descifra y restaura en **otra base**, ejecuta `DBCC CHECKDB` y compara conteos; elimina la base de ensayo. |
| 5. Retención | `./scripts/prune-backups.sh` | Borra lo anterior a `BACKUP_RETENTION_DAYS` (14) conservando siempre los `BACKUP_MIN_KEEP` (3) más recientes, en volumen y exportados. |

### Variables

- `BACKUP_PASSPHRASE_FILE` (obligatoria para exportar/verificar): archivo **fuera del repositorio**, permisos `600`, ≥ 24 caracteres. Genera una frase aleatoria
  (`head -c 48 /dev/urandom | base64 -w0 > /ruta/segura`) y **guárdala también en un gestor de contraseñas o caja fuerte**: sin ella el respaldo es irrecuperable.
- `BACKUP_EXPORT_DIR` (`./backups`, ignorado por git), `BACKUP_RETENTION_DAYS`, `BACKUP_MIN_KEEP`.
- `BACKUP_UPLOAD_COMMAND`: ejecutable propio del operador que recibe la ruta del `.gpg` y la del `.sha256` (p. ej. un script con `rclone`, `aws s3 cp` o `scp`).
  Las credenciales del destino pertenecen a ese script/entorno del operador; **este repositorio no contiene ninguna y no se contrató ningún servicio**.

## Programación sugerida (operador)

    # cron/systemd: diario 02:30 — respaldo, exportación cifrada, verificación y retención
    BAK=$(./scripts/backup.sh | tail -1) && ENC=$(./scripts/export-backup.sh "$BAK" | tail -1) \
      && ./scripts/verify-backup.sh "$ENC" && ./scripts/prune-backups.sh

Vigila el código de salida (≠ 0 = fallo) y ensaya `restore-from-export.sh` al menos una vez al mes en un entorno descartable.

## Objetivos orientativos (a validar por el operador)

RPO = frecuencia del respaldo (diario → hasta 24 h de datos). RTO = tiempo de `restore-from-export.sh` sobre datos reales + conmutación de `DB_DATABASE`.
Para RPO menor, añade respaldos del log de transacciones (modelo de recuperación FULL), que **no están automatizados aquí**.

## Restauración real tras un incidente

1. No restaures sobre la base dañada: restaura en una base **nueva** (`restore-from-export.sh` lo hace para el ensayo; para producción repite los pasos de `restore-test.sh` con el nombre definitivo).
2. Verifica `DBCC CHECKDB` y conteos; ejecuta `php artisan migrate --pretend` para confirmar que el esquema coincide con la versión desplegada.
3. Cambia `DB_DATABASE`, vacía cachés (`php artisan optimize:clear`) y vuelve a levantar app/cola/scheduler.
4. Conserva la base dañada hasta cerrar el análisis.

## Qué está probado y qué no

- **Probado en CI (datos ficticios, runner descartable):** respaldo con CHECKSUM, `VERIFYONLY`, cifrado/descifrado, rechazo de frase incorrecta, rechazo de archivo alterado, rechazo de frase con permisos abiertos,
  restauración desde el archivo cifrado con `CHECKDB` y conteos iguales, y la lógica de retención.
- **No probado:** el gancho de subida (depende del destino del operador), restauración con volúmenes reales y tiempos de RTO, respaldos de log de transacciones, rotación de la frase de cifrado.
- Una restauración solo se considera demostrada cuando el workflow `staging-verify` de ese commit está en verde.

## Rotación de la frase de cifrado

Los respaldos antiguos siguen requiriendo la frase con la que se cifraron. Para rotar: conserva la frase anterior mientras existan respaldos que la usan, exporta los nuevos con la frase nueva y retira la anterior cuando expire el último respaldo asociado.
