#!/usr/bin/env bash
# Retención: borra respaldos más antiguos que BACKUP_RETENTION_DAYS (14) conservando SIEMPRE los BACKUP_MIN_KEEP (3) más recientes.
# Aplica al volumen sqlbackup (.bak) y al directorio de exportación (.gpg). No toca almacenamiento externo (política del operador).
HERE="$(cd "$(dirname "$0")" && pwd)"
. "$HERE/lib.sh"
RET="${BACKUP_RETENTION_DAYS:-14}"
KEEP="${BACKUP_MIN_KEEP:-3}"
case "$RET$KEEP" in *[!0-9]*|"") echo "BACKUP_RETENTION_DAYS y BACKUP_MIN_KEEP deben ser enteros." >&2; exit 2 ;; esac
[ "$KEEP" -ge 1 ] || { echo "BACKUP_MIN_KEEP debe ser >= 1." >&2; exit 2; }

# Se ejecuta tanto en el host como dentro del contenedor (sh POSIX). Entra en el directorio $1 y filtra por extensión $2.
PRUNE='
cd "$1" 2>/dev/null || exit 0
n=0
for f in $(ls -1t -- *"$2" 2>/dev/null); do
  n=$((n+1))
  [ "$n" -le "$KEEP" ] && continue
  if [ -n "$(find "$f" -mtime +"$RET" -print 2>/dev/null)" ]; then
    rm -f -- "$f" "$f.sha256"
    echo "eliminado: $f"
  fi
done
'
EXPORT="${BACKUP_EXPORT_DIR:-./backups}"
RET="$RET" KEEP="$KEEP" sh -c "$PRUNE" _ "$EXPORT" ".gpg"
"${DC[@]}" exec -T -e RET="$RET" -e KEEP="$KEEP" sqlserver sh -c "$PRUNE" _ /var/opt/mssql/backup ".bak"
echo "Retención aplicada (${RET} días, mínimo ${KEEP} respaldos conservados)."
