#!/usr/bin/env bash
# Exporta un respaldo FUERA del volumen: lo copia, lo cifra (AES-256 con integridad autenticada, gpg), calcula su SHA-256
# y, si el operador lo configura, invoca un gancho de subida a almacenamiento externo. No guarda ninguna clave.
#
# Uso:  export-backup.sh /var/opt/mssql/backup/<archivo>.bak      (la última línea de salida es la ruta del .gpg)
# Variables:
#   BACKUP_PASSPHRASE_FILE  (obligatoria) archivo con la frase de cifrado, FUERA del repositorio, permisos 600, >= 24 caracteres.
#   BACKUP_EXPORT_DIR       (opcional, ./backups) directorio local de exportación (permisos 700).
#   BACKUP_UPLOAD_COMMAND   (opcional) ejecutable propio del operador (p. ej. un script con rclone/aws s3 cp); recibe
#                           la ruta del .gpg y la del .sha256. Las credenciales del destino son de ese script, no de este repositorio.
HERE="$(cd "$(dirname "$0")" && pwd)"
. "$HERE/lib.sh"
BAK="${1:?ruta del .bak dentro del contenedor}"
. "$HERE/backup-common.sh"
require_passphrase

EXPORT="${BACKUP_EXPORT_DIR:-./backups}"
umask 077
mkdir -p "$EXPORT"
chmod 700 "$EXPORT"
NAME="$(basename "$BAK")"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

"${DC[@]}" cp "sqlserver:${BAK}" "${TMP}/${NAME}"
gpg --batch --yes --quiet --pinentry-mode loopback --passphrase-file "$BACKUP_PASSPHRASE_FILE" \
    --symmetric --cipher-algo AES256 --s2k-mode 3 --s2k-count 65011712 --s2k-digest-algo SHA512 \
    --output "${EXPORT}/${NAME}.gpg" "${TMP}/${NAME}"
(cd "$EXPORT" && sha256sum "${NAME}.gpg" > "${NAME}.gpg.sha256")

if [ -n "${BACKUP_UPLOAD_COMMAND:-}" ]; then
  "$BACKUP_UPLOAD_COMMAND" "${EXPORT}/${NAME}.gpg" "${EXPORT}/${NAME}.gpg.sha256"
  echo "Gancho de subida ejecutado (el éxito real del destino es responsabilidad del operador)." >&2
fi
echo "${EXPORT}/${NAME}.gpg"
