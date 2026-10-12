<p align="center">
  <img src="public/images/rentadrive-racing.png" alt="RentaDrive" width="720">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/ITLA-SOF--007-0057B8?style=for-the-badge" alt="ITLA SOF-007">
  <img src="https://img.shields.io/badge/Estado-Evolución%20Comercial-0f766e?style=for-the-badge" alt="Estado: evolución comercial">
  <img src="https://img.shields.io/badge/Fase-2.1%20Hardening-7c3aed?style=for-the-badge" alt="Fase 2.1 Hardening">
  <img src="https://img.shields.io/badge/Licencia-MIT-16a34a?style=for-the-badge" alt="Licencia MIT">
</p>

<p align="center">
  <a href="https://github.com/Jairo0811/RentaDrive/actions/workflows/ci.yml"><img src="https://github.com/Jairo0811/RentaDrive/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4.1+-777BB4?logo=php&logoColor=white" alt="PHP 8.4.1+">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/SQL%20Server-2022%20CI-CC2927?logo=microsoftsqlserver&logoColor=white" alt="SQL Server 2022 CI">
  <img src="https://img.shields.io/badge/Tailwind-4.x-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
</p>

# RentaDrive

**RentaDrive** es una plataforma web multiempresa para la gestión integral de rent-a-car. Centraliza clientes, sucursales, flota, reservas, alquileres, contratos, inspecciones, mantenimiento, facturación, pagos, reportes, usuarios, configuración y auditoría, e incorpora un **Booking Engine público por empresa**.

El proyecto nació como trabajo final de **Análisis y Diseño de Sistemas (SOF-007)** del Instituto Tecnológico de Las Américas (ITLA) y fue modernizado en 2026. Actualmente evoluciona de proyecto académico/profesional a producto SaaS comercial orientado inicialmente al mercado dominicano.

> RentaDrive no es una aplicación oficial del ITLA. Su origen es académico y su evolución comercial es un proyecto independiente.

## Estado actual

Las siguientes etapas están cerradas:

- **Fase 1 — Commercial Foundation**: multi-tenancy, empresas, sucursales, aislamiento de datos, SuperAdmin y planes.
- **Fase 2 — Booking Engine**: disponibilidad, cotización, extras/seguros, reserva pública y autoservicio.
- **Fase 2.1 — Hardening**: seguridad PWA, continuidad administrativa, validación documental, seeders seguros y CI reforzada.

La próxima etapa del roadmap es **Fase 3 — Payments**.

Consulta el detalle completo en [`docs/commercial-roadmap.md`](docs/commercial-roadmap.md).

## Funcionalidades

### Multiempresa y administración SaaS

- Aislamiento lógico por `company_id` mediante contexto y scopes automáticos.
- Empresas con estados `trial`, `active`, `suspended` y `cancelled`.
- Sucursales por empresa.
- Asignación de sucursal por usuario.
- SuperAdmin de plataforma separado de los administradores de tenant.
- Planes Starter, Professional y Business con límites de usuarios, sucursales y vehículos.
- Protección del último Administrador activo de cada empresa.
- Protección del último SuperAdmin activo de plataforma.
- Auditoría de cambios operativos.

### Booking Engine público

Cada empresa activa dispone de un portal público mediante su slug:

```text
/book/{empresa}
```

Incluye:

- selección de sucursal;
- fechas de retiro y devolución;
- disponibilidad por categoría;
- cálculo server-side de días y tarifa;
- depósito informativo;
- extras y seguros `flat` o `per_day`;
- reserva sin necesidad de crear una cuenta;
- token privado de seguimiento;
- confirmación y cancelación autoservicio antes del retiro;
- throttling de endpoints públicos;
- bloqueo transaccional para evitar sobreventa del último cupo;
- capacidad compartida con las reservas internas.

La reserva pública se realiza inicialmente por **categoría**. El vehículo concreto puede asignarse posteriormente por el equipo operativo.

