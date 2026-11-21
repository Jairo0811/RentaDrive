# Funciones compartidas por los scripts de staging.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
DC=(docker compose --env-file .env.staging -f docker-compose.yml)
set -a; . ./.env.staging; set +a

# sqlcmd dentro del contenedor, con contraseña por entorno (no por argumentos)
sqlq() {
  "${DC[@]}" exec -T -e SQLCMDPASSWORD="$MSSQL_SA_PASSWORD" sqlserver /opt/mssql-tools18/bin/sqlcmd -C -b -h -1 -W -S localhost -U sa "$@"
}
