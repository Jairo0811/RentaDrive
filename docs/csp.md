# Content Security Policy (CSP)

La aplicación envía una CSP con **nonce por petición** desde `App\Http\Middleware\SecurityHeaders`
(política en `App\Support\Security\ContentSecurityPolicy`).

## Política vigente (modo `enforce`)

| Directiva | Valor | Motivo |
| --- | --- | --- |
| `default-src` | `'self'` | Todo lo no listado queda restringido al propio origen. |
| `script-src` | `'self'` `'nonce-…'` | Sin scripts en línea sin nonce, sin orígenes externos y **sin `'unsafe-eval'`** (Alpine usa su build CSP). |
| `style-src-elem` | `'self'` `'nonce-…'` `https://cdnjs.cloudflare.com` | Hojas propias y Font Awesome; un `<style>` en línea solo con nonce (p. ej. el contrato imprimible). |
| `style-src-attr` | `'unsafe-inline'` | Atributos `style=""` existentes (anchos de barras, visibilidad de componentes). |
| `font-src` | `'self'` `data:` `https://cdnjs.cloudflare.com` | Iconos Font Awesome. |
| `img-src` | `'self'` `data:` `blob:` + origen del disco de fotos si es externo | Fotos de flota en S3/CDN se autorizan automáticamente. |
| `connect-src` | `'self'` | `fetch` de selectores y búsquedas. |
| `object-src` `frame-src` `frame-ancestors` | `'none'` | Sin plugins ni marcos, y no se puede incrustar la app. |
| `form-action` `base-uri` | `'self'` | Impide redirigir formularios o `<base>` a otro origen. |
| `report-uri` | `/csp-report` | Las violaciones se registran como `security.csp_violation` (solo origen y ruta, sin consultas). |

En producción con HTTPS se añade `upgrade-insecure-requests`.

## Modos y variables

- `CSP_MODE=enforce` (por defecto) · `report-only` (no bloquea, solo informa; útil para probar cambios) · `off` (solo diagnóstico).
- `CSP_IMG_SRC` y `CSP_CONNECT_SRC`: orígenes adicionales `https://host` separados por coma (se validan; valores no válidos se descartan).

## Desarrollo local (Vite)

Con el servidor de Vite activo (`public/hot`) y fuera de producción, la política añade automáticamente su origen HTTP y WebSocket
(`localhost`/`127.0.0.1`/el host anunciado) a `script-src`, `style-src-elem`, `font-src` y `connect-src`, y permite `'unsafe-inline'` en
`style-src-elem` (Vite inyecta CSS durante HMR). **Nada de esto se aplica en producción**, aunque exista un `public/hot` olvidado.
Los scripts de `@vite` reciben el nonce mediante `Vite::useCspNonce()`.

## Scripts en línea

Cualquier `<script>` en línea debe llevar `nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}"`. No se admiten manejadores
`onclick=` en las vistas (se migraron los de las páginas 404/500).

Las respuestas 404 (ruta inexistente o modelo no encontrado en una ruta con binding) también llevan CSP: `SecurityHeaders` es middleware global, no solo del grupo `web`.

## Alpine con build CSP (sin `'unsafe-eval'`)

La interfaz usa `@alpinejs/csp`. Esta build no evalúa JavaScript desde atributos: solo admite expresiones simples (propiedades, llamadas a
métodos, ternarios, asignaciones, literales). Las lógicas con funciones flecha, plantillas, sentencias `if` o acceso a `window/document` viven
en componentes registrados en `resources/js/app.js` (`appShell`, `modalDialog`, `autoHide`, `lookupSelect`, stores `toast` y `accessibility`).
Una expresión no permitida **no rompe la página**, pero esa interacción deja de funcionar y Alpine escribe `Alpine Expression Error` en consola;
la prueba `e2e/csp.spec.js` falla si aparece alguno en las pantallas principales.

Al añadir interactividad: usa `x-data="componente"`/`Alpine.data()`, no funciones ni `${}` en atributos.

## Pendiente

- Sustituir los `style=""` por clases para retirar `'unsafe-inline'` de `style-src-attr`.
- Alojar Font Awesome localmente para dejar de depender de cdnjs.
- Reporting API (`report-to`) además de `report-uri`.

## Verificación

- PHPUnit: `ContentSecurityPolicyTest` (nonce por petición, sin `unsafe-inline` en scripts/estilos, un único origen externo, modos, validación de orígenes, endpoint de reportes).
- Playwright: `e2e/csp.spec.js` falla ante cualquier violación en las pantallas principales (panel y páginas públicas), comprueba que Alpine
  y los gráficos funcionan y que un script inyectado en línea es bloqueado. Es verificación en Chromium; no sustituye pruebas en otros navegadores.
