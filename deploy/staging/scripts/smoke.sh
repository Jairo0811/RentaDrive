#!/usr/bin/env bash
# Pruebas de humo HTTP contra el origen de staging (loopback). Con -H X-Forwarded-Proto simula al túnel.
set -euo pipefail
BASE="${1:-http://127.0.0.1:8080}"
HOST_HDR="${STAGING_HOSTNAME:-staging.invalid}"
fail=0
check() { if eval "$2"; then echo "OK   $1"; else echo "FALLA $1"; fail=1; fi; }
req() { curl -s -o /dev/null -w '%{http_code}' -H "Host: ${HOST_HDR}" -H 'X-Forwarded-Proto: https' "$@"; }

check "/up responde 200" '[ "$(req "$BASE/up")" = 200 ]'
check "/login responde 200" '[ "$(req "$BASE/login")" = 200 ]'
check "/.env no accesible" '[ "$(req "$BASE/.env")" != 200 ]'
check "storage/logs no accesible" '[ "$(req "$BASE/storage/logs/laravel.log")" != 200 ]'
check "ruta protegida redirige a login" '[ "$(req "$BASE/dashboard")" = 302 ]'
H="$(curl -s -D - -o /dev/null -H "Host: ${HOST_HDR}" -H 'X-Forwarded-Proto: https' "$BASE/login")"
check "X-Content-Type-Options" 'echo "$H" | grep -qi "^x-content-type-options: nosniff"'
check "X-Frame-Options" 'echo "$H" | grep -qi "^x-frame-options: DENY"'
check "HSTS tras proxy HTTPS de confianza" 'echo "$H" | grep -qi "^strict-transport-security:"'
check "cookie con Secure y HttpOnly" 'echo "$H" | grep -i "^set-cookie:" | grep -qi "secure" && echo "$H" | grep -i "^set-cookie:" | grep -qi "httponly"'
check "no expone versión de servidor" '! echo "$H" | grep -qi "^x-powered-by:"'
exit "$fail"
