# Matriz de pruebas responsive y accesibilidad

Registro separado de **pruebas automatizadas**, **verificaciones visuales en navegador** y **pendientes o bloqueos**. Estado al 20-11-2026 sobre `main` (`e739023`, CI verde). Solo se anota lo comprobado; lo demás figura como pendiente.

## 1. Pruebas automatizadas (CI de GitHub, SQL Server 2022)

PHPUnit, Pint, `composer audit` y `npm audit` se ejecutan en cada PR. Pruebas relacionadas con este documento:

| Prueba | Qué protege |
| --- | --- |
| `DarkThemeContrastGuardTest` | Ninguna vista usa `text-slate-500` sin variante `dark:` en el mismo atributo `class`. |
| `UnlabeledFieldsAccessibilityTest` | Búsquedas y filtros con nombre accesible en Usuarios, Auditoría, Inspecciones y Catálogos. |
| `TouchTargetGuardTest`, `SummaryTouchTargetGuardTest` | Botones de componentes y `<summary>` declaran altura táctil mínima. |
| `FleetResponsiveViewTest` | Flota: tarjetas en móvil y tabla desde `md` por defecto; `?view=table` y `?view=cards` fuerzan una vista. |
| `PaymentsEmptyOpenInvoicesTest` | Pagos muestra un mensaje en lugar del formulario si no hay facturas con balance. |
| `LookupSelectTest` | Selectores con búsqueda: aislamiento entre empresas, enmascarado del documento, límite de 15 resultados, permisos, tipos desconocidos, id forastero. |
| `MobileDrawerAccessibilityTest` | El menú móvil declara `inert` cerrado/abierto y gestión de foco (solo comprueba el marcado). |
| `VehiclePhotoTest` | Subida, reemplazo, borrado, validación y permisos de la foto del vehículo. |

Las pruebas no verifican el aspecto visual ni el comportamiento de teclado; para eso están las secciones 2 y 3.

## 2. Verificaciones visuales en navegador

Método: navegador integrado de Claude Desktop con emulación de ventana, app local (`php artisan serve`, base `RentaDriveDb`, sesión de administrador, compilada con `npm run build`) y un script propio que mide, por página: desbordamiento horizontal, contraste WCAG AA de texto, campos sin nombre accesible, imágenes sin `alt`, botones sin nombre y objetivos menores de 24 px y de 44 px. El script mide; no sustituye una revisión humana.

| Ancho | Pantallas medidas | Resultado |
| --- | --- | --- |
| 320 | Login, Dashboard, Flota, Clientes, Reservas, formularios de alta (cliente, vehículo, reserva, alquiler, inspección), Catálogos, Usuarios, Configuración, Auditoría, Perfil fiscal, Pagos, Facturas, Reportes, Alquileres, Inspecciones, detalles (reserva, alquiler, factura, inspección, vehículo) y portal público de reservas | Sin desbordamiento, sin contraste fallido, sin campos sin nombre |
| 360 | 23 pantallas: Dashboard, Clientes, Flota, Catálogos, Reservas, Alquileres, Inspecciones, Facturas, Pagos, Reportes, Usuarios, Configuración, Auditoría, Perfil fiscal, Perfil, formularios de alta (cliente, vehículo, reserva, alquiler) y detalles (reserva, alquiler, factura, cliente) | Igual |
| 390 | Dashboard y su menú lateral; portal público | Igual |
| 640 (equivalente a 200 % de zoom sobre 1280) | Dashboard, Flota, formulario de reserva, Pagos, Configuración | Sin desbordamiento horizontal |
| 768 | Catálogos, Usuarios, Configuración, Auditoría, Perfil fiscal, Flota, Pagos, Facturas, Reportes, Dashboard, Clientes, Alquileres, Inspecciones, detalles (reserva, alquiler, factura, inspección, vehículo), portal público | Sin desbordamiento, sin contraste fallido |
| 844 × 390 (horizontal) | Dashboard, Flota, formulario de reserva, detalle de alquiler | Sin desbordamiento |
| 1280 | Catálogos, Auditoría, Flota | Sin desbordamiento |
| 1920 | Dashboard, Clientes, Flota, Reservas, Alquileres, Inspecciones, Facturas, Pagos, Reportes, Catálogos, Usuarios, Fiscal RD, portal público | Sin desbordamiento |

