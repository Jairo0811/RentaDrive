#!/usr/bin/env bash
# Genera una CA interna y un certificado de servidor para SQL Server (SAN: sqlserver). No se versiona.
set -euo pipefail
cd "$(dirname "$0")/.."
D="secrets/tls"
[ -e "$D/ca.crt" ] && { echo "$D ya existe; no se sobrescribe." >&2; exit 1; }
mkdir -p "$D"
openssl genrsa -out "$D/ca.key" 4096 2>/dev/null
openssl req -x509 -new -key "$D/ca.key" -sha256 -days 365 -subj "/CN=RentaDrive Staging CA" -out "$D/ca.crt"
openssl genrsa -out "$D/mssql.key" 2048 2>/dev/null
openssl req -new -key "$D/mssql.key" -subj "/CN=sqlserver" -out "$D/mssql.csr"
printf 'subjectAltName=DNS:sqlserver,DNS:localhost\nextendedKeyUsage=serverAuth\n' > "$D/san.ext"
openssl x509 -req -in "$D/mssql.csr" -CA "$D/ca.crt" -CAkey "$D/ca.key" -CAcreateserial -days 365 -sha256 -extfile "$D/san.ext" -out "$D/mssql.pem" 2>/dev/null
rm -f "$D/mssql.csr" "$D/san.ext" "$D/ca.srl"
# La clave debe poder leerla el usuario mssql (uid 10001) dentro del contenedor; el directorio queda sin acceso a terceros.
chmod 644 "$D/mssql.key" "$D/mssql.pem" "$D/ca.crt"; chmod 600 "$D/ca.key"; chmod 755 "$D"
echo "Certificados creados en deploy/staging/$D (ca.key permanece solo en este equipo)."
