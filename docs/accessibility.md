# Accesibilidad de RentaDrive

RentaDrive adopta una línea base de accesibilidad inspirada en **NORTIC B2:2017** y las pautas WCAG 2.0 utilizadas por la norma. El objetivo técnico de esta fase es aproximar la interfaz al **nivel AA**, sin afirmar certificación formal hasta completar una auditoría integral de todas las páginas y procesos.

## Principios aplicados

### Perceptible

- Alternativas textuales para imágenes informativas.
- Imágenes decorativas excluidas de tecnologías asistivas.
- Preferencias de alto contraste.
- Escalado de texto al 100 %, 125 %, 150 % y 200 %.
- Color acompañado de texto, iconos o estados explícitos.

### Operable

- Enlaces para saltar directamente al contenido y a la navegación.
- Indicadores de foco visibles.
- Navegación mediante teclado.
- Menús que se cierran con `Escape`.
- Reducción de movimiento manual y respeto de `prefers-reduced-motion`.

### Comprensible

- Idioma del documento definido dinámicamente.
- Etiquetas descriptivas para controles.
- Mensajes de validación textuales y anunciados mediante regiones `alert`.
- Navegación y controles identificados de forma coherente.

### Robusto

- Uso de elementos semánticos (`header`, `main`, `aside`, `footer`).
- Relaciones ARIA para menús, diálogos y controles expandibles.
- Estados accesibles mediante `aria-expanded`, `aria-pressed`, `aria-live` y `aria-atomic`.

## Correspondencia inicial con NORTIC B2:2017

| Criterio | Implementación inicial |
|---|---|
| 3.01.1.a Contenido no textual | `alt` descriptivo o vacío según propósito |
| 3.01.3.a Información y relaciones | Regiones y estructura semántica |
| 3.01.4.a Uso del color | Estados acompañados por texto e iconos |
| 3.01.4.c Contraste mínimo | Modo de alto contraste y revisión visual pendiente |
| 3.01.4.d Cambio de tamaño del texto | Escalado hasta 200 % |
| 3.02.1.a Teclado | Controles operables por teclado |
| 3.02.1.b Sin trampas para el foco | Cierre con Escape y navegación estándar |
| 3.02.4.a Evitar bloques | Enlaces de salto |
| 3.02.4.b Titulado de páginas | Títulos descriptivos mediante layout |
| 3.02.4.c Orden de foco | Orden DOM coherente |
| 3.02.4.g Foco visible | Foco reforzado en amarillo |
| 3.03.1.a Idioma de la página | Atributo `lang` dinámico |
| 3.03.3.a Identificación de errores | Resumen de errores con `role="alert"` |
| 3.03.3.b Etiquetas o instrucciones | Componentes de formulario etiquetados |
| 3.04.1.b Nombre, función, valor | ARIA en menús, botones y preferencias |

## Pendientes para auditoría completa

- Revisar contraste de todos los estados y componentes con herramientas automáticas.
- Verificar cada formulario, tabla, modal y flujo financiero con lector de pantalla.
- Añadir títulos de página específicos en todas las vistas.
- Revisar encabezados de tablas, `scope`, captions y tablas responsivas.
- Validar orden de tabulación en procesos de reservas, alquileres, pagos y configuración.
- Añadir pruebas automatizadas con axe-core o Lighthouse CI.
- Documentar una declaración de accesibilidad y un canal para reportar barreras.

## Validación manual recomendada

1. Navegar toda la aplicación usando únicamente `Tab`, `Shift + Tab`, `Enter`, `Space` y `Escape`.
2. Aumentar el texto al 200 % y confirmar que no se pierda contenido ni funcionalidad.
3. Probar modo claro, oscuro y alto contraste.
4. Activar reducción de movimiento.
5. Probar con NVDA en Windows y verificar nombres, roles, estados y errores.
6. Ejecutar Lighthouse Accessibility y axe DevTools en cada módulo principal.
