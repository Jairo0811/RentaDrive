# Voz en off

**Dirección:** español latinoamericano, voz masculina cinematográfica, firme, moderna y profesional; ritmo pausado (≈ 0,9×), sin prisa, con respiros antes de cada golpe de música.

| Id | Texto | Inicio (s) | Duración (s) |
|---|---|---|---|
| l1 | Cada segundo cuenta. | 2,0 | 1,5 |
| l2 | Cada vehículo representa una oportunidad. | 6,5 | 2,8 |
| l3 | Cada reserva mueve tu negocio. | 9,5 | 2,2 |
| l4 | Por eso, el control debe estar a otro nivel. | 12,4 | 2,8 |
| l5 | Flota. Reservas. Clientes. Operaciones. | 20,4 | 2,6 |
| l6 | Todo conectado. Todo bajo control. | 24,6 | 2,3 |
| l7 | RentaDrive. | 38,0 | 1,3 |
| l8 | Gestiona tu flota. Impulsa tu negocio. | 39,9 | 2,8 |
| l9 | Próximamente. | 43,2 | 1,3 |

Pronunciación de marca: «Renta-Drive» (como en el logotipo, sin pausa entre las dos palabras).

## Estado de la voz del render
La versión renderizada usa **voz sintética de referencia** (Kokoro-82M, licencia Apache-2.0, voz masculina `em_alex`). Es un *guide track* correcto en tiempos y contenido, pero **no es un locutor profesional** y su acento es neutro. Para la versión de lanzamiento se recomienda grabar un locutor real con este mismo texto y tiempos y sustituir los `.wav` de `render/.work/vo/` y ejecutar de nuevo `build_audio.py`.

La música original y los efectos son sintetizados por código (`render/build_audio.py`); ninguna canción ni muestra de terceros.
