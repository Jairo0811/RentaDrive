<p align="center">
  <img src="public/images/rentadrive-racing.png" alt="RentaDrive" width="460">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/ITLA-2017--C1-0057B8?style=for-the-badge" alt="ITLA 2017-C1">
  <img src="https://img.shields.io/badge/Estado-Finalizado-16a34a?style=for-the-badge" alt="Estado finalizado">
  <img src="https://img.shields.io/badge/Versión-1.0.0-2563eb?style=for-the-badge" alt="Versión 1.0.0">
  <img src="https://img.shields.io/badge/Licencia-MIT-16a34a?style=for-the-badge" alt="Licencia MIT">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php&logoColor=white" alt="PHP 8.4+">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/SQL%20Server-2017+-CC2927?logo=microsoftsqlserver&logoColor=white" alt="SQL Server">
  <img src="https://img.shields.io/badge/Tests-35%20passing-success" alt="35 tests passing">
</p>

# 🚘 RentaDrive

**RentaDrive** es una plataforma web profesional para la gestión integral de empresas de alquiler de vehículos. Centraliza clientes, flota, reservas, alquileres, contratos, inspecciones, mantenimiento, facturación, pagos, reportes, usuarios, configuración y auditoría dentro de un flujo operativo completo.

El proyecto nació como trabajo final de la asignatura **Análisis y Diseño de Sistemas (SOF-007)** del Instituto Tecnológico de Las Américas (ITLA) y fue modernizado en 2026 como una aplicación funcional con Laravel, Microsoft SQL Server y una arquitectura monolítica modular.

## ✅ Estado actual

La versión **1.0.0** está finalizada y lista para uso académico, portafolio profesional y evolución comercial.

- 🧪 35 pruebas automatizadas aprobadas.
- 🎯 103 assertions.
- 🎨 Laravel Pint validado.
- ⚡ Build de Vite aprobado.
- 🗄️ SQL Server como motor principal y SQLite en memoria para testing.
- 📊 Dashboard con métricas, gráficos y calendario.
- 📱 Aplicación instalable como PWA.
- 📄 Facturas, contratos y reportes operativos en PDF.
- 📤 Reportes exportables en CSV.

# 🧩 Funcionalidades

## 🚗 Operaciones

- 👤 Gestión de clientes, documentos, contacto y licencias.
- 🪪 Validación local de cédulas dominicanas por dígito verificador.
- 🔢 Validación de longitud para cédula, RNC y pasaporte.
- 🚘 Gestión de marcas, modelos, categorías, tarifas, depósitos y vehículos.
- 🚦 Estados de flota: disponible, reservado, alquilado, mantenimiento e inactivo.
- 🛠️ Historial y programación de mantenimientos.
- 📅 Reservas con validación de disponibilidad y solapamiento.
- 🔄 Conversión de reservas en alquileres.
- 🔑 Apertura y cierre de alquileres.
- ⛽ Gestión de kilometraje, combustible, depósitos, cargos y fechas.
- 📸 Inspecciones de entrega y devolución.

## 💰 Finanzas

- 🧾 Facturación automática.
- 🇩🇴 ITBIS fijo del 18 % protegido desde backend.
- 💳 Pagos parciales y totales.
- ↩️ Anulación de pagos y recálculo del balance.
- 📄 Facturas, contratos y reportes en PDF.
- 📊 Reportes operativos en PDF y CSV.

## 📊 Experiencia de usuario

- 📈 Indicadores operativos en tiempo real.
- 💹 Gráfico de ingresos.
- 🚙 Distribución del estado de la flota.
- 🗓️ Calendario de reservas.
- 🔔 Notificaciones tipo toast.
- 🌗 Modo claro y oscuro.
- 📱 Diseño responsive.
- 🎯 Navegación con Font Awesome.
- 🚫 Páginas personalizadas 404 y 500.

## 🔐 Administración y seguridad

- 👥 Gestión de usuarios activos e inactivos.
- 🛡️ Roles y permisos por módulo.
- 🚧 Policies y middleware de autorización.
- 🧿 Protección CSRF.
- ✅ Validaciones mediante Form Requests.
- 🧾 Auditoría automática.
- 🗑️ Eliminación segura de cuentas.

# 🧰 Stack tecnológico

## ⚙️ Backend

- PHP 8.4.1+
- Laravel 13.8
- Composer
- Eloquent ORM
- Migraciones, seeders, factories y servicios de dominio

## 🎨 Frontend

- Blade
- HTML5
- Tailwind CSS 3.4
- Alpine.js 3.15
- JavaScript ES Modules
- Chart.js 4.5
- Font Awesome 6.7
- Vite 8

## 🗄️ Base de datos

- Microsoft SQL Server 2017+
- ODBC Driver 17 o 18
- Extensiones `sqlsrv` y `pdo_sqlsrv`
- SQLite en memoria para testing

## 🧪 Calidad y CI

- PHPUnit 12.5
- Laravel Pint 1.27
- Mockery
- Faker
- GitHub Actions

# 📥 Instalación

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

```powershell
Copy-Item .env.example .env
```

## 4. Generar la clave

```bash
php artisan key:generate
```

## 5. Configurar SQL Server

```dotenv
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=RentaDriveDb
DB_USERNAME=rentadrive_app
DB_PASSWORD=RentaDrive_Local_2026!
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true
```

## 6. Configurar credenciales iniciales

```dotenv
RENTADRIVE_ADMIN_EMAIL=admin@rentadrive.com.do
RENTADRIVE_ADMIN_PASSWORD=RentaDrive123..
```

> Cambia estas credenciales antes de publicar la aplicación.

## 7. Crear tablas y datos iniciales

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan storage:link
```

## 8. Compilar y validar

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

## 9. Iniciar la aplicación

```bash
php artisan serve
```

Abrir:

```text
http://127.0.0.1:8000
```

# 🔑 Credenciales locales

```text
Correo: admin@rentadrive.com.do
Contraseña: RentaDrive123..
```

# 🎓 Ficha académica

| Campo | Información |
|---|---|
| 🏫 Institución | Instituto Tecnológico de Las Américas (ITLA) |
| 🎓 Carrera | Tecnólogo en Desarrollo de Software |
| 📘 Asignatura | Análisis y Diseño de Sistemas (SOF-007) |
| 👨‍🏫 Profesor | Huascar Frias Vilorio |
| 📅 Período académico | 2017-C1 |
| 👨‍💻 Estudiante | Francis Jairo Matías Rosario |
| 🪪 Matrícula | 2015-2984 |
| 🚘 Proyecto | RentaDrive |
| 🛠️ Modernización | 2026 |

# 👨‍💻 Autor

**Francis Jairo Matías Rosario**  
Matrícula: **2015-2984**  
GitHub: [@Jairo0811](https://github.com/Jairo0811)

# 🗺️ Evolución futura

- Multiempresa y multisucursal.
- Firma digital de contratos.
- Integración con correo y WhatsApp.
- API REST con OpenAPI/Swagger.
- Integración oficial con servicios de identidad, cuando exista acceso autorizado.

# 📄 Licencia

Este proyecto se distribuye bajo la licencia **MIT**.