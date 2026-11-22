#!/usr/bin/env bash
# Ensayo de recuperación desde un respaldo exportado y cifrado: verifica, descifra, lo coloca en el contenedor y lo restaura
# en una base DISTINTA (restore-test.sh). Nunca restaura sobre la base de staging.
# Uso: restore-from-export.sh /ruta/<archivo>.bak.gpg   (requiere BACKUP_PASSPHRASE_FILE)
HERE="$(cd "$(dirname "$0")" && pwd)"
. "$HERE/lib.sh"
. "$HERE/backup-common.sh"
require_passphrase
FILE="${1:?ruta del .gpg}"
case "$FILE" in /*) ;; *) FILE="$OLDPWD/$FILE" ;; esac
"$HERE/verify-backup.sh" "$FILE"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"; "${DC[@]}" exec -T -u 0 sqlserver rm -f /var/opt/mssql/backup/import-restore.bak || true' EXIT
umask 077
gpg --batch --quiet --pinentry-mode loopback --passphrase-file "$BACKUP_PASSPHRASE_FILE" --decrypt --output "${TMP}/import-restore.bak" "$FILE"
"${DC[@]}" cp "${TMP}/import-restore.bak" sqlserver:/var/opt/mssql/backup/import-restore.bak
"${DC[@]}" exec -T -u 0 sqlserver chown 10001:0 /var/opt/mssql/backup/import-restore.bak
"$HERE/restore-test.sh" /var/opt/mssql/backup/import-restore.bak
