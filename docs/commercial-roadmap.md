# RentaDrive — Roadmap comercial

RentaDrive evoluciona desde una versión académica/profesional monolítica hacia un producto SaaS para empresas de alquiler de vehículos, con prioridad inicial en República Dominicana.

## Objetivo de producto

Convertir RentaDrive en una plataforma multiempresa capaz de operar de forma segura varias rent-a-car desde una misma instalación, con aislamiento de datos, sucursales, reservas públicas, pagos, contratos digitales, automatizaciones y facturación fiscal integrable.

## Principios

1. No reescribir la v1: evolucionar de forma incremental.
2. Ningún tenant puede leer o modificar información de otro tenant.
3. SQL Server se mantiene como motor oficial.
4. Las migraciones deben conservar los datos existentes.
5. Toda funcionalidad comercial crítica requiere pruebas automatizadas.
6. Accesibilidad y auditoría continúan como requisitos transversales.
7. La adaptación dominicana será una ventaja competitiva del producto.

## Fase 1 — Commercial Foundation

### 1A. Identidad de empresa y sucursal

- [x] Modelo `Company`.
- [x] Modelo `Branch`.
- [x] Empresa y sucursal asociadas al usuario.
- [x] Contexto de tenant por petición.
- [x] Middleware obligatorio para rutas autenticadas.
- [x] Empresa/sucursal predeterminadas para preservar instalaciones v1.
- [x] Administración de usuarios limitada a la empresa activa.
- [x] Pruebas de tenant foundation.

### 1B. Aislamiento de datos operativos

- [x] Incorporar `company_id` en clientes.
- [x] Incorporar `company_id` y `branch_id` en flota.
- [x] Aislar categorías y tarifas por empresa.
- [x] Aislar reservas y alquileres.
- [x] Aislar inspecciones y mantenimientos.
- [x] Aislar facturas y pagos.
- [x] Aislar configuración y auditoría.
- [x] Sustituir índices únicos globales por índices únicos por tenant cuando corresponda.
- [x] Aplicar scopes automáticos de tenant.
- [x] Pruebas negativas de acceso cruzado en todos los módulos.

### 1C. Administración comercial

- [x] Perfil de empresa.
- [x] CRUD seguro de sucursales (eliminación solo sin relaciones; desactivación para historial existente).
- [x] Selección/asignación de sucursal por usuario.
- [x] SuperAdmin de plataforma separado de los administradores de tenant.
- [x] Estados de empresa: trial, active, suspended, cancelled.
- [x] Límites comerciales por plan para usuarios, sucursales y vehículos.

#### Planes base de 1C

| Plan | Usuarios | Sucursales | Vehículos |
| --- | ---: | ---: | ---: |
| Starter | 5 | 2 | 25 |
| Professional | 15 | 5 | 100 |
| Business | 50 | 20 | 500 |

Los límites se validan en backend. Los estados `suspended` y `cancelled` bloquean el tenant; `trial` requiere una fecha de vencimiento futura. La eliminación de sucursales es conservadora: una sucursal con relaciones operativas debe desactivarse en lugar de borrarse.

**Criterio de cierre de 1C:** npm audit sin vulnerabilidades high/critical, build de producción, Pint y PHPUnit deben pasar contra SQL Server 2022.

## Fase 2 — Booking Engine + experiencia comercial

### 2A. UI/UX comercial

- [x] Landing pública de RentaDrive separada del login administrativo.
- [x] Layout público responsive con modo claro/oscuro y accesibilidad existente.
- [x] Portal público por empresa usando el `slug` del tenant.
- [x] Tarjetas visuales de vehículos con fotografía, sucursal, datos operativos, tarifa y alerta de mantenimiento.
- [x] Fotografía principal administrable por vehículo, con almacenamiento público controlado.
- [x] Calendario operativo de 14 días por vehículo, sucursal, reservas, alquileres y mantenimiento.
- [x] Personalización white-label de logo, colores y dominio por empresa.

### 2B. Booking Engine

- [x] Disponibilidad por fechas y sucursal.
- [x] Filtro opcional por categoría.
- [x] Exclusión de reservas, alquileres activos, mantenimiento e inactivos.
- [x] Cotización calculada en backend según tarifa diaria y duración.
- [x] Checkout público con datos mínimos del cliente.
- [x] Reserva online creada como `pending`.
- [x] Confirmación visual con código de reserva.
- [x] Pruebas de aislamiento multi-tenant del portal público.
- [x] Tarifas semanales y mensuales mediante descuentos configurables por duración.
- [x] Tarifas de temporada mediante multiplicadores configurables por rango de fechas.
- [x] Extras y seguros configurables por día o cargo único.
- [x] Códigos promocionales porcentuales o fijos con vigencia opcional.
- [x] Política de cancelación por horas y cancelación self-service mediante enlace firmado.
- [x] Confirmaciones por email y adaptador de WhatsApp Cloud API configurable por tenant.

**Criterio de cierre de Fase 2:** toda cotización se recalcula en backend, persiste su desglose, respeta aislamiento multi-tenant y la suite CI debe pasar sobre SQL Server 2022.

## Fase 3 — Payments

- [x] Abstracción desacoplada de pasarela de pagos.
- [x] Depósitos configurables de reserva.
- [x] Pagos parciales/totales y libro de cobros auditable.
- [x] Reembolsos parciales/totales sin borrar evidencia.
- [x] Webhooks firmados e idempotentes.
- [x] Conciliación y reparación de saldos.

**Criterio de cierre de Fase 3:** cobros y reembolsos deben ser trazables, los webhooks no pueden duplicar movimientos y la conciliación debe reconstruir saldos desde el libro de pagos.

## Fase 4 — Dominican Edition

- [x] Perfil fiscal dominicano por tenant.
- [x] NCF y e-CF mediante secuencias autorizadas e integración desacoplada.
- [x] RNC, razón social y domicilio fiscal.
- [x] ITBIS general protegido en 18% y recálculo íntegro antes de emisión.
- [x] B01/B02 y e-CF E31/E32 con controles de secuencia, vencimiento y autorización.
- [x] Facturas fiscalizadas protegidas contra alteración posterior de importes.
- [x] Adaptador fiscal preparado para proveedor certificado o conector propio.

**Criterio de cierre de Fase 4:** RentaDrive controla identidad fiscal, secuencias y consistencia contable; la emisión e-CF real exige credenciales, autorización y proveedor/conector configurados en el entorno de despliegue.

## Fase 5 — Digital Rental

- Firma de contratos.
- Check-in y check-out móvil.
- Fotografías por inspección.
- Registro de daños.
- Combustible, kilometraje y accesorios.
- Evidencia auditable del proceso de entrega/devolución.

## Fase 6 — Automation

- Email y WhatsApp mediante proveedores desacoplados.
- Recordatorios de reservas y devoluciones.
- Alertas de mantenimiento y documentos.
- Jobs y colas de producción.

## Fase 7 — SaaS Production

- Suscripciones y planes.
- Trial y onboarding.
- Backups y recuperación.
- Observabilidad y alertas.
- Storage externo.
- Rate limiting y hardening.
- Pruebas E2E y de seguridad multi-tenant.
- Pipeline de despliegue reproducible.

## Meta de salida comercial

RentaDrive se considerará listo para pilotos pagados cuando tenga completadas las fases 1, 2, 3 y los controles de producción esenciales de la fase 7. La integración fiscal dominicana puede desplegarse de forma incremental según el proveedor y el proceso de certificación elegido.
