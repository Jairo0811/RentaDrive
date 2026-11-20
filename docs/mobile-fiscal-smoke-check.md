# RentaDrive — Validación móvil de Fiscal RD

**Alcance:** interfaz responsive del módulo `/fiscal`, sin cambios de emisión fiscal ni de base de datos.

## Ajustes de interfaz

- Tarjetas de estado: una columna en móvil, dos en tabletas, tres en escritorio amplio.
- Checklist: una columna hasta 1024 px, dos desde escritorio; último paso ocupa el ancho completo.
- Enlace accesible “Completar perfil fiscal” que desplaza el foco al formulario; el formulario tiene un encabezado propio.
- Los borradores usan tarjetas en menos de 640 px y tabla en pantallas mayores, sin duplicar acciones o datos persistidos.
- Entradas a 16 px para evitar zoom involuntario en Safari iOS y botones con área táctil de al menos 44 px en móvil.
- Toast con márgenes laterales sin desbordamiento en pantallas de 320 px.

## Matriz de comprobación visual MANUAL (pendiente de navegador real)

| Viewport | Verificaciones |
| --- | --- |
| 320 × 700 | No hay overflow horizontal; texto y botones legibles; menú hamburguesa abre/cierra; formulario completo |
| 360 × 800 | Todos los estados se apilan; enlace lleva al formulario; al escribir RNC no hay zoom inesperado |
| 390 × 844 | Checklist con 5 pasos; borradores en tarjetas; paginación y alertas visibles |
| 768 × 1024 | Estados en dos columnas; checklist en una; tabla fiscal desplazable si hace falta |
| 1280 × 800 | Tres estados en fila, checklist dos columnas y último paso de ancho completo |

Realizar también navegación por teclado, alto contraste, ajuste de tamaño de fuente, zoom al 200 %, modo apaisado y retorno mediante botón Atrás. No declarar accesibilidad WCAG certificada sin auditoría manual.
