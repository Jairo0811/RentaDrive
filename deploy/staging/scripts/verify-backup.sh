#!/usr/bin/env bash
# Verifica un respaldo exportado: huella SHA-256 y descifrado con comprobación de integridad (no restaura nada).
# Uso: verify-backup.sh /ruta/<archivo>.bak.gpg   (requiere BACKUP_PASSPHRASE_FILE y el .sha256 junto al archivo)
HERE="$(cd "$(dirname "$0")" && pwd)"
. "$HERE/lib.sh"
. "$HERE/backup-common.sh"
require_passphrase
FILE="${1:?ruta del .gpg}"
case "$FILE" in /*) ;; *) FILE="$OLDPWD/$FILE" ;; esac
(cd "$(dirname "$FILE")" && sha256sum --check --status "$(basename "$FILE").sha256") || { echo "HUELLA SHA-256 NO COINCIDE: el archivo está dañado o fue alterado." >&2; exit 1; }
gpg --batch --quiet --pinentry-mode loopback --passphrase-file "$BACKUP_PASSPHRASE_FILE" --decrypt "$FILE" > /dev/null \
  || { echo "NO SE PUDO DESCIFRAR (clave incorrecta o contenido alterado)." >&2; exit 1; }
echo "RESPALDO EXPORTADO VERIFICADO (SHA-256 y descifrado con integridad)."
