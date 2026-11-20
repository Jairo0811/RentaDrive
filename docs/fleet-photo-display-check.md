# Verificación de fotografía en Flota

## Comportamiento implementado

- Todas las tarjetas usan un área fotográfica 16:9, incluso cuando no hay foto.
- La ausencia de archivo muestra **«Sin fotografía»**.
- Una URL configurada cuyo archivo no puede cargarse cambia el contenido a **«Fotografía no disponible»** mediante el evento de error de la imagen (Alpine.js).
- Detalle y formulario de edición reutilizan el mismo componente de imagen.
- Los archivos existentes, rutas y columna \`photo_path\` permanecen inalterados.

## Confirmación en equipo real

El enlace \`public/storage\` del equipo de desarrollo fue corregido manualmente para apuntar a \`ITLA/RentaDrive/storage/app/public\`. Este PR no altera esa junction ni necesita migraciones.

Pruebas manuales pendientes: Flota en vistas Tarjetas y Tabla, 320/390/768/1280px, imagen válida, ausencia de imagen y URL que responda 404. Validar que la imagen no muestre el icono roto y que el placeholder mantenga la altura. Usar solamente datos sintéticos o archivos autorizados.