Con interacción real (clics y teclado):

- **Selectores con búsqueda** (reserva): lista con estados «Buscando…» y resultados, documento enmascarado, selección con Enter, autocompletado de categoría y tarifa al elegir vehículo.
- **Menú móvil a 390 px**: cerrado queda inerte; abierto, el foco pasa a «Cerrar menú» (44 × 44 px) y el fondo queda inerte; Escape cierra y devuelve el foco al botón de menú.
- **Flota a 320, 390, 768 y 1280 px**: tarjetas en móvil, tabla desde 768 px, sin recorte de contenido en las tarjetas.

Objetivos táctiles: no queda ninguno menor de 24 px en lo medido. Chips y botones de filtro siguen entre 24 y 44 px de alto (cumplen el mínimo de 24 px; no llegan a los 44 px recomendados para táctil).

Limitación conocida del entorno de prueba: al redimensionar una página ya cargada, el navegador integrado no vuelve a evaluar las media queries (ni el CSS ni `matchMedia`). Cada ancho se midió con la página recién cargada.

## 3. Pendientes

- Recuperación de contraseña (`/forgot-password`, `/reset-password`) y login a 360 y 390 px: la sesión de pruebas estaba iniciada y redirige al Dashboard; requiere cerrar sesión.
- Disponibilidad, cotización y confirmación del portal público con datos reales (ver `public-booking-mobile-audit.md`).
- Estado de error de los selectores con búsqueda (fallo de red) y formulario de Pagos con facturas abiertas: la base local no tiene facturas con balance.
- Zoom real del navegador al 200 % y 400 % (solo se emuló el ancho equivalente).
- Lector de pantalla (NVDA, VoiceOver, TalkBack): no probado.
- Safari en iOS y Chrome en Android reales: no probados; solo la emulación del navegador integrado.
- Pruebas E2E automatizadas con Playwright en CI: no implementadas.
- Subida, sustitución y eliminación de fotografías desde el navegador: cubiertas por `VehiclePhotoTest`; la subida real de un archivo desde el navegador no se ha podido ejecutar con el navegador integrado.

## 4. Flota: fotografías

- Tarjetas y detalle usan un área 16:9 uniforme, con «Sin fotografía» o «Fotografía no disponible» como alternativa (`docs/fleet-photo-display-check.md`).
- **Inconsistencia de datos de demostración (no corregida):** la ficha del Toyota Corolla 2025 (`RD-001`) registra el color **Blanco**, pero su fotografía muestra un vehículo rojo. No se modificó la ficha ni la imagen. Corrección segura propuesta: o bien actualizar el campo «Color» a lo que muestre la foto, o bien sustituir la foto por una imagen autorizada del color registrado, y hacerlo desde la interfaz como cualquier otro cambio (queda auditado).
- La fotografía de demostración del Hyundai Accent muestra una placa con la marca de un tercero (texto «ALKADY CARS»). No usarla en demostraciones públicas ni en producción; sustituirla por una imagen propia o autorizada.
- Almacenamiento: la fotografía se guarda en `vehicles/{company_id}/…` del disco configurado en `RENTA_VEHICLE_PHOTO_DISK` (`public` por defecto), servida por el enlace `public/storage`. Si se mueve la carpeta del proyecto hay que comprobar que ese enlace siga apuntando a `storage/app/public`.

## 5. Bloqueos externos (sin cambio)

Pasarela de pagos real, certificación e-CF DGII y proveedor autorizado, infraestructura de staging/producción y proveedor SMTP: **BLOQUEADOS POR DEPENDENCIA EXTERNA**. No se declara certificación fiscal ni preparación para producción.
