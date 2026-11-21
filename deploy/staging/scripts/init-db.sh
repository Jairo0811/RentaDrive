#!/usr/bin/env bash
# Crea la base de staging y las cuentas (app y migraciones). Idempotente. Rechaza RentaDriveDb.
. "$(dirname "$0")/lib.sh"
[ "$STAGING_DB_NAME" = "RentaDriveDb" ] && { echo "STAGING_DB_NAME no puede ser RentaDriveDb." >&2; exit 1; }
"${DC[@]}" exec -T -e SQLCMDPASSWORD="$MSSQL_SA_PASSWORD" -e STAGING_DB_NAME -e DB_APP_PASSWORD -e DB_MIGRATOR_PASSWORD \
  sqlserver /opt/mssql-tools18/bin/sqlcmd -C -b -S localhost -U sa -i /init/01-init.sql
echo "Base $STAGING_DB_NAME lista."
