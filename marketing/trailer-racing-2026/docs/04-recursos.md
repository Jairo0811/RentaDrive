# Lista de recursos audiovisuales

## Marca (del repositorio, propios)
| Recurso | Uso |
|---|---|
| `public/images/rentadrive-racing.png` (arte oficial racing 1672×941) | Escena 6 (cierre, revelado del logotipo), fondo desenfocado en escenas 2–5 y fotos de demostración de los vehículos. Ya NO se usa como si fuera un auto real en las escenas 1 y 2 |
| `public/images/rentadrive-logo-dark.png` | Logotipo auténtico con letras blancas para fondo oscuro (escena 2B) |
| `public/images/rentadrive-mark.png` | Reservado para versiones futuras (marca de agua no usada) |

## Capturas reales de la interfaz (`captures/`)
Generadas por Playwright en una base **descartable** con datos ficticios (workflow `Marketing captures`). No se usó `RentaDriveDb`.

| Archivo (16:9, 1920×1080) | Escena |
|---|---|
| `h-flota-tarjetas.png`, `h-flota-tabla.png`, `h-vehiculo-detalle.png` | 3 |
| `h-reservas.png`, `h-clientes.png`, `h-reserva-formulario.png`, `h-alquileres.png`, `h-inspecciones.png` | 4 |
| `h-dashboard.png`, `h-reportes.png`, `h-facturas.png`, `h-pagos.png` | 5 |
| `v-*.png` (9:16, 1080×1920) | versión vertical |

Datos ficticios: clientes «María Rodríguez», «Carlos Méndez», «Ana Peña» (documentos y correos de ejemplo `example.test`), placas de demostración `A123456`, `G234567`, `A345678`. Las fotos de los vehículos de demostración son recortes del arte oficial de la marca (`assets/demo-vehicles/`), no fotos de terceros. No aparecen contraseñas, tokens, correos reales ni datos fiscales.

## Audio (original)
- `render/build_audio.py`: motor V8 sintetizado (RPM programadas), arranque, cambios de marcha, bajo, bombo, caja, hats, arpegio, pad, risers e impactos.
- `render/make_voiceover.py`: voz en off sintética de referencia (ver `03-voz-en-off.md`).

## Tipografía
Inter Bold/SemiBold Italic (SIL OFL 1.1).

## Pendiente de herramientas externas (NO incluido en el render)
- Clips cinematográficos reales/generativos de autos (circuito nocturno, garaje, asfalto mojado). Ver `05-prompts-generativos.md`. El render actual los sustituye con gráficos HUD procedurales (tacómetro, líneas de velocidad); NO hay planos de auto real en la apertura.
- Locutor profesional (ver `03-voz-en-off.md`).
- Mezcla/masterización final por un ingeniero de sonido si se usa en campaña pagada.
