#!/usr/bin/env bash
# Migraciones NO destructivas con la cuenta de migración (nunca migrate:fresh / db:wipe).
. "$(dirname "$0")/lib.sh"
"${DC[@]}" run --rm -T -e DB_USERNAME="$DB_MIGRATOR_USERNAME" -e DB_PASSWORD="$DB_MIGRATOR_PASSWORD" app php artisan migrate --force "$@"
