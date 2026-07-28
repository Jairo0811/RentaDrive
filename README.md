# 🚘 RentaDrive

> **Gestiona tu flota. Impulsa tu negocio.**

RentaDrive es un sistema web de gestión de alquiler de vehículos construido como evolución profesional de un proyecto académico del ITLA. Centralizará clientes, flota, reservas, alquileres, contratos, inspecciones, devoluciones, facturación, pagos y reportes.

El proyecto se desarrolla como un monolito modular: conserva la sencillez operativa de Laravel y separa la lógica por dominios para facilitar su mantenimiento y una futura API REST.

![Estado](https://img.shields.io/badge/estado-fase%201-blue)
![Laravel](https://img.shields.io/badge/Laravel-13.8-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![SQL Server](https://img.shields.io/badge/SQL%20Server-2017+-CC2927?logo=microsoftsqlserver&logoColor=white)
![Licencia](https://img.shields.io/badge/licencia-MIT-green)

## Estado actual

**Fase 1 — Fundación técnica**

- Autenticación con Laravel Breeze y registro público deshabilitado.
- Protección CSRF, regeneración de sesión y limitación de intentos de acceso.
- Usuarios activos/inactivos.
- Roles y permisos con Spatie Laravel Permission.
- Roles iniciales: Administrador, Gerente, Agente de alquiler e Inspector.
- Policies y middleware de autorización.
- Seeder local idempotente para el administrador.
- Layout responsive con sidebar, navbar y navegación móvil.
- Modo oscuro persistente.
- Dashboard inicial con datos reales de la fundación.
- Pruebas de autenticación y autorización.
- Integración continua para backend y frontend.

Los módulos operativos todavía no están implementados. El dashboard los muestra como próximos para no presentar funciones simuladas como terminadas.

## Stack tecnológico

### Backend

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/php/php-original.svg" alt="PHP" title="PHP 8.3+" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg" alt="Laravel" title="Laravel 13" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/composer/composer-original.svg" alt="Composer" title="Composer" width="42" height="42">
</p>

- **PHP 8.3+** como lenguaje del servidor.
- **Laravel 13** como framework principal y base del monolito modular.
- **Composer** para la administración de dependencias del backend.

### Frontend

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/html5/html5-original.svg" alt="HTML5" title="Blade y HTML5" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/tailwindcss/tailwindcss-original.svg" alt="Tailwind CSS" title="Tailwind CSS 3" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/javascript/javascript-original.svg" alt="JavaScript" title="JavaScript" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/alpinejs/alpinejs-original.svg" alt="Alpine.js" title="Alpine.js 3" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/chartjs/chartjs-original.svg" alt="Chart.js" title="Chart.js 4" width="42" height="42">
</p>

- **Blade** para las vistas renderizadas por Laravel.
- **Tailwind CSS 3** para estilos, diseño responsive y modo oscuro.
- **Alpine.js 3** para la interactividad ligera de la interfaz.
- **Chart.js 4** para gráficos y visualizaciones del dashboard.

### Base de datos y persistencia

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/microsoftsqlserver/microsoftsqlserver-original.svg" alt="Microsoft SQL Server" title="Microsoft SQL Server" width="42" height="42">
</p>

- **Microsoft SQL Server 2017+** como motor de base de datos; se recomienda SQL Server 2022.
- **Eloquent ORM**, migraciones y seeders para el acceso y administración de los datos.
- **SQLite en memoria** exclusivamente para pruebas locales rápidas.

### Seguridad y control de acceso

- **Laravel Breeze** para la autenticación.
- **Spatie Laravel Permission** para roles y permisos.
- **Policies, Gates y middleware** para la autorización del lado del servidor.
- Protección **CSRF**, regeneración de sesiones y limitación de intentos de inicio de sesión.

### Reportes y documentos

- **DomPDF** para generar documentos PDF.
- **Laravel Excel** para importar y exportar archivos de Excel.
- **Chart.js** para presentar indicadores y reportes visuales.

### Herramientas, pruebas e integración continua

<p>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg" alt="Node.js" title="Node.js 20+" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/npm/npm-original-wordmark.svg" alt="npm" title="npm 10+" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vitejs/vitejs-original.svg" alt="Vite" title="Vite 8" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/git/git-original.svg" alt="Git" title="Git" width="42" height="42">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/githubactions/githubactions-original.svg" alt="GitHub Actions" title="GitHub Actions" width="42" height="42">
</p>

- **Vite 8**, **Node.js 20+** y **npm 10+** para compilar los recursos del frontend.
- **PHPUnit 12** para pruebas automatizadas.
- **Laravel Pint** para verificar el estilo del código PHP.
- **Git y GitHub** para control de versiones.
- **GitHub Actions** para validar el build, el estilo y las pruebas sobre SQL Server 2022.

## Requisitos

- PHP 8.3 o superior.
- Composer 2.7 o superior.
- Microsoft SQL Server 2017 o superior; se recomienda SQL Server 2022.
- Microsoft ODBC Driver 18 for SQL Server.
- Node.js 20 o superior.
- npm 10 o superior.

Extensiones PHP principales: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_sqlsrv`, `session`, `sqlsrv`, `tokenizer` y `xml`.

Laravel requiere las extensiones `sqlsrv` y `pdo_sqlsrv` junto con el controlador ODBC de Microsoft. Consulta la [configuración oficial de SQL Server en Laravel](https://laravel.com/docs/13.x/database#microsoft-sql-server-configuration) y la [instalación oficial de los controladores PHP](https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server).

## Instalación

```bash
git clone https://github.com/Jairo0811/RentaDrive.git
cd RentaDrive
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Crea una base de datos SQL Server vacía desde SQL Server Management Studio o Azure Data Studio:

```sql
IF DB_ID(N'RentaDriveDb') IS NULL
BEGIN
    CREATE DATABASE [RentaDriveDb];
END;
GO
```

Usa un login dedicado con permisos sobre `RentaDriveDb`; evita utilizar `sa` en producción.

Ajusta estas variables en `.env`:

```dotenv
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=RentaDriveDb
DB_USERNAME=rentadrive_app
DB_PASSWORD=
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true
```

La configuración anterior facilita el desarrollo local con un certificado autofirmado. En producción utiliza `DB_ENCRYPT=yes`, `DB_TRUST_SERVER_CERTIFICATE=false` y un certificado válido.

Finaliza la preparación:

```bash
php artisan storage:link
php artisan migrate --seed
npm run build
php artisan serve
```

Para ejecutar servidor, cola, logs y Vite en desarrollo:

```bash
composer run dev
```

## Credenciales locales

El seeder crea este usuario **únicamente en los entornos `local` y `testing`**:

```text
Correo: admin@rentadrive.test
Contraseña: password
```

Puedes reemplazarlo antes de ejecutar los seeders:

```dotenv
RENTADRIVE_ADMIN_EMAIL=admin@rentadrive.test
RENTADRIVE_ADMIN_PASSWORD=una-clave-local-segura
```

El usuario de demostración no se crea en producción.

## Roles iniciales

| Rol | Alcance preparado |
|---|---|
| Administrador | Control completo y administración |
| Gerente | Consultas operativas, financieras y reportes |
| Agente de alquiler | Clientes, reservas, alquileres, contratos, entregas y devoluciones |
| Inspector | Vehículos, alquileres e inspecciones |

Los permisos están definidos como enums y asignados de forma idempotente por `RolePermissionSeeder`.

## Estructura inicial

```text
RentaDrive/
├── app/
│   ├── Domain/
│   │   └── Security/
│   │       ├── Enums/
│   │       └── Services/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Models/
│   ├── Policies/
│   └── View/Components/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/analysis/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
└── tests/
    ├── Feature/
    └── Unit/
```

Cada dominio nuevo se incorporará bajo `app/Domain` sin duplicar la estructura base de Laravel.

## Pruebas

Las pruebas locales usan SQLite en memoria y no modifican `RentaDriveDb`:

```bash
composer test
```

La integración continua ejecuta la misma suite contra una instancia efímera de SQL Server 2022 para validar las migraciones y el comportamiento real del motor elegido.

Verificación de estilo:

```bash
./vendor/bin/pint --test
```

Compilación del frontend:

```bash
npm run build
```

## Hoja de ruta

1. ✅ Fundación técnica.
2. ⏳ Catálogos y flota.
3. ⏳ Clientes.
4. ⏳ Reservas y disponibilidad.
5. ⏳ Alquileres y contratos.
6. ⏳ Inspecciones y devoluciones.
7. ⏳ Facturación y pagos.
8. ⏳ Dashboard y reportes.
9. ⏳ Auditoría y hardening.
10. ⏳ Pruebas integrales y documentación final.

Consulta [el alcance técnico de la Fase 1](docs/analysis/phase-1.md) para ver decisiones y criterios de aceptación.

## Seguridad

- No se versiona `.env` ni ningún secreto.
- El registro público está deshabilitado.
- Las contraseñas se almacenan con el hasher configurado por Laravel.
- Los usuarios inactivos no pueden autenticarse.
- Los permisos se verifican en backend; ocultar opciones de la interfaz no sustituye la autorización.
- `APP_DEBUG` debe permanecer en `false` en producción.
- El usuario de demostración está limitado a entornos locales y de prueba.

## 📌 Información académica

| Información | Detalle |
|-------------|---------|
| 👨‍🎓 Estudiante | Francis Jairo Matías Rosario |
| 🆔 Matrícula | 2015-2984 |
| 📖 Asignatura | Análisis y Diseño de Sistemas (SOF-007) |
| 👨‍🏫 Profesor | Huáscar Frías Vilorio |
| 🏫 Institución | Instituto Tecnológico de Las Américas (ITLA) |
| 📅 Período académico | 2017-C1 |
| 🎯 Tipo de proyecto | Proyecto final |

## Capturas

Las capturas definitivas se agregarán cuando los primeros recorridos operativos estén disponibles.

## Licencia

Distribuido bajo la licencia [MIT](LICENSE).
