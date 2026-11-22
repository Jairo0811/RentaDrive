# Content Security Policy (CSP)

La aplicación envía una CSP con **nonce por petición** desde `App\Http\Middleware\SecurityHeaders`
(política en `App\Support\Security\ContentSecurityPolicy`).

## Política vigente (modo `enforce`)

| Directiva | Valor | Motivo |
| --- | --- | --- |
| `default-src` | `'self'` | Todo lo no listado queda restringido al propio origen. |
| `script-src` | `'self'` `'nonce-…'` `'unsafe-eval'` | Sin scripts en línea sin nonce ni orígenes externos. **`'unsafe-eval'` sigue presente** (ver abajo). |
| `style-src-elem` | `'self'` `https://cdnjs.cloudflare.com` | Hojas propias y Font Awesome; sin `<style>` en línea. |
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

## Limitación conocida: `'unsafe-eval'`

Alpine.js estándar evalúa las expresiones de `x-data`, `@click`, `x-show`… con `new Function`. Quitar `'unsafe-eval'` exige migrar a la
**build CSP de Alpine** (`@alpinejs/csp`) y reescribir ~240 expresiones en ~12 vistas como componentes registrados con `Alpine.data()`.
Se deja como siguiente paso incremental y no se hizo aquí para no romper selectores, menús ni formularios sin pruebas de navegador por componente.
Mientras tanto la CSP sigue bloqueando scripts inyectados en línea y de orígenes externos, que es el vector habitual de XSS.

## Pendiente

- Migrar a `@alpinejs/csp` y eliminar `'unsafe-eval'` (y revisar `Chart.js`, que no lo necesita).
- Sustituir los `style=""` por clases para retirar `'unsafe-inline'` de `style-src-attr`.
- Alojar Font Awesome localmente para dejar de depender de cdnjs.
- Reporting API (`report-to`) además de `report-uri`.

## Verificación

- PHPUnit: `ContentSecurityPolicyTest` (nonce por petición, sin `unsafe-inline` en scripts/estilos, un único origen externo, modos, validación de orígenes, endpoint de reportes).
- Playwright: `e2e/csp.spec.js` falla ante cualquier violación en las pantallas principales (panel y páginas públicas), comprueba que Alpine
  y los gráficos funcionan y que un script inyectado en línea es bloqueado. Es verificación en Chromium; no sustituye pruebas en otros navegadores.
