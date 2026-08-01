<p align="center">
  <img src="public/images/rentadrive-racing.jpeg" alt="RentaDrive" width="460">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/ITLA-2017--C1-0057B8?style=for-the-badge" alt="ITLA 2017-C1">
  <img src="https://img.shields.io/badge/Estado-Finalizado-16a34a?style=for-the-badge" alt="Estado finalizado">
  <img src="https://img.shields.io/badge/Versión-1.0-2563eb?style=for-the-badge" alt="Versión 1.0">
  <img src="https://img.shields.io/badge/Licencia-MIT-16a34a?style=for-the-badge" alt="Licencia MIT">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php&logoColor=white" alt="PHP 8.4+">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/SQL%20Server-2017+-CC2927?logo=microsoftsqlserver&logoColor=white" alt="SQL Server">
  <img src="https://img.shields.io/badge/Tests-35%20passing-success" alt="35 tests passing">
</p>

# RentaDrive

**RentaDrive** es un sistema web profesional para la gestión integral de empresas de alquiler de vehículos. Centraliza clientes, flota, reservas, alquileres, contratos, inspecciones, mantenimiento, facturación, pagos, reportes, usuarios, configuración y auditoría dentro de un flujo operativo completo.

El proyecto nació como trabajo final de la asignatura **Análisis y Diseño de Sistemas** del Instituto Tecnológico de Las Américas (ITLA) y fue reconstruido como una aplicación funcional con Laravel, Microsoft SQL Server y una arquitectura monolítica modular orientada a mantenibilidad, seguridad y escalabilidad.

## Estado actual

La versión 1.0 se encuentra finalizada y validada para uso académico, portafolio profesional y evolución comercial.

- 35 pruebas automatizadas aprobadas.
- 103 assertions.
- Laravel Pint en verde.
- Build de Vite aprobado.
- Compatibilidad validada con SQL Server y SQLite en memoria para testing.
- Dashboard operativo con métricas, gráficos y calendario.
- Aplicación instalable como PWA.

# Funcionalidades

## Operaciones

- Gestión completa de clientes, contacto, documentos y licencias.
- Validación local de cédulas dominicanas por dígito verificador.
- Normalización automática de cédulas antes de guardar.
- Administración de marcas, modelos, categorías, tarifas, depósitos y vehículos.
- Estados de flota: disponible, reservado, alquilado, mantenimiento e inactivo.
- Historial y programación de mantenimientos.
- Reservas con validación de disponibilidad y solapamiento.
- Conversión de reservas en alquileres.
- Apertura y cierre de alquileres.
- Gestión de kilometraje, combustible, depósito, cargos y fechas.
- Contrato imprimible por alquiler.
- Inspecciones de entrega y devolución.
- Actualización automática del estado del vehículo.

## Finanzas

- Facturación automática al abrir un alquiler.
- Recálculo de factura al devolver el vehículo.
- ITBIS fijo del 18 % protegido desde backend.
- Gestión de descuentos, vencimientos y notas.
- Pagos parciales y totales.
- Recibos con método de pago y referencia.
- Anulación de pagos y recálculo del balance.
- Facturas y contratos en PDF.
- Exportaciones CSV y soporte para Laravel Excel.

## Dashboard y experiencia de usuario

- Indicadores operativos en tiempo real.
- Gráfico de ingresos de los últimos seis meses.
- Gráfico de distribución del estado de la flota.
- Calendario mensual de reservas.
- Accesos rápidos para nuevas reservas y alquileres.
- Notificaciones tipo toast.
- Modo claro y oscuro.
- Diseño responsive.
- Páginas personalizadas para errores 404 y 500.
- Manifest y service worker para instalación como PWA.

## Administración y seguridad

- Gestión de usuarios activos e inactivos.
- Roles: Administrador, Gerente, Agente de alquiler e Inspector.
- Permisos por módulo y operación.
- Policies y middleware de autorización.
- Registro público deshabilitado.
- Protección CSRF.
- Validaciones mediante Form Requests.
- Auditoría automática de creaciones, modificaciones y eliminaciones.
- Eliminación segura de cuentas con limpieza de referencias relacionadas.

# Flujo principal

```mermaid
flowchart TD
    A[Cliente] --> B[Reserva]
    B --> C[Alquiler]
    C --> D[Inspección de entrega]
    C --> E[Factura]
    E --> F[Pago]
    C --> G[Inspección de devolución]
    G --> H[Cierre del alquiler]
    H --> I[Vehículo disponible]
```

