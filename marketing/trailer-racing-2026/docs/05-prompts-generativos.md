# Prompts para escenas racing generativas (Flow / Higgsfield u otra herramienta)

**Estado:** PENDIENTE. No se generó ningún clip generativo en este trabajo (requiere herramienta y créditos externos). Estos prompts reemplazarían los planos animados del arte oficial en las escenas 1, 2 y 6.

**Bloque de continuidad (pegar al inicio de cada prompt):**
> Cinematic motorsport commercial, night, wet asphalt with blue neon reflections, deep black shadows, electric blue (#0568F5) LED accent lighting, silver metallic bodywork, subtle haze and sparks of light, shallow depth of field, anamorphic lens flares, 24 fps look, high contrast, premium automotive brand film. Modern silver sports coupe with blue LED headlights, **no brand badges, no readable license plates, no text, no people's faces**. Safe, controlled, closed-circuit driving only; no crashes, no street racing, no dangerous driving.

**Negativo común:** logos de marcas reales, matrículas legibles, texto, rostros, accidentes, humo excesivo, estética de videojuego, cartoon.

| Escena | Duración | Prompt específico |
|---|---|---|
| 1 · A | 1 s | Extreme close-up of a blue LED headlight igniting in a dark garage, slow push-in. |
| 1 · B | 1 s | Macro of a performance tire and brake disc, blue light reflecting on wet floor. |
| 1 · C | 1 s | Slow lateral slide over glossy silver bodywork, blue streak of light crossing the paint. |
| 1 · D | 1 s | Digital dashboard in a sports car, tachometer needle rising fast, blue HUD glow. |
| 2 | 4 s | Low-angle tracking shot of the silver sports coupe driving on a closed night circuit, wet asphalt, speed lines of blue light; camera whips up to the sky leaving a blue light trail. |
| 6 | 5 s | Same coupe accelerating on a closed circuit at night under blue stadium lights; crane camera rises smoothly above the track revealing an empty, dark, glossy asphalt where a logo can be placed. |

**Instrucciones de integración:** exportar 1920×1080 a 24/30 fps, color grading frío (azul/negro), y sustituir en `render_video.py` las funciones `scene1`, `scene2` (fase A) y `scene6` por la lectura del clip (por ejemplo vía `ffmpeg` → fotogramas) manteniendo los textos y tiempos.
