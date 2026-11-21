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

## Fase 2 — Booking Engine

- [x] Sitio público por empresa mediante slug propio (`/book/{empresa}`).
- [x] Disponibilidad por fechas, categoría y sucursal.
- [x] Capacidad compartida entre reservas públicas e internas para evitar sobreventa.
- [x] Cotización server-side con días, tarifa diaria, subtotal y depósito.
- [x] Extras y seguros configurables por tenant con precio fijo o por día.
- [x] Reserva online sin cuenta de cliente.
- [x] Token público privado para consulta de reserva.
- [x] Confirmación web y cancelación autoservicio antes del retiro.
- [x] Throttling en creación y cancelación públicas.
- [x] Bloqueo transaccional por sucursal para serializar la toma del último cupo.
- [x] Backoffice identifica reservas Online vs. Internas y muestra su desglose.
- [x] Pruebas E2E de aislamiento, cotización, capacidad, creación y cancelación.

### Modelo de cotización de 2.0

La reserva pública se realiza por categoría. La unidad concreta puede asignarse después por el equipo operativo. El motor calcula la disponibilidad física de la sucursal, descuenta vehículos ya comprometidos y también reservas por categoría todavía sin vehículo asignado.

Los extras/seguros admiten dos modos de precio:

- `flat`: importe único por reserva.
- `per_day`: precio multiplicado por la duración calculada de la reserva.

El depósito se informa durante la cotización pero no se cobra todavía; el cobro online comienza en la Fase 3.

**Criterio de cierre de Fase 2:** migración SQL Server, portal público, backoffice, npm audit, build, Pint y PHPUnit deben quedar verdes en CI.

## Fase 2.1 — Hardening previo a Payments

- [x] Service Worker limitado a recursos públicos estáticos; no almacena HTML autenticado, dashboard ni URLs privadas con token.
- [x] Protección del último Administrador activo de cada empresa.
- [x] Protección del último SuperAdmin activo de plataforma.
- [x] Bloqueo de desactivación o cambio de rol cuando dejaría una empresa sin Administrador activo.
- [x] Validación de cédula dominicana en reservas públicas con normalización del documento.
- [x] Normalización y validación consistente de pasaportes en el portal público.
- [x] Seeders de datos operativos demo restringidos a `local` y `testing`.
- [x] Configuración de ejemplo endurecida para despliegue HTTPS.
- [x] `composer.lock` sincronizado con el requisito de PHP de aquel momento (el requisito vigente es PHP ^8.4.27, ver `composer.json`).
- [x] CI con `composer validate --strict`, `composer audit --locked`, `npm audit`, build, Pint y PHPUnit sobre SQL Server 2022.
- [x] Pruebas de regresión específicas para continuidad administrativa, documentos públicos, PWA y seeders.
- [x] README actualizado al estado SaaS/comercial actual.

**Criterio de cierre de Fase 2.1:** Composer y npm sin vulnerabilidades conocidas que rompan los gates definidos, metadata reproducible, build de producción, Pint y suite completa verdes sobre SQL Server 2022.

## Fase 3 — Payments (en desarrollo; sandbox)

- [x] Intentos multi-tenant de depósito y eventos firmados.
- [x] Contrato de proveedor desacoplado; adaptador solo sandbox (local/testing).
- [x] Callback HMAC, validación de importes y eventos idempotentes.
- [x] Captura/reembolso simulado y trazabilidad.
- [x] Anulación lógica de pagos manuales.
- [ ] Integrar pasarela real, credenciales, cobros, reembolsos y conciliación.
- [ ] Liquidación segura del depósito al alquiler/factura.

- [ ] Pagos parciales y totales online mediante pasarela real (hoy solo intentos sandbox y pagos manuales).
- [ ] Conciliación contra el extracto de la pasarela y registro de fallos.

## Fase 4 — Dominican Edition (en desarrollo; borradores)

- [x] Puerto desacoplado de proveedor e-CF con adaptador deshabilitado y pruebas fail-closed.
- [x] Borradores enriquecidos con tipo **candidato** E31/E32; sin e-NCF ni XML emitido.
- [x] Estado visible de integración e-CF y guía de requisitos por empresa (docs/ecf-provider-integration.md).
- [ ] Seleccionar e integrar proveedor autorizado, habilitar cada contribuyente y validar XML/firma/e-NCF/acuse antes de emitir.
- [x] Perfil fiscal multiempresa (razón social, RNC, dirección).
- [x] Borradores fiscales vinculados a facturas; NO se emiten NCF/e-CF.
- [x] Formato RNC y presentación administrativa dark-only.
- [ ] Verificación tributaria de datos en fuentes autorizadas.
- [ ] Proveedor e-CF certificado, firma digital, XML, acuses y DGII.
- [ ] Gestión de rangos NCF, notas de crédito y certificación.

- [ ] Reglas de ITBIS configurables con controles de integridad (no verificado en este repositorio; revisar antes de declararlo).

## Fase 5 — Digital Rental (en desarrollo)

- [x] Evidencia fotográfica nueva en almacenamiento privado por tenant con descargas autorizadas.
- [x] Acuse de operador auditable sobre datos del contrato y hash SHA-256.
- [ ] Firma digital certificada y consentimiento verificable del cliente.
- [ ] Migración de fotografías públicas existentes y storage externo.

- [ ] Validar en dispositivos móviles reales el flujo de inspección de entrega y devolución (fotografías, kilometraje, combustible, daños y accesorios); no se ha probado en teléfono.

