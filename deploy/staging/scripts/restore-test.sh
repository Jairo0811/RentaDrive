#!/usr/bin/env bash
# Restaura un .bak en una base DISTINTA (ensayo) y compara conteos con el origen. Nunca restaura sobre staging.
# Uso: restore-test.sh /var/opt/mssql/backup/<archivo>.bak
. "$(dirname "$0")/lib.sh"
BAK="${1:?ruta del .bak dentro del contenedor}"
TARGET="${STAGING_DB_NAME}RestoreTest"
[ "$TARGET" = "$STAGING_DB_NAME" ] && { echo "El destino coincide con el origen." >&2; exit 1; }
sqlq -Q "IF DB_ID(N'${TARGET}') IS NOT NULL BEGIN ALTER DATABASE [${TARGET}] SET SINGLE_USER WITH ROLLBACK IMMEDIATE; DROP DATABASE [${TARGET}]; END;"
LIST="$(sqlq -Q "SET NOCOUNT ON; RESTORE FILELISTONLY FROM DISK = N'${BAK}';" -s '|')"
DATA="$(echo "$LIST" | awk -F'|' '$3=="D"{print $1; exit}')"
LOG="$(echo "$LIST" | awk -F'|' '$3=="L"{print $1; exit}')"
sqlq -Q "RESTORE DATABASE [${TARGET}] FROM DISK = N'${BAK}' WITH CHECKSUM, MOVE N'${DATA}' TO N'/var/opt/mssql/data/${TARGET}.mdf', MOVE N'${LOG}' TO N'/var/opt/mssql/data/${TARGET}_log.ldf', REPLACE;"
sqlq -Q "DBCC CHECKDB (N'${TARGET}') WITH NO_INFOMSGS;"
FAIL=0
for T in migrations users companies branches vehicles customers; do
  A="$(sqlq -Q "SET NOCOUNT ON; SELECT COUNT(*) FROM [${STAGING_DB_NAME}].dbo.[${T}];")"
  B="$(sqlq -Q "SET NOCOUNT ON; SELECT COUNT(*) FROM [${TARGET}].dbo.[${T}];")"
  echo "tabla ${T}: origen=${A} restaurada=${B}"
  [ "$A" = "$B" ] || FAIL=1
done
sqlq -Q "ALTER DATABASE [${TARGET}] SET SINGLE_USER WITH ROLLBACK IMMEDIATE; DROP DATABASE [${TARGET}];"
[ "$FAIL" = 0 ] && echo "RESTAURACIÓN VERIFICADA (conteos iguales, CHECKDB sin errores)." || { echo "CONTEOS DISTINTOS" >&2; exit 1; }