### Clientes

- Cédula, pasaporte, RNC y otros documentos en backoffice.
- Validación local de cédulas dominicanas mediante dígito verificador.
- Normalización de cédula y pasaporte.
- Validación de cédula también en el Booking Engine público.
- Contacto, licencia, vencimiento, dirección y estado del cliente.

### Flota

- Marcas, modelos y categorías por tenant.
- Tarifas y depósitos por categoría.
- Vehículos por sucursal.
- Estados de flota: disponible, reservado, alquilado, mantenimiento e inactivo.
- Kilometraje, combustible y mantenimiento.
- Límites comerciales de vehículos por plan.

### Reservas y alquileres

- Reservas internas y online.
- Validación de solapamientos.
- Disponibilidad física y por categoría.
- Asignación opcional de vehículo al reservar.
- Conversión de reserva a alquiler.
- Apertura y cierre de alquileres.
- Actualización de kilometraje y estado del vehículo.
- Inspecciones de entrega y devolución.

### Facturación y pagos internos

- Facturación automática al abrir un alquiler.
- ITBIS configurable desde backend.
- Pagos parciales y totales.
- Anulación de pagos con recálculo de balance y auditoría.
- Facturas y contratos en PDF.
- Reportes operativos en PDF y CSV.

> Los pagos online, depósitos mediante pasarela, reembolsos y webhooks pertenecen a la **Fase 3** y todavía no forman parte del producto actual.

### Dashboard y experiencia

- Indicadores operativos.
- Gráficos con Chart.js.
- Calendario de reservas.
- Diseño responsive.
- Interfaz **dark-only**.
- Notificaciones y estados visuales.
- PWA instalable.

## Seguridad y hardening

RentaDrive aplica controles de seguridad en varias capas:

- autenticación y verificación de correo;
- roles y permisos con Spatie Permission;
- policies y middleware de autorización;
- CSRF;
- Form Requests;
- aislamiento multi-tenant automático;
- validaciones tenant-aware para claves foráneas;
- transacciones y `lockForUpdate()` en operaciones sensibles;
- tokens públicos aleatorios para reservas;
- rate limiting en operaciones públicas;
- protección de continuidad administrativa;
- auditoría automática;
- `npm audit --audit-level=high` en CI;
- `composer validate --strict` y `composer audit --locked` en CI.

### PWA y privacidad

El Service Worker **no almacena páginas HTML, dashboard, datos operativos ni URLs privadas con token**. Su caché se limita a recursos públicos estáticos como assets compilados, imágenes y manifest.

## Accesibilidad

RentaDrive mantiene una línea transversal de accesibilidad inspirada en **NORTIC B2:2017** y **WCAG 2.0**, con objetivo técnico de aproximación a AA.

Entre las medidas implementadas se encuentran:

- navegación mediante teclado;
- skip links;
- foco visible;
- landmarks semánticos;
- nombres accesibles y ARIA;
- mensajes `role="alert"` y `aria-live`;
- escalado de texto;
- alto contraste;
- reducción de movimiento;
- soporte de `prefers-reduced-motion`;
- cierre de menús mediante `Escape`;
- persistencia local de preferencias de accesibilidad.

Esto **no constituye una certificación formal NORTIC B2**. La conformidad completa requiere auditoría, pruebas manuales y evaluación con tecnologías asistivas.

Consulta [`docs/accessibility.md`](docs/accessibility.md).

## Stack tecnológico

### Backend

- PHP **8.4.1+**
- Laravel **13.x**
- Eloquent ORM
- Spatie Laravel Permission
- DomPDF
- Maatwebsite Excel
- arquitectura monolítica modular con servicios de dominio

### Frontend

- Blade
- Tailwind CSS **4.x**
- Alpine.js **3.x**
- Chart.js **4.x**
- Vite **8.x**
- JavaScript ES Modules

