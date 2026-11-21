# QA móvil (Bloque 4)

## Qué automatiza el CI (`e2e/movil.spec.js`, proyectos `mobile` y `mobile-pagos`)
Chromium emulando un teléfono (Pixel 5) contra la base descartable `RentaDriveE2E`, con datos ficticios.

- **360 px y 390 px**: `/login`, `/forgot-password` y el portal público `/book/{slug}` sin scroll horizontal, con encabezado y acción principal visibles.
- **Inicio de sesión**: credenciales inválidas muestran error; inicio válido llega al panel; solicitud de recuperación confirma el envío.
- **Reflujo equivalente a zoom 200 % y 400 %**: viewports de 640 y 320 px de ancho CSS (WCAG 1.4.10) en páginas públicas y en panel, reservas, nueva reserva, facturas y pagos, sin scroll horizontal de la página.
- **Selectores**: búsqueda sin resultados no selecciona nada ni lanza errores de JavaScript; enviar el formulario vacío muestra la validación.
- **Pago con facturas abiertas**: aplicar un pago desde Pagos a 390 px con una factura creada por el flujo E2E de escritorio.
- La recuperación completa con enlace (que cambia la contraseña E2E) pasó a `e2e/recuperacion.spec.js` y corre al final.

## Qué NO prueba (pendiente manual)
- **Zoom real** del navegador (Ctrl +) a 200 % y 400 %: el CI solo emula el ancho CSS equivalente.
- **Dispositivos físicos** iOS/Android: táctil real, teclado virtual, notch, rendimiento.
- **Lectores de pantalla** (VoiceOver, TalkBack, NVDA).
- Safari/WebKit y Firefox: solo se ejecuta Chromium.
- Pasarela de pago real y proveedores externos: bloqueados por dependencia externa.
- Contraste y tamaño de áreas táctiles: no se miden automáticamente.
