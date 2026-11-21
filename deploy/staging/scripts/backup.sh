#!/usr/bin/env bash
# Respaldo completo con CHECKSUM y verificación. Deja el .bak en el volumen sqlbackup.
. "$(dirname "$0")/lib.sh"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
FILE="/var/opt/mssql/backup/${STAGING_DB_NAME}-${STAMP}.bak"
sqlq -Q "BACKUP DATABASE [${STAGING_DB_NAME}] TO DISK = N'${FILE}' WITH CHECKSUM, COMPRESSION, INIT, NAME = N'RentaDrive staging ${STAMP}';"
sqlq -Q "RESTORE VERIFYONLY FROM DISK = N'${FILE}' WITH CHECKSUM;"
echo "${FILE}"
