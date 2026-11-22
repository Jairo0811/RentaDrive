# Matriz de permisos

Generada a partir de `PermissionName` y `database/seeders/RolePermissionSeeder.php` (fuente de verdad). Los permisos se aplican por **política + middleware** y siempre dentro de la empresa activa (`TenantScope`); ningún rol cruza empresas. El administrador de plataforma (SaaS) opera solo en `/platform`, no dentro de empresas (cubierto por `CrossTenantAuthorizationMatrixTest`).

| Permiso | Administrador | Gerente | Agente de alquiler | Inspector |
|---|:-:|:-:|:-:|:-:|
| `view dashboard` | ✔ | ✔ | ✔ | ✔ |
| `manage users` | ✔ | — | — | — |
| `view reports` | ✔ | ✔ | — | — |
| `manage configuration` | ✔ | — | — | — |
| `view audit log` | ✔ | — | — | — |
| `view vehicles` | ✔ | ✔ | ✔ | ✔ |
| `manage vehicles` | ✔ | — | — | — |
| `view customers` | ✔ | ✔ | ✔ | — |
| `manage customers` | ✔ | — | ✔ | — |
| `view reservations` | ✔ | ✔ | ✔ | — |
| `manage reservations` | ✔ | — | ✔ | — |
| `view rentals` | ✔ | ✔ | ✔ | ✔ |
| `manage rentals` | ✔ | — | ✔ | — |
| `manage contracts` | ✔ | — | ✔ | — |
| `manage deliveries` | ✔ | — | ✔ | — |
| `manage inspections` | ✔ | — | — | ✔ |
| `manage returns` | ✔ | — | ✔ | — |
| `view invoices` | ✔ | ✔ | ✔ | — |
| `manage invoices` | ✔ | — | — | — |
| `view payments` | ✔ | ✔ | ✔ | — |
| `manage payments` | ✔ | — | — | — |

Notas:
- «Administrador» recibe todos los permisos de la empresa.
- Con `MFA_ENFORCE=true`, administradores de empresa y de plataforma deben activar MFA TOTP (`docs/mfa.md`).
- Para cambiar la matriz, edita el seeder y ejecuta `php artisan db:seed --class=RolePermissionSeeder` (idempotente, no toca usuarios).
