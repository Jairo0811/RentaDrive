# Arquitectura de RentaDrive

RentaDrive está implementado como un **monolito modular Laravel**. La aplicación mantiene un único despliegue y una única base transaccional, pero separa autenticación, operaciones de renta, flota, clientes, finanzas, reportes y administración mediante responsabilidades de framework y servicios de dominio.

## Vista general

```mermaid
flowchart LR
    User["Usuario"] --> Browser["Navegador · Blade / Tailwind / Alpine.js"]
    Browser --> Routes["Laravel Routes"]
    Routes --> Middleware["Middleware · Auth · CSRF"]
    Middleware --> Policies["Policies / Roles / Permisos"]
    Policies --> Controllers["Controllers"]
    Controllers --> Requests["Form Requests · Validación"]
    Requests --> Services["Servicios de dominio"]

    Services --> Models["Eloquent Models"]
    Models --> SQL[("SQL Server")]

    Services --> Audit["Auditoría"]
    Services --> PDF["Contratos / Facturas / PDF"]
    Services --> CSV["Reportes CSV"]
    Services --> Storage["Storage · Inspecciones / Archivos"]

    Browser --> Charts["Chart.js · Dashboard"]
    Browser --> PWA["PWA / Service Worker"]
```

El navegador nunca accede directamente a Eloquent ni a SQL Server. Autenticación, permisos, validaciones y reglas de negocio se resuelven en backend antes de persistir cualquier cambio.

## Módulos funcionales

```mermaid
flowchart TB
    Platform["RentaDrive"]
    Platform --> Customers["Clientes"]
    Platform --> Fleet["Flota / Vehículos"]
    Platform --> Reservations["Reservas"]
    Platform --> Rentals["Alquileres / Contratos"]
    Platform --> Inspections["Inspecciones"]
    Platform --> Maintenance["Mantenimiento"]
    Platform --> Billing["Facturación / Pagos"]
    Platform --> Reports["Dashboard / Reportes"]
    Platform --> Admin["Usuarios / Roles / Configuración"]
    Platform --> Audit["Auditoría"]

    Customers --> DB[("SQL Server")]
    Fleet --> DB
    Reservations --> DB
    Rentals --> DB
    Inspections --> DB
    Maintenance --> DB
    Billing --> DB
    Admin --> DB
    Audit --> DB
```

## Flujo de una operación

```mermaid
sequenceDiagram
    participant U as Usuario
    participant B as Blade / Alpine
    participant M as Middleware / Policy
    participant C as Controller
    participant R as Form Request
    participant S as Service
    participant E as Eloquent
    participant DB as SQL Server

    U->>B: envía formulario
    B->>M: request HTTP + CSRF
    M->>M: autenticar y autorizar
    M->>C: solicitud permitida
    C->>R: validar entrada
    R->>S: datos validados
    S->>S: aplicar reglas de negocio
    S->>E: persistir operación
    E->>DB: transacción SQL
    DB-->>E: resultado
    E-->>S: entidades actualizadas
    S-->>C: resultado del caso de uso
    C-->>B: redirect / respuesta
```

## Persistencia

- **SQL Server** es el motor principal de desarrollo y operación.
- **Eloquent ORM** encapsula relaciones y persistencia.
- Migraciones y seeders versionan el esquema y los datos iniciales.
- Las reglas sensibles, como disponibilidad, estados de alquiler, ITBIS y balances, permanecen en backend.

## Pruebas

```mermaid
flowchart LR
    PHPUnit["PHPUnit"] --> App["Laravel Application"]
    App --> SQLite[("SQLite in-memory")]
    Pint["Laravel Pint"] --> Source["Código PHP"]
    Vite["Vite Build"] --> Front["Assets frontend"]
    Actions["GitHub Actions"] --> PHPUnit
    Actions --> Pint
    Actions --> Vite
```

SQLite en memoria se utiliza para pruebas automatizadas rápidas; SQL Server continúa siendo la referencia de producción/desarrollo para la persistencia principal.

## Decisiones arquitectónicas

- Monolito modular antes que microservicios: los módulos comparten transacciones y ciclo de despliegue.
- Controllers delgados; la lógica reutilizable y reglas complejas deben vivir en servicios o modelos apropiados.
- Form Requests centralizan validación de entrada.
- Policies y middleware centralizan autorización.
- Blade/Alpine se mantienen como capa de presentación; no contienen reglas financieras autoritativas.
- Integraciones de documentos, reportes y almacenamiento permanecen separadas de la lógica principal.