# Stack tecnológico

## Backend

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/php/php-original.svg" alt="PHP" title="PHP 8.4+" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg" alt="Laravel" title="Laravel 13" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/composer/composer-original.svg" alt="Composer" title="Composer" width="42" height="42">
</p>

- **PHP 8.4.1 o superior**.
- **Laravel 13.8**.
- **Composer** para dependencias y scripts.
- **Eloquent ORM** para persistencia y relaciones.
- Migraciones, seeders, factories y servicios de dominio.
- Arquitectura monolítica modular.

## Frontend

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/html5/html5-original.svg" alt="HTML5" title="HTML5" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/tailwindcss/tailwindcss-original.svg" alt="Tailwind CSS" title="Tailwind CSS" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/javascript/javascript-original.svg" alt="JavaScript" title="JavaScript" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/alpinejs/alpinejs-original.svg" alt="Alpine.js" title="Alpine.js" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/chartjs/chartjs-original.svg" alt="Chart.js" title="Chart.js" width="42" height="42">
</p>

- **Blade** y HTML5.
- **Tailwind CSS 3.4**.
- **Alpine.js 3.15**.
- **JavaScript ES Modules**.
- **Chart.js 4.5**.
- **Laravel Vite Plugin 3.1**.

## Base de datos

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/microsoftsqlserver/microsoftsqlserver-original.svg" alt="Microsoft SQL Server" title="Microsoft SQL Server" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/sqlite/sqlite-original.svg" alt="SQLite" title="SQLite para testing" width="42" height="42">
</p>

- **Microsoft SQL Server 2017 o superior** como motor principal.
- **ODBC Driver 17 o 18**.
- Extensiones PHP `sqlsrv` y `pdo_sqlsrv`.
- **SQLite en memoria** para pruebas automatizadas.

## Seguridad y autorización

- Laravel Breeze.
- Spatie Laravel Permission 8.3.
- Policies, middleware y permisos por módulo.
- CSRF, sesiones seguras y validación de entradas.
- Auditoría de operaciones sensibles.

## Documentos y exportaciones

- DomPDF 3.1 para contratos y facturas.
- Laravel Excel 3.1.
- Exportación CSV.

## Calidad, build y CI

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg" alt="Node.js" title="Node.js 22+" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/npm/npm-original-wordmark.svg" alt="npm" title="npm" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vitejs/vitejs-original.svg" alt="Vite" title="Vite 8" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/git/git-original.svg" alt="Git" title="Git" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/githubactions/githubactions-original.svg" alt="GitHub Actions" title="GitHub Actions" width="42" height="42">
</p>

- Node.js 22 o superior.
- npm 10 o superior.
- Vite 8.
- PHPUnit 12.5.
- Laravel Pint 1.27.
- Mockery y Faker.
- GitHub Actions para integración continua.

# Requisitos

- PHP 8.4.1 o superior.
- Composer 2.7 o superior.
- Microsoft SQL Server 2017 o superior.
- Microsoft ODBC Driver 17 o 18.
- Extensiones `sqlsrv` y `pdo_sqlsrv`.
- Node.js 22 o superior.
- npm 10 o superior.
- Git.

Extensiones PHP recomendadas:

```text
ctype
curl
dom
fileinfo
filter
hash
mbstring
openssl
pdo
pdo_sqlsrv
session
sqlsrv
tokenizer
xml
zip
```

# Instalación

## 1. Clonar el repositorio

```bash
git clone https://github.com/Jairo0811/RentaDrive.git
cd RentaDrive
```

## 2. Instalar dependencias

```bash
composer install
npm install
```

## 3. Crear el archivo de entorno

Linux o macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

## 4. Generar la clave de Laravel

```bash
php artisan key:generate
```

## 5. Crear la base de datos

Ejecuta en SQL Server Management Studio o Azure Data Studio:

```sql
IF DB_ID(N'RentaDriveDb') IS NULL
BEGIN
    CREATE DATABASE [RentaDriveDb];
END;
GO
```

## 6. Crear el login de aplicación

