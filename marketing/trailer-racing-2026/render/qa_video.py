"""Verificación automática de los MP4 de la versión 1.1 (ffprobe + decodificación + negros + loudness + hoja de contacto).
Uso: python qa_video.py   (lee out/*.mp4 v1.1; escribe out/qa-v1.1.json y out/hoja-contacto-*.jpg)"""
import json
import re
import subprocess
from pathlib import Path

from PIL import Image, ImageDraw

OUT = Path(__file__).resolve().parent.parent / "out"
SPEC = {
    "rentadrive-trailer-v1.1-16x9-1920x1080.mp4": (1920, 1080, 45.0),
    "rentadrive-trailer-v1.1-9x16-1080x1920.mp4": (1080, 1920, 45.0),
    "rentadrive-final-v1.1-16x9.mp4": (1920, 1080, 8.0),
    "rentadrive-final-v1.1-9x16.mp4": (1080, 1920, 8.0),
}


def run(cmd):
    return subprocess.run(cmd, capture_output=True, text=True)


report = {}
for name, (w, h, dur) in SPEC.items():
    f = OUT / name
    r = {"existe": f.exists()}
    if not f.exists():
        report[name] = r
        continue
    pr = json.loads(run(["ffprobe", "-v", "error", "-show_streams", "-show_format", "-of", "json", str(f)]).stdout)
    v = next(s for s in pr["streams"] if s["codec_type"] == "video")
    a = next(s for s in pr["streams"] if s["codec_type"] == "audio")
    num, den = map(int, v["r_frame_rate"].split("/"))
    r.update(
        resolucion=f'{v["width"]}x{v["height"]}', resolucion_ok=(v["width"], v["height"]) == (w, h),
        codec_video=v["codec_name"], fps=num / den, fps_ok=abs(num / den - 30) < 0.01,
        codec_audio=a["codec_name"], audio_hz=int(a["sample_rate"]), audio_canales=a["channels"],
        audio_ok=a["codec_name"] == "aac" and int(a["sample_rate"]) == 48000 and a["channels"] == 2,
        duracion=float(pr["format"]["duration"]), duracion_ok=abs(float(pr["format"]["duration"]) - dur) < 0.1,
        tamano_mb=round(f.stat().st_size / 1e6, 1),
    )
    dec = run(["ffmpeg", "-v", "error", "-i", str(f), "-f", "null", "-"])
    r["decodificacion_sin_errores"] = dec.stderr.strip() == ""
    bd = run(["ffmpeg", "-i", str(f), "-vf", "blackdetect=d=0.12:pix_th=0.04", "-an", "-f", "null", "-"]).stderr
    r["segmentos_negros"] = re.findall(r"black_start:([\d.]+) black_end:([\d.]+)", bd)
    lo = run(["ffmpeg", "-i", str(f), "-af", "ebur128=peak=true", "-vn", "-f", "null", "-"]).stderr
    integ = re.findall(r"I:\s+(-?[\d.]+) LUFS", lo)
    peak = re.findall(r"Peak:\s+(-?[\d.]+) dBFS", lo)
    r["loudness_integrado_lufs"] = float(integ[-1]) if integ else None
    r["pico_verdadero_dbfs"] = float(peak[-1]) if peak else None
    report[name] = r
    # hoja de contacto
    count = 10 if dur > 20 else 4
    times = [dur * (i + 0.5) / count for i in range(count)]
    tw, th = (384, 216) if w > h else (216, 384)
    cols = 5 if w > h else 10
    rows = (count + cols - 1) // cols
    sheet = Image.new("RGB", (tw * cols, th * rows))
    tmp = OUT / ".contact"
    tmp.mkdir(exist_ok=True)
    for i, t in enumerate(times):
        p = tmp / f"{i}.png"
        run(["ffmpeg", "-y", "-v", "error", "-ss", f"{t:.2f}", "-i", str(f), "-frames:v", "1", "-vf", f"scale={tw}:{th}", str(p)])
        im = Image.open(p)
        ImageDraw.Draw(im).text((6, 4), f"{t:.1f}s", fill=(255, 255, 255))
        sheet.paste(im, ((i % cols) * tw, (i // cols) * th))
    sheet.save(OUT / f"hoja-contacto-{name.replace('.mp4', '')}.jpg", quality=85)

(OUT / "qa-v1.1.json").write_text(json.dumps(report, indent=2, ensure_ascii=False))
for n, r in report.items():
    print(n, {k: v for k, v in r.items() if k in ("resolucion_ok", "fps_ok", "audio_ok", "duracion", "duracion_ok", "decodificacion_sin_errores", "segmentos_negros", "loudness_integrado_lufs", "pico_verdadero_dbfs")})