## Fase 6 — Automation (en desarrollo)

- [x] Alertas de reservas para mañana, devoluciones vencidas y mantenimientos próximos por correo.
- [x] Opt-in de despliegue por variable de entorno (deshabilitado de fábrica).
- [x] Historial idempotente multiempresa de entregas por evento/destinatario/canal.
- [ ] Canal WhatsApp real, proveedor autorizado y opt-in del destinatario.
- [ ] Reintentos supervisados, alertas, monitoreo de colas y rendimiento de producción.


## Fase 7 — SaaS Production (preparación técnica)

- [x] Planes y límites manuales; historial de cambios comerciales por empresa y operador.
- [x] Onboarding existente y expiración idempotente de trials con scheduler.
- [x] Comando de preflight (entorno, TLS, cookies, SQL Server, cache, evidencias).
- [x] Cabeceras HTTP de seguridad y no-cache para datos privados.
- [x] Disco privado configurable por inspección; adaptador S3 para almacenamiento compartido.
- [x] Pipeline para empaquetar release reproducible con checksum (sin desplegar).
- [x] Manual de operaciones, backups, restauración, monitoreo y rollback.
- [ ] Suscripciones cobradas mediante proveedor y conciliación real.
- [ ] Provisionamiento real de infraestructura, secretos, TLS, DNS, storage, backups y alertas.
- [ ] Migración de fotografías públicas legacy a almacenamiento privado.
- [ ] Simulacro real de recuperación, E2E y auditoría de seguridad/carga en producción.
- [ ] Despliegue controlado y verificación posterior.

La Fase 7 no se considera terminada comercialmente hasta verificar estos puntos externos en infraestructura real.

## Meta de salida comercial

RentaDrive no considera intentos sandbox ni borradores fiscales como cobros o comprobantes reales. Se considerará listo para pilotos pagados cuando tenga completadas las fases 1, 2, 2.1, 3 y los controles de producción esenciales de la fase 7. La integración fiscal dominicana puede desplegarse de forma incremental según el proveedor y el proceso de certificación elegido.

## Registro de revisión (noviembre de 2026)

Estado verificado al 20-11-2026 sobre `main` (`e739023`, CI verde). Solo se listan hechos comprobados. El detalle de pruebas automatizadas, verificaciones visuales y pendientes está en [`docs/responsive-test-matrix.md`](responsive-test-matrix.md).

| Elemento | Estado |
| --- | --- |
| PR #21 a #28 | Fusionados. Interfaz de flota y dashboard, accesibilidad de filtros y estados vacíos, enmascarado del documento en el listado de clientes, contraste, validación del rango de Reportes, correcciones de tablas y correo del administrador sin precarga fuera de `local`/`testing`. |
| PR #29 a #31 | Fusionados. Base e-CF desacoplada (sin emisión), lista de habilitación y optimización móvil de Fiscal RD. |
| PR #33 | Fusionado. Foto principal del vehículo (migración `photo_path`, disco configurable). Requiere `php artisan migrate` y `php artisan storage:link`. |
| PR #34, #35, #37, #38 | Fusionados. Alturas táctiles mínimas, Flota en tarjetas en móvil y tabla desde `md`, Pagos sin formulario cuando no hay facturas con balance. |
| PR #39 a #41 | Fusionados. Recuperación de contraseña en español, portal público de reservas responsive y fotografías uniformes en Flota. |
| PR #42 | Fusionado. Selectores con búsqueda en el servidor (clientes, vehículos, alquileres, facturas) con límite de 15 resultados, permisos por operación y aislamiento entre empresas. |
| PR #43 | Fusionado. Menú lateral móvil inerte cuando está cerrado y gestión de foco. |
| PR #12 | **Cerrado sin fusionar el 19-11-2026; la rama `feature/commercial-booking-ui` se conserva.** Sus piezas útiles se recuperan una a una en ramas nuevas con CI (ver «Piezas pendientes de extraer»). La calidad y la seguridad de ese código no se han revisado. |

| Seguridad previa a staging | Implementado y probado en CI en la PR de endurecimiento (ver `docs/security-audit.md`). Pendiente: rotar el login SQL local, CSP, MFA y escaneo de secretos en CI. |

### Piezas pendientes de extraer de la rama de #12

Cada una irá en un PR propio, con pruebas y revisión de seguridad previa. Ninguna está en `main`.

- [ ] Webhooks y reembolsos de pago
- [ ] Suscripciones
- [ ] Firma de contrato
- [ ] Respaldos
- [ ] Chequeo de salud
- [ ] `Dockerfile`
- [ ] Calendario de flota

### Verificado en navegador (administrador, entorno local)

Ver la matriz por ancho en `docs/responsive-test-matrix.md`: 320, 360, 390, 640, 768, 844 × 390, 1280 y 1920 px en las pantallas allí listadas, sin desbordamiento horizontal ni contraste fallido según el script de medición, más pruebas con clic y teclado del selector con búsqueda y del menú móvil.

### Sin verificar

Recuperación de contraseña y login a 360/390 px; estados de error de los selectores con búsqueda; disponibilidad, cotización y confirmación del portal público con datos reales; zoom real al 200 % y 400 %; lector de pantalla; Safari iOS y Chrome Android reales. Las pruebas E2E automatizadas (Playwright, Chromium de escritorio) ya corren en CI; ver `docs/responsive-test-matrix.md`.