```sql
USE [master];
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.server_principals
    WHERE name = N'rentadrive_app'
)
BEGIN
    CREATE LOGIN [rentadrive_app]
    WITH PASSWORD = 'RentaDrive_Local_2026!',
         CHECK_POLICY = ON,
         CHECK_EXPIRATION = OFF;
END
ELSE
BEGIN
    ALTER LOGIN [rentadrive_app]
    WITH PASSWORD = 'RentaDrive_Local_2026!';

    ALTER LOGIN [rentadrive_app] ENABLE;
END;
GO

USE [RentaDriveDb];
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.database_principals
    WHERE name = N'rentadrive_app'
)
BEGIN
    CREATE USER [rentadrive_app]
    FOR LOGIN [rentadrive_app];
END;
GO

ALTER ROLE [db_datareader] ADD MEMBER [rentadrive_app];
ALTER ROLE [db_datawriter] ADD MEMBER [rentadrive_app];
ALTER ROLE [db_ddladmin] ADD MEMBER [rentadrive_app];
GO
```

> La contraseña anterior es únicamente un ejemplo para desarrollo local. Debe sustituirse en cualquier entorno compartido o productivo.

## 7. Configurar `.env`

```dotenv
APP_NAME=RentaDrive
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_TIMEZONE=America/Santo_Domingo
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_DO

DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=RentaDriveDb
DB_USERNAME=rentadrive_app
DB_PASSWORD=RentaDrive_Local_2026!
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

RENTADRIVE_ADMIN_EMAIL=admin@rentadrive.com.do
RENTADRIVE_ADMIN_PASSWORD=RentaDrive123..
```

Para producción:

```dotenv
APP_ENV=production
APP_DEBUG=false
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=false
```

## 8. Crear las tablas y datos iniciales

Instalación nueva:

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
```

Base existente:

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed
```

## 9. Crear el enlace de almacenamiento

```bash
php artisan storage:link
```

## 10. Compilar frontend

```bash
npm run build
```

## 11. Ejecutar validaciones

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

Resultado validado:

```text
35 pruebas aprobadas
103 assertions
```

## 12. Iniciar la aplicación

```bash
php artisan serve
```

Abre:

```text
http://127.0.0.1:8000
```

# Desarrollo local

Ejecutar todos los procesos:

```bash
composer run dev
```

O por separado:

```bash
php artisan serve
php artisan queue:listen
php artisan pail
npm run dev
```

# Credenciales locales

El seeder crea el usuario administrador únicamente en los entornos `local` y `testing`:

```text
Correo: admin@rentadrive.com.do
Contraseña: RentaDrive123..
```

> Estas credenciales son exclusivas para desarrollo. Deben cambiarse antes de publicar la aplicación.

# Roles

| Rol | Alcance |
|---|---|
| Administrador | Acceso completo, configuración, usuarios y auditoría |
| Gerente | Consultas operativas, financieras y reportes |
| Agente de alquiler | Clientes, reservas, alquileres, contratos y devoluciones |
| Inspector | Flota, alquileres e inspecciones |

# Estructura principal

```text
app/
├── Domain/
│   ├── Operations/Services/
│   └── Security/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Models/
│   └── Concerns/Auditable.php
├── Rules/
│   └── DominicanCedula.php
├── Policies/
└── Providers/

database/
├── migrations/
├── seeders/
└── factories/

resources/
├── css/
├── js/
└── views/
    ├── customers/
    ├── vehicles/
    ├── reservations/
    ├── rentals/
    ├── inspections/
    ├── invoices/
    ├── payments/
    ├── reports/
    ├── settings/
    ├── users/
    ├── errors/
    └── audit/

tests/
├── Feature/
└── Unit/
```

# Comandos útiles

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan test
vendor/bin/pint
npm run build
php artisan serve
```

# Solución de problemas

## `Login failed for user 'rentadrive_app'`

- Verifica que SQL Server use autenticación mixta.
- Confirma usuario y contraseña en `.env`.
- Reinicia el servicio SQL Server después de cambiar el modo de autenticación.
- Ejecuta `php artisan config:clear`.

## `Invalid object name 'vehicles'`

```bash
php artisan migrate:status
php artisan migrate
php artisan db:seed
```

## Error con claves foráneas en SQL Server

Las migraciones utilizan `NO ACTION` en las relaciones que podrían producir múltiples rutas en cascada.

## Vistas o configuración desactualizadas

```bash
php artisan optimize:clear
npm run build
```

# Información académica

| Campo | Información |
|---|---|
| Institución | Instituto Tecnológico de Las Américas (ITLA) |
| Asignatura | Análisis y Diseño de Sistemas |
| Código | SOF-007 |
| Período | 2017-C1 |
| Profesor | Huascar Frias Vilorio |
| Estudiante | Francis Jairo Matías Rosario |
| Matrícula | 2015-2984 |

# Licencia

Este proyecto se distribuye bajo la licencia MIT. Consulta el archivo `LICENSE` para más información.
