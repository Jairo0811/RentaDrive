# Storyboard — seis escenas

Cada cuadro describe el plano inicial y el movimiento. Los fotogramas de referencia reales se generan con `python render/render_video.py --format h --frame <segundo>` (guardados en `render/.work/`).

| # | Tiempo | Plano inicial → final | Cámara / movimiento | Elemento clave |
|---|---|---|---|---|
| 1 | 0:00–0:06 | Fundido desde negro (0,7 s) → clip A: auto en garaje, faros LED azules encienden → vapor y destello de luz por el capó → primer plano del faro | Destello leve en 2,1 s | Texto de la nueva era a 4,1 s (abajo en 16:9, arriba en 9:16). Recorte vertical centrado en el auto (35 % del ancho) |
| 2 | 0:06–0:12 | Líneas de luz sobre el ambiente oscuro de circuito → negro → logotipo | Barridos de luz; el logotipo se revela con halo azul a 9,4 s | Logotipo auténtico RentaDrive |
| 3 | 0:12–0:20 | Flota en tarjetas → Flota en listado → ficha del vehículo | Zoom lento 1,0→1,1 dentro de panel HUD; barrido de luz en cada corte | Fotos, categorías, estados, tarifas y kilometraje reales |
| 4 | 0:20–0:28 | Reservas → Clientes → Formulario (selector abierto) → Alquileres → Inspecciones | Cortes de ~1,7 s a ritmo de corcheas | Selector con búsqueda funcionando |
| 5 | 0:28–0:37 | Tacómetro al 93 % → sus segmentos viajan y forman barras → Dashboard → Reportes → Facturas → Pagos | Morfosis del velocímetro en gráfico (29,5–30,5 s) | «Facturación interna» y «pagos manuales» (sin e-CF ni cobros en línea) |
| 6 | 0:37–0:45 | Arte oficial al ras del asfalto → ascenso de cámara → logotipo completo | Zoom 1,9→1,0 con inclinación hacia arriba; líneas de velocidad desaparecen | Lema, rótulo «EN DESARROLLO COMERCIAL · PRÓXIMAMENTE» y negro final |

## Versión vertical (9:16): recomposición, no recorte
- Capturas del navegador en viewport móvil real (540×960 a 2× = 1080×1920) dentro de un marco central de 700×1244.
- Escenas 3, 4 y 5 usan solo 2–3 pantallas por escena (más tiempo en pantalla) en lugar de cinco cortes.
- El logotipo y el cierre se recomponen en columna: franja del arte oficial en el tercio superior-medio, lema y rótulo debajo, dentro del área segura.
