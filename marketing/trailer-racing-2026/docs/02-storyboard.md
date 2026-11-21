# Storyboard — seis escenas

Cada cuadro describe el plano inicial y el movimiento. Los fotogramas de referencia reales se generan con `python render/render_video.py --format h --frame <segundo>` (guardados en `render/.work/`).

| # | Tiempo | Plano inicial → final | Cámara / movimiento | Elemento clave |
|---|---|---|---|---|
| 1 | 0:00–0:06 | Fundido desde negro (0,7 s) → clip A: auto en garaje, faros LED azules encienden → vapor y destello de luz por el capó → primer plano del faro | Destello leve en 2,1 s | Texto de la nueva era a 4,1 s (abajo en 16:9, arriba en 9:16). Recorte vertical centrado en el auto (35 % del ancho) |
| 2 | 0:06–0:12 | Líneas de luz sobre el ambiente oscuro de circuito → negro → logotipo | Barridos de luz; el logotipo se revela con halo azul a 9,4 s | Logotipo auténtico RentaDrive |
| 3 | 0:12–0:20 | Transición por movimiento (auto de competición desenfocado → interfaz) → Flota en tarjetas con travelling lateral → acercamiento a la foto → enfoque en tarifa y estado → listado → ficha | Cortes en la rejilla musical (15, 16, 17 y 18,5 s); corchetes HUD sobre tarifa y estado | Tres vehículos distintos y coherentes con su ficha (v1.1) |
| 4 | 0:20–0:28 | Reservas → selector de cliente (búsqueda «Rod») → cliente elegido → selector de vehículo → tarifa autocompletada → Alquileres → Inspecciones | Pasos REALES capturados de la aplicación; clic de interfaz sincronizado en cada paso | Sin estados simulados |
| 5 | 0:28–0:37 | Tacómetro al 93 % → barras → indicadores del Dashboard → gráficos → Reportes → Facturas → Pagos | Morfosis del velocímetro; zoom y enfoque HUD en KPI y gráficos | «Facturación interna» y «pagos manuales» (sin e-CF ni cobros en línea) |
| 6 | 0:37–0:45 | Arte oficial al ras del asfalto → ascenso de cámara → logotipo completo | Zoom 1,9→1,0 con inclinación hacia arriba; líneas de velocidad desaparecen | Lema, rótulo «EN DESARROLLO COMERCIAL · PRÓXIMAMENTE» y negro final |

## Versión vertical (9:16): recomposición, no recorte
**v1.1:** alterna plano general (panel completo de 574×1020) y plano de detalle (960×930 sobre un campo, tarifa o gráfico), siempre dentro de la zona segura de Reels/TikTok (y entre 260 y 1500 px).
- Capturas del navegador en viewport móvil real (540×960 a 2× = 1080×1920) dentro de un marco central de 700×1244.
- Escenas 3, 4 y 5 usan solo 2–3 pantallas por escena (más tiempo en pantalla) en lugar de cinco cortes.
- El logotipo y el cierre se recomponen en columna: franja del arte oficial en el tercio superior-medio, lema y rótulo debajo, dentro del área segura.
