#!/usr/bin/env bash
# Ejecuta una consulta T-SQL en el SQL Server de staging (contraseña por entorno). Uso: sql.sh "SELECT 1"
. "$(dirname "$0")/lib.sh"
sqlq -Q "$1"
