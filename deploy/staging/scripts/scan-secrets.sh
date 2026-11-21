#!/usr/bin/env bash
# Busca los secretos generados (.env.staging) en logs de contenedores, storage/logs y capas de la imagen.
. "$(dirname "$0")/lib.sh"
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
"${DC[@]}" logs --no-color > "$TMP/compose.log" 2>&1 || true
"${DC[@]}" exec -T app sh -c 'cat storage/logs/*.log 2>/dev/null || true' > "$TMP/laravel.log"
docker run --rm --entrypoint sh "rentadrive-staging:${RELEASE_TAG:-local}" -c 'find / -xdev \( -name ".env" -o -name ".env.*" \) -not -path "/proc/*" 2>/dev/null; true' > "$TMP/envfiles.txt"
fail=0
for NAME in APP_KEY DB_PASSWORD DB_MIGRATOR_PASSWORD MSSQL_SA_PASSWORD RENTADRIVE_STAGING_ADMIN_PASSWORD; do
  VAL="${!NAME}"; VAL="${VAL#base64:}"
  [ -z "$VAL" ] && continue
  if grep -rqF -- "$VAL" "$TMP"; then echo "SECRETO ${NAME} PRESENTE en logs/artefactos"; fail=1; fi
done
if [ -s "$TMP/envfiles.txt" ]; then echo "Archivos .env dentro de la imagen:"; cat "$TMP/envfiles.txt"; fail=1; fi
[ "$fail" = 0 ] && echo "Sin secretos en logs ni en la imagen." 
exit "$fail"
