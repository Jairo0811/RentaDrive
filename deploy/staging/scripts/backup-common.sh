# Funciones comunes de exportación/verificación de respaldos (se carga después de lib.sh).
require_passphrase() {
  local f="${BACKUP_PASSPHRASE_FILE:-}"
  [ -n "$f" ] || { echo "Define BACKUP_PASSPHRASE_FILE (archivo con la frase de cifrado, fuera del repositorio)." >&2; exit 2; }
  [ -f "$f" ] || { echo "No existe el archivo de frase indicado." >&2; exit 2; }
  case "$(stat -c '%a' "$f")" in
    ?00) ;;
    *) echo "El archivo de frase debe tener permisos 600 (sin acceso de grupo/otros)." >&2; exit 2 ;;
  esac
  [ "$(wc -c < "$f")" -ge 24 ] || { echo "La frase de cifrado debe tener al menos 24 caracteres." >&2; exit 2; }
}
