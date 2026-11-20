# RentaDrive · Portal público de reservas: accesibilidad móvil

**Alcance:** interfaz de búsqueda, disponibilidad, cotización y confirmación. Ninguna regla de disponibilidad, pagos ni almacenamiento fue modificada.

## Mejoras

- El nombre comercial de la empresa ya puede contraerse sin empujar el encabezado fuera del viewport.
- En disponibilidad, las categorías apilan su título y tarifa en móviles estrechos; los importes se muestran sin columnas comprimidas.
- Botones y enlaces principales tienen objetivos táctiles de altura suficiente; el enlace de regreso a categorías no es un objetivo diminuto.
- En la cotización, los extras utilizan checkboxes de 20 px dentro de etiquetas completas, el teléfono activa teclado telefónico y el total actualizado se anuncia a lectores de pantalla solo cuando el cliente modifica una selección.
- La confirmación de reserva admite títulos largos y el botón de cancelación mantiene una altura táctil de 44 px.

## Matriz de aceptación manual PENDIENTE

Realizar con datos sintéticos y **sin publicar el servidor de desarrollo**:

1. 320 y 360 px: portada, tarjetas de disponibilidad, selección de opciones, datos de cliente, resumen y confirmación.
2. 390 px: verificar que no hay desplazamiento horizontal ni elementos cortados.
3. 768 y 1280 px: comprobar tarjetas de dos columnas, resumen y enlaces de navegación.
4. Navegación completa por teclado, foco visible, zoom del 200 %, pantalla horizontal, VoiceOver/TalkBack, alto contraste.
5. Repetir validaciones incorrectas de sucursal, fechas y documento; el cálculo del servidor debe seguir siendo autoritativo.
6. Comprobar que una reserva privada de empresa A no es visible desde empresa B.

La suite CI comprueba rutas y markup; **no sustituye pruebas visuales móviles ni certifica WCAG**.
