# RentaDrive — criterios de cierre del desarrollo

El proyecto no se considera listo para producción únicamente porque CI esté verde. Las fases 1, 2 y 2.1 están implementadas; pagos reales, e-CF certificado, proveedores e infraestructura comercial tienen dependencias externas.

## Gates automatizados (existentes)

El workflow `.github/workflows/ci.yml` ya ejecuta:

- `composer validate --strict`, `composer audit --locked --no-interaction`;
- `npm audit --audit-level=high`, `npm run build`;
- `./vendor/bin/pint --test` y `php artisan test` sobre SQL Server 2022;
- Playwright (incluido QA móvil Chromium) con datos ficticios en base descartable.

El workflow `staging-verify.yml` comprueba contenedores, TLS, migraciones no destructivas, smoke tests, cola, backup y restauración en runner descartable. No equivale a un despliegue real.

## Nuevas protecciones

- `.github/dependabot.yml`: PR semanales para Composer, npm, GitHub Actions y el Dockerfile de `deploy/`. Se revisan y prueban; no se fusionan automáticamente.
- `.github/workflows/secrets-scan.yml`: Gitleaks sobre el rango de commits nuevos de cada PR o push a `main`, con valores redactados. No analiza toda la historia anterior; no elimina credenciales históricas.

## Bloqueos y comprobaciones del operador

1. **Urgente:** rotar la contraseña antigua del login SQL `rentadrive_app` que estuvo en Git. Actualizar primero los consumidores de esa credencial, validar conexión, revocar la anterior. Nunca pegar contraseñas en Issues, commits o logs. El escáner nuevo no sustituye la rotación.
2. Revisar protección de ramas y reglas obligatorias (CI, E2E y secrets scan) en GitHub.
3. Probar zoom real, lector de pantalla, Safari/iOS y Chrome/Android físicos; documentar el resultado.
4. Antes del staging real: DNS, certificados TLS, Cloudflare Access/túnel autorizado, secretos, retención de backups y restauración probada en infraestructura real.
5. Antes de operaciones de pago o fiscales: contratos y credenciales autorizadas, conciliación, auditoría y validación con el proveedor e-CF.
6. Revisión de riesgos de MFA, CSP, colas, monitoreo, pruebas de carga y seguridad independiente.

## Política de cambios

Trabajar en ramas aisladas y PR pequeñas. Conservar el diseño dark-only, Laravel 13, PHP 8.4.27+ y SQL Server 2022. No ejecutar migraciones destructivas ni seeders demo contra `RentaDriveDb`, ni activar pasarelas o certificados fiscales sin aprobación.
