#!/usr/bin/env bash
# Genera deploy/staging/.env.staging con secretos aleatorios (permisos 600, ignorado por Git).
# No sobrescribe un archivo existente. Uso: STAGING_HOSTNAME=staging.ejemplo.com ./gen-env.sh
set -euo pipefail
cd "$(dirname "$0")/.."
OUT=".env.staging"
[ -e "$OUT" ] || [ -e .env.app ] && { echo "$OUT o .env.app ya existen; no se sobrescriben." >&2; exit 1; }

rand() { openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | cut -c1-"${1:-28}"; }
HOST="${STAGING_HOSTNAME:-staging.invalid}"
SA="$(rand 28)Aa1!"
APP_PW="$(rand 28)Aa1!"
MIG_PW="$(rand 28)Aa1!"
ADMIN_PW="$(rand 24)-Aa1#x"

umask 077
cat > "$OUT" <<ENV
# Generado por gen-env.sh. NO versionar. Guardar una copia en el gestor de secretos del operador.
RELEASE_TAG=local
STAGING_HOSTNAME=${HOST}
STAGING_DB_NAME=RentaDriveStaging

APP_NAME=RentaDrive
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:$(openssl rand -base64 32)
APP_URL=https://${HOST}
APP_TIMEZONE=America/Santo_Domingo
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
LOG_CHANNEL=stderr
LOG_LEVEL=warning
TRUSTED_PROXIES=172.29.80.0/24

DB_CONNECTION=sqlsrv
DB_HOST=sqlserver
DB_PORT=1433
DB_DATABASE=RentaDriveStaging
DB_USERNAME=rentadrive_staging_app
DB_PASSWORD=${APP_PW}
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=false

DB_APP_PASSWORD=${APP_PW}
DB_MIGRATOR_USERNAME=rentadrive_staging_migrator
DB_MIGRATOR_PASSWORD=${MIG_PW}
MSSQL_SA_PASSWORD=${SA}

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
RENTA_EVIDENCE_DISK=local
RENTA_AUTOMATION_ENABLED=false
MAIL_MAILER=log

# Datos ficticios de staging
RENTADRIVE_ALLOW_STAGING_SEED=true
RENTADRIVE_STAGING_ADMIN_PASSWORD=${ADMIN_PW}

# Solo para el perfil "tunnel" (token del túnel con nombre; nunca en Git)
# CLOUDFLARE_TUNNEL_TOKEN=
ENV
# Subconjunto para los contenedores de la aplicación: sin la contraseña de SA, la de migraciones ni el token del túnel.
grep -Ev '^(MSSQL_SA_PASSWORD|DB_MIGRATOR_|DB_APP_PASSWORD|CLOUDFLARE_|RELEASE_TAG|STAGING_|#)' "$OUT" | grep -v '^$' > .env.app
chmod 600 .env.app
echo "Creado $OUT y .env.app (permisos 600). Revísalos y guarda una copia segura."
