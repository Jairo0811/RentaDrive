# Mensajes de validación de reservas

- El formulario de alta/edición ahora muestra un resumen accesible de errores, sin eliminar mensajes ni validaciones de backend.
- Los mensajes aparecen junto a sus campos, incluidos los combobox con búsqueda.
- Cada campo con error tiene `aria-invalid=true` y referencia a su mensaje mediante `aria-describedby`.
- Los selectores con búsqueda de otros formularios también muestran sus errores de servidor.
- No cambia políticas, reglas, base de datos ni disponibilidad. Se prueban respuestas de Laravel con errores de sesión en `ReservationFormFeedbackTest`.

QA manual: confirmar en navegador que un período incorrecto, una categoría sin disponibilidad y un cliente no válido muestren mensajes claros; repetir desde 320px y con teclado.
