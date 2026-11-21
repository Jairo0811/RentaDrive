# RentaDrive — Official Racing Trailer 2026

Materiales y proyecto de edición **reproducible** del tráiler de 45 s de RentaDrive. Separado del código operativo (la aplicación no depende de esta carpeta).

> RentaDrive está **en desarrollo comercial**; no está disponible como SaaS de producción. El tráiler lo dice en pantalla.

## Contenido
| Ruta | Qué es |
|---|---|
| `docs/` | Guion técnico, storyboard, voz en off, lista de recursos y prompts generativos |
| `capture/` | Playwright: prepara datos ficticios y captura pantallas reales (16:9 y 9:16) |
| `assets/demo-vehicles/` | Fotos de demostración derivadas del arte oficial de la marca |
| `captures/` | PNG de la interfaz real (resultado del workflow `Marketing captures`) |
| `render/` | `make_voiceover.py`, `build_audio.py`, `render_video.py` |
| `out/` | MP4 finales (ignorados por git; ver «Distribución») |

## Seguridad de las capturas
- Solo contra una base **descartable** (`RentaDriveE2E`) del servicio SQL Server del runner de GitHub Actions. **No se toca `RentaDriveDb`.** No se usa `migrate:fresh`, `db:wipe` ni seeders de demostración sobre instalaciones existentes.
- La contraseña del administrador ficticio se genera al azar en cada ejecución y se enmascara en los logs; las capturas no muestran contraseñas, tokens ni datos reales.
- Si quieres capturar localmente, hazlo contra una base nueva y aparte, nunca contra la base de trabajo.

## Cómo reproducirlo
1. **Capturas:** GitHub → Actions → *Marketing captures* → Run workflow (o abre un PR que toque `marketing/**`). Descarga el artefacto `trailer-captures` y copia los PNG a `captures/`.
2. **Dependencias de render** (Python 3.11+): `pip install pillow numpy scipy soundfile kokoro-onnx` y `ffmpeg` con `libx264`. Fuente: Inter (OFL) en `/usr/share/fonts/opentype/inter/` (ajusta `FONT_BOLD/FONT_REG` si está en otra ruta).
3. **Voz de referencia (opcional):** descarga `kokoro-v1.0.onnx` y `voices-v1.0.bin` de https://github.com/thewh1teagle/kokoro-onnx/releases/tag/model-files-v1.0 y ejecuta  
   `python render/make_voiceover.py --model kokoro-v1.0.onnx --voices voices-v1.0.bin`
4. **Audio:** `python render/build_audio.py` → `render/.work/audio.wav`.
5. **Video:**  
   `python render/render_video.py --format h` → `out/rentadrive-trailer-16x9-1920x1080.mp4`  
   `python render/render_video.py --format v` → `out/rentadrive-trailer-9x16-1080x1920.mp4`  
   Pruebas rápidas: `--preview 8` (primeros 8 s) o `--frame 11.0` (un fotograma PNG en `render/.work/`).

Salida: MP4 H.264 (High, yuv420p, CRF 17), AAC 192 kb/s a 48 kHz, 30 fps, `+faststart`, sin marcas de agua. El audio se normaliza a ≈ −14 LUFS (`loudnorm`).

## Estado honesto
- **Hecho y renderizado:** ambas versiones con capturas reales, arte y logotipo oficiales, música/efectos originales y voz sintética de referencia.
- **Pendiente (herramientas externas):** clips generativos o filmados de autos (ver `docs/05-prompts-generativos.md`), locutor profesional y masterización de sonido. Los planos de autos del render son recortes y animaciones del arte oficial, no video de autos real.

## Distribución
Los MP4 no se suben al repositorio de código. Opciones: GitHub Release (adjunto), almacenamiento del equipo o Git LFS si se decide versionarlos.