### Base de datos

- Microsoft SQL Server como motor oficial.
- SQL Server 2022 en GitHub Actions.
- ODBC Driver 18 en CI.
- `sqlsrv` y `pdo_sqlsrv`.

### Calidad

- PHPUnit 12.x
- Laravel Pint
- GitHub Actions
- Composer audit
- npm audit
- build de producción con Vite

## Arquitectura multi-tenant

Los modelos operativos registrados reciben un scope global de tenant. Durante una petición de tenant, las consultas se restringen automáticamente por empresa y los nuevos registros heredan el `company_id` del contexto.

Los recursos dependientes de sucursal, como vehículos, reservas y alquileres, también heredan `branch_id` cuando corresponde.

La administración de plataforma funciona fuera del contexto operacional del tenant y un SuperAdmin no puede entrar accidentalmente al workspace de una empresa.

## Planes comerciales base

| Plan | Usuarios | Sucursales | Vehículos |
| --- | ---: | ---: | ---: |
| Starter | 5 | 2 | 25 |
| Professional | 15 | 5 | 100 |
| Business | 50 | 20 | 500 |

Estos límites son actualmente reglas de producto. La facturación recurrente de suscripciones pertenece a la Fase 7.

## Instalación local

### Requisitos

- PHP 8.4.1+
- Composer 2
- Node.js 24 recomendado
- npm
- SQL Server 2017+; CI valida SQL Server 2022
- ODBC Driver 18 recomendado
- extensiones PHP `sqlsrv` y `pdo_sqlsrv`

### 1. Clonar

```bash
git clone https://github.com/Jairo0811/RentaDrive.git
cd RentaDrive
```

### 2. Dependencias

```bash
composer install
npm ci
```

### 3. Configuración

```bash
cp .env.example .env
php artisan key:generate
```

Configura la conexión SQL Server en `.env`.

### 4. Base de datos

```bash
php artisan migrate
php artisan db:seed
```

Los datos operativos de demostración solamente se insertan en entornos `local` y `testing`.

### 5. Frontend

```bash
npm run build
```

Para desarrollo:

```bash
composer dev
```

## SuperAdmin de plataforma

En producción el SuperAdmin no se crea mediante credenciales demo. Utiliza el comando interactivo:

```bash
php artisan rentadrive:platform-admin correo@dominio.com
```

La contraseña se solicita de forma interactiva y no se pasa como argumento de consola.

## Validación local

Antes de enviar cambios:

```bash
composer validate --strict
composer audit --locked
npm audit --audit-level=high
npm run build
./vendor/bin/pint --test
php artisan test
```

La CI ejecuta la suite contra **SQL Server 2022**, no contra SQLite.

## Producción

El `.env.example` está preparado para desarrollo. En producción HTTPS se deben aplicar como mínimo:

```env
APP_ENV=production
APP_DEBUG=false
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

Además deben usarse credenciales propias, HTTPS, backups, observabilidad y una estrategia de despliegue reproducible. Esos controles se completarán durante la Fase 7 — SaaS Production.

## Roadmap resumido

| Fase | Estado |
| --- | --- |
| 1 — Commercial Foundation | ✅ Completa |
| 2 — Booking Engine | ✅ Completa |
| 2.1 — Hardening | ✅ Completa |
| 3 — Payments | ⏭️ Próxima |
| 4 — Dominican Edition | Pendiente |
| 5 — Digital Rental | Pendiente |
| 6 — Automation | Pendiente |
| 7 — SaaS Production | Pendiente |

Consulta [`docs/commercial-roadmap.md`](docs/commercial-roadmap.md) para el detalle técnico.

## Origen académico

RentaDrive conserva su origen como proyecto final de **SOF-007 — Análisis y Diseño de Sistemas** del ITLA. La versión actual es una modernización y evolución comercial posterior desarrollada de manera independiente.

## Licencia

MIT.
