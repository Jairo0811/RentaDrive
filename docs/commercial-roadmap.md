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

- [ ] Incorporar `company_id` en clientes.
- [ ] Incorporar `company_id` y `branch_id` en flota.
- [ ] Aislar categorías y tarifas por empresa.
- [ ] Aislar reservas y alquileres.
- [ ] Aislar inspecciones y mantenimientos.
- [ ] Aislar facturas y pagos.
- [ ] Aislar configuración y auditoría.
- [ ] Sustituir índices únicos globales por índices únicos por tenant cuando corresponda.
- [ ] Aplicar scopes automáticos de tenant.
- [ ] Pruebas negativas de acceso cruzado en todos los módulos.

### 1C. Administración comercial

- [ ] Perfil de empresa.
- [ ] CRUD de sucursales.
- [ ] Selección/asignación de sucursal por usuario.
- [ ] SuperAdmin de plataforma separado de los administradores de tenant.
- [ ] Estados de empresa: trial, active, suspended, cancelled.
- [ ] Límites comerciales por plan.

## Fase 2 — Booking Engine

- Sitio público por empresa.
- Disponibilidad por fechas/categoría/sucursal.
- Cotización.
- Extras y seguros.
- Reserva online.
- Confirmaciones y cancelaciones.

## Fase 3 — Payments

- Abstracción de pasarela de pagos.
- Depósitos de reserva.
- Pagos parciales/totales.
- Reembolsos.
- Webhooks idempotentes.
- Conciliación y auditoría.

## Fase 4 — Dominican Edition

- Perfil fiscal dominicano.
- NCF/e-CF mediante integración compatible.
- RNC y datos fiscales.
- ITBIS y reglas fiscales configuradas con controles de integridad.
- Integraciones locales cuando sean técnicamente y legalmente viables.

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
