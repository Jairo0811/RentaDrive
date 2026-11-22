#!/usr/bin/env bash
# Respaldo completo con CHECKSUM, verificación y huella SHA-256. Deja el .bak (y su .sha256) en el volumen sqlbackup.
# La ÚLTIMA línea de la salida es la ruta del .bak dentro del contenedor.
. "$(dirname "$0")/lib.sh"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
FILE="/var/opt/mssql/backup/${STAGING_DB_NAME}-${STAMP}.bak"
sqlq -Q "BACKUP DATABASE [${STAGING_DB_NAME}] TO DISK = N'${FILE}' WITH CHECKSUM, COMPRESSION, INIT, NAME = N'RentaDrive staging ${STAMP}';"
sqlq -Q "RESTORE VERIFYONLY FROM DISK = N'${FILE}' WITH CHECKSUM;"
"${DC[@]}" exec -T sqlserver sh -c "cd /var/opt/mssql/backup && sha256sum '$(basename "$FILE")' > '$(basename "$FILE").sha256'"
echo "${FILE}"
