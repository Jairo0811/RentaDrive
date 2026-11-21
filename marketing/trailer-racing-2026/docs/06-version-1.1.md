# Versión 1.1 — mejoras sobre la v1.0

Partió de los materiales de la v1.0 (mismos clips A, C, D y E, mismo logotipo, música y cierre). No se rehízo el concepto.

## Qué cambió
| Área | v1.0 | v1.1 |
|---|---|---|
| Fotos de Flota | Tres variantes recortadas del mismo deportivo de la marca en fichas de modelos distintos | Tres vehículos genéricos **distintos** y coherentes con la ficha: sedán compacto gris, SUV azul, sedán blanco. Nombres ficticios (Aero Pulse, Aero Terra, Aero Vía) |
| Escena 3 (12–20 s) | Cortes de paneles casi estáticos | Transición por movimiento desde el auto, travelling lateral sobre tarjetas, acercamiento a la foto, enfoque HUD en tarifa y estado, listado y ficha |
| Escena 4 (20–28 s) | Capturas de formularios | Pasos **reales** del selector inteligente de cliente y de vehículo y de la tarifa autocompletada, con clic de interfaz sincronizado |
| Escena 5 (28–37 s) | Dashboard y reportes estáticos | Zoom y enfoque sobre indicadores, gráficos reales del dashboard, reportes con datos, facturación interna y pagos manuales (el tacómetro→barras se conserva) |
| Vertical 9:16 | Panel pequeño con la interfaz reducida | Alterna plano general (574×1020) y plano de detalle (960×930) sobre campo, tarifa o gráfico; todo dentro de la zona segura (260–1500 px) |
| Audio | Mezcla v1.0 | Cortes alineados a la rejilla de 0,5 s con clics de interfaz y barridos; pico verdadero −2,0 dBTP (la primera mezcla v1.1 llegó a +1,3 y se corrigió) |
| Cierre | Clip D + logotipo oficial | Sin cambios (se conserva) |

Escenas modificadas: 3, 4 y 5 (en ambos formatos). Escenas 1, 2 y 6 y los cierres de 8 s no cambiaron de contenido.

## Recursos sustituidos
- `assets/demo-vehicles/vehiculo-demo-{1,2,3}.jpg` (recortes del arte de la marca) → `sedan-compacto-gris.jpg`, `suv-azul.jpg`, `sedan-blanco.jpg`.
- Capturas nuevas: `h-form-0…4-*.png`, `v-form-0…4-*.png`, `h/v-dashboard-graficos.png`, `v-flota-tabla.png`, `v-vehiculo-detalle.png`; regeneradas el resto. Reportes ahora con datos (rango que incluye el alquiler ficticio).
- Se eliminó `capture/make-demo-photos.py`.

## Verificación automática (`render/qa_video.py`)
| Archivo | Resolución | FPS | Duración | Tamaño | Loudness / pico | Decodifica sin errores | Segmentos negros |
|---|---|---|---|---|---|---|---|
| `rentadrive-trailer-v1.1-16x9-1920x1080.mp4` | 1920x1080 | 30 | 45.0 s | 64.8 MB | -14.2 LUFS / -2.0 dBTP | sí | 0 |
| `rentadrive-trailer-v1.1-9x16-1080x1920.mp4` | 1080x1920 | 30 | 45.0 s | 64.6 MB | -14.2 LUFS / -2.0 dBTP | sí | 0 |
| `rentadrive-final-v1.1-16x9.mp4` | 1920x1080 | 30 | 8.0 s | 11.9 MB | -16.1 LUFS / -3.5 dBTP | sí | 0 |
| `rentadrive-final-v1.1-9x16.mp4` | 1080x1920 | 30 | 8.0 s | 12.6 MB | -16.1 LUFS / -3.5 dBTP | sí | 0 |

Comprobado: duración exacta, resolución, 30 fps, H.264 + AAC 48 kHz estéreo, decodificación completa sin errores, ausencia de cuadros negros accidentales y saturación. Fotogramas revisados visualmente (hoja de contacto de cada MP4 en `out/` y fotogramas a 3, 8, 14, 18, 22, 27, 32, 38, 42 y 44 s en ambos formatos): sin textos cortados ni superpuestos en esos puntos, sin deformación de autos, tres vehículos distintos, logotipo legible y aviso «EN DESARROLLO COMERCIAL · PRÓXIMAMENTE» visible.

## Pendiente de revisión humana (no verificable automáticamente)
- **Voz en off:** sigue siendo la voz sintética de referencia (Kokoro). No pude escucharla: pronunciación de «RentaDrive», entonación y naturalidad requieren oído humano. **No es una locución profesional definitiva**; para publicar, grabar un locutor con el mismo texto y tiempos y reemplazar `render/.work/vo/*.wav`.
- **Sincronización audio-imagen** por oído (los cortes están en la rejilla musical, pero no se auditó).
- **Fotos de vehículos y clips generativos:** revisar a mano que no haya logos, placas legibles ni deformaciones, y **confirmar los términos de uso comercial** de Google Flow y del generador de imágenes. Las imágenes recuerdan a diseños reales; por eso las fichas usan marcas ficticias.
- Reproducción en dispositivos reales y subida a Reels/TikTok (no probadas). Revisión de legibilidad en pantalla de teléfono.
- Mezcla/masterización por un ingeniero de sonido si va a campaña pagada.

## Dónde están los MP4
En `render/../out/` del entorno de trabajo (carpeta ignorada por git; no se suben al repositorio). La v1.0 se conserva en `out/v1.0/` para comparar. Ver «Distribución» en el README.
