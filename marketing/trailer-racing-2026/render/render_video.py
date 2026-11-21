"""Compositor del tráiler RentaDrive (PIL + numpy → FFmpeg). Reproducible, sin marcas de agua.

Usa SOLO: logotipo y arte oficial del repositorio (public/images), capturas reales de la interfaz
(captures/*.png, datos ficticios) y gráficos procedurales (HUD, tacómetro, líneas de velocidad).
No inventa pantallas del sistema.

Uso: python render_video.py --format h|v [--preview N] [--fps 30]
  h = 1920x1080 (16:9) · v = 1080x1920 (9:16)
"""
import argparse
import math
import subprocess
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageChops, ImageDraw, ImageFilter, ImageFont

HERE = Path(__file__).resolve().parent
BASE = HERE.parent
REPO = BASE.parents[1]
WORK = HERE / ".work"
OUT = BASE / "out"

BLUE = (5, 104, 245)
ELECTRIC = (40, 150, 255)
DEEP = (6, 14, 36)
WHITE = (255, 255, 255)
SILVER = (190, 198, 210)

FONT_BOLD = "/usr/share/fonts/opentype/inter/Inter-BoldItalic.otf"
FONT_REG = "/usr/share/fonts/opentype/inter/Inter-SemiBoldItalic.otf"

ap = argparse.ArgumentParser()
ap.add_argument("--format", choices=["h", "v"], required=True)
ap.add_argument("--fps", type=int, default=30)
ap.add_argument("--preview", type=float, default=0, help="renderiza solo hasta N segundos (prueba rápida)")
ap.add_argument("--frame", type=float, default=None, help="guarda un solo fotograma en este segundo (PNG)")
ap.add_argument("--no-audio", action="store_true")
ap.add_argument("--final", action="store_true", help="video final de 8 s: solo la escena de cierre (clip D + logo)")
args = ap.parse_args()

VERT = args.format == "v"
W, H = (1080, 1920) if VERT else (1920, 1080)
FPS = args.fps
DURATION = 45.0
SAFE_L, SAFE_R = (70, W - 70)
SAFE_TOP, SAFE_BOTTOM = (260, 1500) if VERT else (60, H - 60)  # Reels/TikTok: UI arriba y abajo

rng = np.random.default_rng(7)
TITLE_Y = 345 if VERT else 1008  # título fuera del panel (vertical: dentro del área segura superior)
CHIPS_Y = 1455 if VERT else 948


# ------------------------------------------------------------------ utilidades
def load(path):
    return Image.open(path).convert("RGB")


def clamp01(x):
    return max(0.0, min(1.0, x))


def ease(x):
    x = clamp01(x)
    return x * x * (3 - 2 * x)


def ease_out(x):
    x = clamp01(x)
    return 1 - (1 - x) ** 3


def font(size, bold=True):
    return ImageFont.truetype(FONT_BOLD if bold else FONT_REG, int(size))


def cover(img, size, zoom=1.0, cx=0.5, cy=0.5):
    """Recorta 'img' para cubrir 'size' con zoom y centro normalizados (Ken Burns)."""
    tw, th = size
    iw, ih = img.size
    scale = max(tw / iw, th / ih) * zoom
    bw, bh = tw / scale, th / scale
    x0 = min(max(cx * iw - bw / 2, 0), iw - bw)
    y0 = min(max(cy * ih - bh / 2, 0), ih - bh)
    return img.resize((tw, th), Image.BILINEAR, box=(x0, y0, x0 + bw, y0 + bh))


def fit(img, size, zoom=1.0, cx=0.5, cy=0.5):
    """Ajusta 'img' completa dentro de 'size' (sin recortar) y aplica zoom/pan opcional."""
    tw, th = size
    iw, ih = img.size
    scale = min(tw / iw, th / ih) * zoom
    bw, bh = tw / scale, th / scale
    if zoom <= 1.0001:
        return img.resize((int(iw * scale), int(ih * scale)), Image.BILINEAR)
    x0 = min(max(cx * iw - bw / 2, 0), iw - bw)
    y0 = min(max(cy * ih - bh / 2, 0), ih - bh)
    return img.resize((tw, th), Image.BILINEAR, box=(x0, y0, x0 + bw, y0 + bh))


def lighten(base, layer, alpha=1.0):
    if alpha <= 0:
        return base
    if alpha < 1:
        layer = Image.eval(layer, lambda v: int(v * alpha))
    return ImageChops.screen(base, layer)


def blend(a, b, k):
    k = clamp01(k)
    if k <= 0:
        return a
    if k >= 1:
        return b
    return Image.blend(a, b, k)


def glow_text(base, text, size, center, color=WHITE, glow=BLUE, alpha=1.0, tracking=0.0, rise=0.0, bold=True):
    if alpha <= 0.01:
        return base
    f = font(size, bold)
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    widths = [d.textlength(ch, font=f) for ch in text]
    total = sum(widths) + tracking * size * (len(text) - 1)
    x = center[0] - total / 2
    y = center[1] - size / 2 + rise
    for ch, w in zip(text, widths):
        d.text((x, y), ch, font=f, fill=color + (255,))
        x += w + tracking * size
    halo = layer.filter(ImageFilter.GaussianBlur(size * 0.28))
    halo_tint = Image.new("RGBA", base.size, glow + (0,))
    halo_tint.putalpha(halo.getchannel("A").point(lambda v: min(255, int(v * 1.6 * alpha))))
    out = base.convert("RGBA")
    out = Image.alpha_composite(out, halo_tint)
    a = layer.getchannel("A").point(lambda v: int(v * alpha))
    layer.putalpha(a)
    return Image.alpha_composite(out, layer).convert("RGB")


def wrap_title(text, max_chars):
    words, lines, cur = text.split(), [], ""
    for w in words:
        if len(cur) + len(w) + 1 > max_chars and cur:
            lines.append(cur)
            cur = w
        else:
            cur = (cur + " " + w).strip()
    lines.append(cur)
    return lines


def title(base, text, t, t0, t1, y, size, fade_out=0.4, color=WHITE, tracking=0.04, impact=True, max_chars=None):
    """Texto de título con entrada rápida (sincronizada al golpe) y salida suave."""
    if t < t0 or t > t1:
        return base
    a_in = ease_out((t - t0) / 0.28)
    a_out = 1.0 - ease((t - (t1 - fade_out)) / fade_out)
    alpha = a_in * a_out
    pop = 1.0 + (0.06 * (1 - ease_out((t - t0) / 0.35)) if impact else 0)
    lines = wrap_title(text, max_chars or (25 if VERT else 46))
    # reduce el cuerpo hasta que la línea más ancha cabe en el área segura
    probe = ImageDraw.Draw(Image.new("RGB", (4, 4)))
    avail = (SAFE_R - SAFE_L) * 0.98
    fit_size = size
    for _ in range(12):
        f = font(fit_size)
        widest = max(sum(probe.textlength(ch, font=f) for ch in line) + tracking * fit_size * (len(line) - 1) for line in lines)
        if widest * 1.06 <= avail:
            break
        fit_size *= 0.93
    s = fit_size * pop
    for i, line in enumerate(lines):
        yy = y + (i - (len(lines) - 1) / 2) * s * 1.18
        base = glow_text(base, line, s, (W // 2, yy), color=color, alpha=alpha, tracking=tracking, rise=(1 - a_in) * 24)
    return base


def vignette(img, strength=0.55):
    key = ("vig", img.size, strength)
    if key not in CACHE:
        w, h = img.size
        yy, xx = np.mgrid[0:h, 0:w]
        d = np.sqrt(((xx - w / 2) / (w / 2)) ** 2 + ((yy - h / 2) / (h / 2)) ** 2)
        m = np.clip(1 - strength * (d ** 2) / 2.0, 0.25, 1)
        CACHE[key] = m[..., None].astype(np.float32)
    arr = np.asarray(img, dtype=np.float32) * CACHE[key]
    return Image.fromarray(arr.astype(np.uint8))


def grain(img, amount=6):
    n = rng.integers(-amount, amount + 1, size=(img.size[1] // 2, img.size[0] // 2, 1), dtype=np.int16)
    n = np.repeat(np.repeat(n, 2, axis=0), 2, axis=1)
    arr = np.asarray(img, dtype=np.int16) + n
    return Image.fromarray(np.clip(arr, 0, 255).astype(np.uint8))


CACHE = {}


def speed_lines_layer(seed, count=120):
    r = np.random.default_rng(seed)
    layer = Image.new("RGB", (W * 2, H), (0, 0, 0))
    d = ImageDraw.Draw(layer)
    for _ in range(count):
        y = int(r.integers(0, H))
        x = int(r.integers(0, W * 2))
        length = int(r.integers(160, 900 if not VERT else 700))
        thick = int(r.choice([1, 1, 2, 2, 3, 5]))
        shade = float(r.uniform(0.35, 1.0))
        col = tuple(int(c * shade) for c in (ELECTRIC if r.random() < 0.8 else WHITE))
        d.line([(x, y), (x + length, y)], fill=col, width=thick)
    return layer.filter(ImageFilter.GaussianBlur(1.2))


def speed_lines(base, t, intensity, speed=1800.0, seed=1):
    if intensity <= 0.01:
        return base
    key = ("sl", seed)
    if key not in CACHE:
        CACHE[key] = speed_lines_layer(seed)
    layer = CACHE[key]
    off = int((t * speed) % (W * 2))
    arr = np.roll(np.asarray(layer), -off, axis=1)[:, :W]
    # los trazos se concentran hacia los bordes (efecto túnel)
    yy = np.abs(np.linspace(-1, 1, H))[:, None]
    mask = (0.25 + 0.75 * yy ** 1.2)[..., None]
    out = np.asarray(base, dtype=np.float32) + arr.astype(np.float32) * intensity * mask
    return Image.fromarray(np.clip(out, 0, 255).astype(np.uint8))


def light_sweep(base, p, width=0.18, alpha=1.0, angle=0.35):
    """Barrido de luz diagonal; p en 0..1 recorre toda la pantalla."""
    if p <= 0 or p >= 1:
        return base
    key = ("sw", W, H)
    if key not in CACHE:
        yy, xx = np.mgrid[0:H // 4, 0:W // 4].astype(np.float32)
        CACHE[key] = (xx / (W / 4), yy / (H / 4))
    xx, yy = CACHE[key]
    coord = xx + yy * angle
    center = -0.3 + p * (1.6 + angle)
    band = np.exp(-(((coord - center) / width) ** 2))
    small = (band * 255 * alpha).astype(np.uint8)
    layer = Image.fromarray(small).resize((W, H), Image.BILINEAR)
    tint = Image.merge("RGB", [layer.point(lambda v: int(v * c / 255)) for c in (110, 190, 255)])
    return ImageChops.screen(base, tint)


def flash(base, a):
    if a <= 0:
        return base
    return ImageChops.screen(base, Image.new("RGB", base.size, tuple(int(c * a) for c in (60, 140, 255))))


def corner_brackets(base, rect, color=ELECTRIC, size=36, width=4, alpha=1.0):
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    x0, y0, x1, y1 = rect
    col = color + (int(255 * alpha),)
    for (cx, cy, sx, sy) in [(x0, y0, 1, 1), (x1, y0, -1, 1), (x0, y1, 1, -1), (x1, y1, -1, -1)]:
        d.line([(cx, cy), (cx + sx * size, cy)], fill=col, width=width)
        d.line([(cx, cy), (cx, cy + sy * size)], fill=col, width=width)
    return Image.alpha_composite(base.convert("RGBA"), layer).convert("RGB")


def rounded_mask(size, radius):
    m = Image.new("L", size, 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, size[0] - 1, size[1] - 1), radius=radius, fill=255)
    return m


# ------------------------------------------------------------------ recursos
def asset(name):
    key = ("asset", name)
    if key not in CACHE:
        CACHE[key] = load(name)
    return CACHE[key]


def capture(name):
    p = BASE / "captures" / name
    if not p.exists():
        raise SystemExit(f"Falta la captura {p}. Ejecuta el flujo de capturas (ver README).")
    return asset(p)


ART = lambda: asset(REPO / "public/images/rentadrive-racing.png")
LOGO_T = lambda: Image.open(REPO / "public/images/rentadrive-logo-dark.png").convert("RGBA")  # variante con letras blancas para fondo oscuro


def logo_rgba():
    if "logo" not in CACHE:
        im = LOGO_T()
        bbox = im.getchannel("A").point(lambda v: 255 if v > 20 else 0).getbbox()
        CACHE["logo"] = im.crop(bbox) if bbox else im
    return CACHE["logo"]


def paste_logo(base, width, center, alpha=1.0, glow_amount=1.0, logo=None):
    logo = logo if logo is not None else logo_rgba()
    h = int(logo.height * width / logo.width)
    lg = logo.resize((width, h), Image.LANCZOS)
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    layer.paste(lg, (int(center[0] - width / 2), int(center[1] - h / 2)), lg)
    halo = layer.filter(ImageFilter.GaussianBlur(width * 0.035))
    halo_c = Image.new("RGBA", base.size, BLUE + (0,))
    halo_c.putalpha(halo.getchannel("A").point(lambda v: int(min(255, v * 1.4 * glow_amount * alpha))))
    out = Image.alpha_composite(base.convert("RGBA"), halo_c)
    layer.putalpha(layer.getchannel("A").point(lambda v: int(v * alpha)))
    return Image.alpha_composite(out, layer).convert("RGB")


# ------------------------------------------------------------------ elementos HUD
def tach_gauge(base, center, radius, value, glow_a=1.0, alpha=1.0, bars=0.0):
    """Tacómetro racing. 'bars' (0..1) transforma los segmentos del arco en un gráfico de barras."""
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    cx, cy = center
    a0, a1 = math.radians(150), math.radians(390)
    ticks = 36
    heights = [0.35, 0.5, 0.42, 0.62, 0.55, 0.78, 0.7, 0.9, 0.82, 1.0, 0.88, 0.95]
    for i in range(ticks + 1):
        f = i / ticks
        ang = a0 + (a1 - a0) * f
        lit = f <= value
        major = i % 6 == 0
        col = (ELECTRIC if f < 0.75 else (255, 80, 70)) if lit else (50, 70, 110)
        r_out = radius
        r_in = radius * (0.84 if major else 0.9)
        p_out = (cx + r_out * math.cos(ang), cy + r_out * math.sin(ang))
        p_in = (cx + r_in * math.cos(ang), cy + r_in * math.sin(ang))
        if bars < 1.0:
            width = 7 if major else 4
            # el segmento viaja hacia su posición de barra
            bx = cx - radius + (2 * radius) * (i / ticks)
            bh = radius * 1.1 * heights[min(i * len(heights) // (ticks + 1), len(heights) - 1)] * (0.3 + 0.7 * (i / ticks))
            by = cy + radius * 0.55
            q_out = (bx, by - bh)
            q_in = (bx, by)
            k = ease(bars)
            po = (p_out[0] + (q_out[0] - p_out[0]) * k, p_out[1] + (q_out[1] - p_out[1]) * k)
            pi_ = (p_in[0] + (q_in[0] - p_in[0]) * k, p_in[1] + (q_in[1] - p_in[1]) * k)
            d.line([po, pi_], fill=col + (255,), width=width + int(5 * k))
    # aguja
    if bars < 0.5:
        ang = a0 + (a1 - a0) * value
        tip = (cx + radius * 0.78 * math.cos(ang), cy + radius * 0.78 * math.sin(ang))
        d.line([(cx, cy), tip], fill=(255, 255, 255, int(255 * (1 - bars * 2))), width=6)
        d.ellipse((cx - 14, cy - 14, cx + 14, cy + 14), fill=(20, 30, 60, 255), outline=ELECTRIC + (255,), width=4)
        f = font(radius * 0.42)
        label = f"{int(value * 8000)}"
        d.text((cx, cy + radius * 0.45), label, font=f, fill=(255, 255, 255, int(255 * (1 - bars * 2))), anchor="mm")
        d.text((cx, cy + radius * 0.72), "RPM", font=font(radius * 0.14, False), fill=SILVER + (int(255 * (1 - bars * 2)),), anchor="mm")
    halo = layer.filter(ImageFilter.GaussianBlur(radius * 0.05))
    halo_c = Image.new("RGBA", base.size, BLUE + (0,))
    halo_c.putalpha(halo.getchannel("A").point(lambda v: int(min(255, v * 1.2 * glow_a * alpha))))
    layer.putalpha(layer.getchannel("A").point(lambda v: int(v * alpha)))
    out = Image.alpha_composite(base.convert("RGBA"), halo_c)
    return Image.alpha_composite(out, layer).convert("RGB")


def hud_panel(base, img, rect, t_local, zoom0=1.0, zoom1=1.08, pan=(0.5, 0.5, 0.5, 0.5), alpha=1.0, label=None, focus=None, ease_fn=None):
    """Captura real dentro de un panel tipo cabina: borde azul, esquinas HUD, zoom lento."""
    x0, y0, x1, y1 = [int(v) for v in rect]
    size = (x1 - x0, y1 - y0)
    k = clamp01(t_local)
    e = (ease_fn or ease)(k)
    z = zoom0 + (zoom1 - zoom0) * e
    cx = pan[0] + (pan[2] - pan[0]) * e
    cy = pan[1] + (pan[3] - pan[1]) * e
    shot = cover(img, size, zoom=z, cx=cx, cy=cy)
    mask = rounded_mask(size, 22)
    glow = Image.new("RGBA", base.size, (0, 0, 0, 0))
    ImageDraw.Draw(glow).rounded_rectangle((x0 - 6, y0 - 6, x1 + 6, y1 + 6), radius=28, outline=ELECTRIC + (255,), width=6)
    glow_b = glow.filter(ImageFilter.GaussianBlur(14))
    out = base.convert("RGBA")
    ga = glow_b.getchannel("A").point(lambda v: int(min(255, v * 1.5 * alpha)))
    gl = Image.new("RGBA", base.size, BLUE + (0,))
    gl.putalpha(ga)
    out = Image.alpha_composite(out, gl).convert("RGB")
    m = mask.point(lambda v: int(v * alpha))
    out.paste(shot, (x0, y0), m)
    if focus:
        # corchetes HUD sobre regiones REALES de la captura (coordenadas normalizadas de la imagen)
        iw, ih = img.size
        scale = max(size[0] / iw, size[1] / ih) * z
        bw, bh = size[0] / scale, size[1] / scale
        ox = min(max(cx * iw - bw / 2, 0), iw - bw)
        oy = min(max(cy * ih - bh / 2, 0), ih - bh)
        for fx0, fy0, fx1, fy1 in focus:
            a_f = ease_out((k - 0.18) / 0.22) * alpha
            if a_f <= 0:
                continue
            rx0 = x0 + (fx0 * iw - ox) * scale - 10
            ry0 = y0 + (fy0 * ih - oy) * scale - 10
            rx1 = x0 + (fx1 * iw - ox) * scale + 10
            ry1 = y0 + (fy1 * ih - oy) * scale + 10
            rx0, ry0 = max(rx0, x0 + 6), max(ry0, y0 + 6)
            rx1, ry1 = min(rx1, x1 - 6), min(ry1, y1 - 6)
            if rx1 - rx0 > 30 and ry1 - ry0 > 20:
                out = corner_brackets(out, (rx0, ry0, rx1, ry1), color=ELECTRIC, size=22, width=3, alpha=a_f)
    border = Image.new("RGBA", base.size, (0, 0, 0, 0))
    ImageDraw.Draw(border).rounded_rectangle((x0, y0, x1, y1), radius=22, outline=ELECTRIC + (int(200 * alpha),), width=3)
    out = Image.alpha_composite(out.convert("RGBA"), border).convert("RGB")
    out = corner_brackets(out, (x0 - 18, y0 - 18, x1 + 18, y1 + 18), alpha=alpha)
    if label and alpha > 0.6 and not VERT:
        d = ImageDraw.Draw(out)
        d.text((x0 + 6, y0 - 44), label, font=font(22, False), fill=SILVER)
    return out


def chips(base, items, y, t, t0, size=26):
    """Etiquetas HUD que aparecen en secuencia (funciones reales mostradas en pantalla)."""
    f = font(size, False)
    d0 = ImageDraw.Draw(base)
    widths = [d0.textlength(s, font=f) + 46 for s in items]
    gap = 18
    total = sum(widths) + gap * (len(items) - 1)
    x = W / 2 - total / 2
    out = base.convert("RGBA")
    for i, (s, w) in enumerate(zip(items, widths)):
        a = ease_out((t - t0 - i * 0.18) / 0.3)
        if a > 0:
            layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
            d = ImageDraw.Draw(layer)
            d.rounded_rectangle((x, y - 26 + (1 - a) * 12, x + w, y + 26 + (1 - a) * 12), radius=26, fill=(6, 20, 50, int(200 * a)), outline=ELECTRIC + (int(255 * a),), width=2)
            d.text((x + w / 2, y + (1 - a) * 12), s, font=f, fill=WHITE + (int(255 * a),), anchor="mm")
            out = Image.alpha_composite(out, layer)
        x += w + gap
    return out.convert("RGB")


def dark_bg(t, zoom_speed=0.01):
    """Fondo oscuro con el arte oficial muy atenuado y desenfocado (ambiente de circuito nocturno)."""
    key = ("bg", W, H)
    if key not in CACHE:
        art = cover(ART(), (W, H), 1.0)
        art = art.filter(ImageFilter.GaussianBlur(18))
        arr = np.asarray(art, dtype=np.float32) * (0.18 if VERT else 0.28) + np.array(DEEP, dtype=np.float32) * 0.6
        CACHE[key] = Image.fromarray(np.clip(arr, 0, 255).astype(np.uint8))
    return CACHE[key].copy()


# ------------------------------------------------------------------ escenas
def car_crop(box, size, zoom=1.0, shift=(0, 0)):
    """Primer plano del auto del arte oficial (faros, rueda, carrocería) con zoom lento."""
    art = ART()
    x0, y0, x1, y1 = box
    cx, cy = (x0 + x1) / 2 + shift[0], (y0 + y1) / 2 + shift[1]
    bw, bh = (x1 - x0) / zoom, (y1 - y0) / zoom
    return art.resize(size, Image.BICUBIC, box=(cx - bw / 2, cy - bh / 2, cx + bw / 2, cy + bh / 2))


def clip_frame(name, t, fade_in=0.7, vfit=False):
    """Fotograma del clip generativo (pre-extraído por ffmpeg a 30 fps, recorte 'cover')."""
    d = WORK / f"{name}-{args.format}{'-fit' if vfit and VERT else ''}"
    if not d.exists():
        d.mkdir(parents=True)
        src = BASE / "assets" / "clips" / f"{name}.mp4"
        if vfit and VERT:  # clip oscuro: se centra sobre negro en vertical (sin recortar el instrumento)
            vf = f"fps={FPS},scale=1700:956:flags=lanczos,crop={W}:956:(iw-ow)/2:0,pad={W}:{H}:0:{(H-956)//2}:black"
        else:
            vf = f"fps={FPS},scale={W}:{H}:force_original_aspect_ratio=increase:flags=lanczos,crop={W}:{H}:" + ("'(iw-ow)*0.35':0" if VERT else "(iw-ow)/2:(ih-oh)/2")
        subprocess.run(["ffmpeg", "-y", "-loglevel", "error", "-i", str(src), "-an", "-vf", vf, "-q:v", "2", str(d / "%04d.jpg")], check=True)
    n = min(int(t * FPS) + 1, len(list(d.glob("*.jpg"))))
    img = Image.open(d / f"{n:04d}.jpg").convert("RGB")
    k = clamp01(t / fade_in)
    return img if k >= 1 else ImageEnhance_brightness(img, ease(k))


CUT1 = 3.2  # corte del tacómetro al clip A (destello + golpe de audio)


def scene1(t):
    """0–6 s: Clip E del tacómetro que sube de RPM (0–3,2 s, a 1,6x) → destello → clip A:
    auto genérico, faros LED azules encienden (assets/clips/clipA-garaje.mp4, audio descartado) → título."""
    if t < CUT1:
        # clip E (tacómetro 800→6000 RPM, generado en Google Flow, audio descartado) a 1,6x
        frame = clip_frame("clipE-tacometro", t * 1.6, fade_in=0.5, vfit=True)
        frame = vignette(frame, 0.5)
        return flash(frame, 0.5 * max(0, 1 - abs(t - 3.0) / 0.1))
    frame = clip_frame("clipA-garaje", 1.6 + (t - CUT1), fade_in=0.01)
    frame = vignette(frame, 0.55)
    frame = flash(frame, 0.9 * max(0, 1 - (t - CUT1) / 0.25))
    frame = title(frame, "UNA NUEVA ERA DE GESTIÓN AUTOMOTRIZ", t, 4.3, 6.0, H * (0.88 if not VERT else 0.2), 60, fade_out=0.15)
    return frame


def scene1_tacometro(t, with_title=True):
    """0–6 s: El despertar. Negro → arranque → tacómetro que despierta → destello azul → texto.
    Sin planos de auto: el auto real queda pendiente de un clip generativo/filmado (ver docs)."""
    frame = Image.new("RGB", (W, H), (0, 0, 0))
    cy = int(H * (0.6 if not VERT else 0.5))
    radius = int(min(W, H) * 0.33)
    if t >= 0.8:
        wake = ease_out((t - 0.8) / 1.2)
        if t < 3.9:
            jitter = 0.012 * math.sin(t * 38) + 0.008 * math.sin(t * 61)
            value = 0.07 + 0.03 * ease((t - 1.6) / 1.2) + jitter
        elif t < 5.0:
            value = 0.1 + 0.82 * ease((t - 3.9) / 1.1)
        else:
            value = 0.95
        base = Image.new("RGB", (W, H), (2, 6, 16))
        frame = tach_gauge(base, (W // 2, cy), radius, max(0.02, value), alpha=wake * (1.0 if t < 5.0 else 1 - 0.4 * ease(t - 5.0)))
        # barra LED horizontal que se enciende desde el centro (arranque)
        led = ease_out((t - 0.8) / 0.9)
        d = ImageDraw.Draw(frame)
        half = int(W * 0.42 * led)
        d.line([(W // 2 - half, cy + radius + 60), (W // 2 + half, cy + radius + 60)], fill=ELECTRIC, width=3)
    if t >= 3.9:
        frame = speed_lines(frame, t, min(1.0, 0.25 + 0.75 * ease((t - 3.9) / 1.6)), speed=2200 + 1200 * ease((t - 3.9) / 2.0), seed=3)
    frame = flash(frame, 0.55 * max(0, 1 - abs(t - 0.85) / 0.12) + 0.9 * max(0, 1 - abs(t - 3.9) / 0.1))
    frame = light_sweep(frame, (t - 3.6) / 0.7)
    frame = light_sweep(frame, (t - 4.6) / 0.9, width=0.22, alpha=1.0, angle=0.0)
    frame = vignette(frame, 0.8)
    if with_title:
        frame = title(frame, "UNA NUEVA ERA DE GESTIÓN AUTOMOTRIZ", t, 4.1, 6.0, H * (0.15 if not VERT else 0.2), 60, fade_out=0.15)
    return frame


def ImageEnhance_brightness(img, f):
    arr = np.asarray(img, dtype=np.float32) * f
    return Image.fromarray(np.clip(arr, 0, 255).astype(np.uint8))


def scene2(t):
    """6–12 s: Identidad. Clip C (auto en el circuito) → logotipo auténtico."""
    local = t - 6.0
    # fase A (6–9.4): clip C del auto en el circuito
    if local < 3.4:
        # clip generativo C (auto genérico en circuito mojado, travelling lateral); audio descartado
        frame = clip_frame("clipC-circuito", local, fade_in=0.01)
        frame = speed_lines(frame, t, 0.25, speed=3000, seed=5)
        frame = light_sweep(frame, (t - 6.0) / 0.8, alpha=0.7)
        frame = vignette(frame, 0.6)
        frame = flash(frame, 0.6 * max(0, 1 - (t - 6.0) / 0.25))
        out_to_black = ease((local - 2.6) / 0.8)
        frame = blend(frame, Image.new("RGB", (W, H), (0, 0, 0)), out_to_black)
    else:
        # fase B: logotipo auténtico sobre negro / azul eléctrico
        k = (local - 3.4) / 2.6
        bg = Image.new("RGB", (W, H), (0, 0, 0))
        glow = dark_bg(t)
        frame = blend(bg, glow, 0.5 * ease(k * 2))
        frame = speed_lines(frame, t, 0.35 * (1 - ease(k)), speed=1500, seed=6)
        lw = int((W * 0.36 if not VERT else W * 0.86) * (0.94 + 0.06 * ease(k)))
        frame = paste_logo(frame, lw, (W // 2, int(H * (0.42 if not VERT else 0.40))), alpha=ease_out(k * 2.5), glow_amount=0.6, logo=futurist_logo())
        frame = light_sweep(frame, (local - 3.5) / 0.9, alpha=0.6)
        frame = flash(frame, 0.5 * max(0, 1 - (local - 3.4) / 0.2))
        frame = title(frame, "TECNOLOGÍA QUE IMPULSA TU NEGOCIO", t, 10.2, 12.0, H * (0.82 if not VERT else 0.64), 38 if not VERT else 50, fade_out=0.15, tracking=0.06)
    return frame


def split_scene(t, t0, t1, shots, title_text, chip_items=None, title_y=None):
    """Escena con capturas reales en paneles HUD; 'shots' = [(archivo, inicio_rel, fin_rel, label, pan)]."""
    local = t - t0
    frame = dark_bg(t)
    frame = speed_lines(frame, t, 0.18, speed=900, seed=11)
    present = [s for s in shots if s[1] <= local < s[2] + 0.35]
    for name, a, b, label, pan in present:
        k = (local - a) / (b - a)
        fade_in = ease_out((local - a) / 0.28)
        fade_out_ = 1 - ease((local - b) / 0.35) if local >= b else 1.0
        alpha = fade_in * fade_out_
        if VERT:
            pw, ph = 523, 930
            px, py = (W - pw) // 2, 470
        else:
            pw, ph = 1440, 810
            px, py = (W - pw) // 2, 62
        rect = (px, py, px + pw, py + ph)
        frame = hud_panel(frame, capture(name), rect, k, 1.0, 1.07 if VERT else 1.1, pan, alpha, label)
        # barrido de luz en cada corte
        frame = light_sweep(frame, (local - a) / 0.45, width=0.12, alpha=0.7)
    frame = title(frame, title_text, t, t0 + 0.05, t1, TITLE_Y, 54 if not VERT else 60, fade_out=0.3)
    if chip_items:
        frame = chips(frame, chip_items, (CHIPS_Y), t, t0 + 0.8, 24)
    return frame


def motion_blur_h(img, px):
    """Desenfoque de movimiento horizontal moderado (promedio de copias desplazadas)."""
    if px < 1:
        return img
    arr = np.asarray(img, dtype=np.float32)
    acc = np.zeros_like(arr)
    n = 5
    for i in range(n):
        acc += np.roll(arr, int((i - n // 2) * px / (n // 2 or 1)), axis=1)
    return Image.fromarray(np.clip(acc / n, 0, 255).astype(np.uint8))


def hud_rails(frame, t, rect):
    """Líneas HUD discretas (reglas con marcas que avanzan) fuera del panel; no tapan la interfaz."""
    x0, y0, x1, y1 = rect
    layer = Image.new("RGBA", frame.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for y, direction in ((y0 - 34, 1), (y1 + 30, -1)):
        d.line([(x0, y), (x1, y)], fill=ELECTRIC + (70,), width=1)
        off = (t * 90 * direction) % 40
        for i in range(-1, int((x1 - x0) / 40) + 2):
            x = x0 + i * 40 + off
            if x0 <= x <= x1:
                d.line([(x, y - (6 if i % 4 == 0 else 3)), (x, y + (6 if i % 4 == 0 else 3))], fill=ELECTRIC + (110,), width=1)
    return Image.alpha_composite(frame.convert("RGBA"), layer).convert("RGB")


def panel_rect(detail=False):
    if not VERT:
        return (W - 1440) // 2, 62, (W - 1440) // 2 + 1440, 62 + 810
    if detail:
        return 60, 450, 1020, 1380  # plano de detalle: 960x930, dentro de la zona segura Reels/TikTok
    return (W - 574) // 2, 410, (W - 574) // 2 + 574, 410 + 1020  # plano general


def shots_scene(t, t0, t1, shots, title_text, chip_items=None, entry=None):
    """Escena con capturas reales en paneles HUD. Cada plano: dict(name, a, b, label, z0, z1, pan, focus, detail)."""
    local = t - t0
    frame = dark_bg(t)
    frame = speed_lines(frame, t, 0.16, speed=900, seed=11)
    for sh in shots:
        a, b = sh["a"], sh["b"]
        if not (a <= local < b + 0.35):
            continue
        k = (local - a) / (b - a)
        alpha = ease_out((local - a) / 0.26) * (1 - ease((local - b) / 0.3) if local >= b else 1.0)
        rect = panel_rect(sh.get("detail", False))
        pan = sh.get("pan", (0.5, 0.5, 0.5, 0.5))
        frame = hud_rails(frame, t, rect)
        frame = hud_panel(frame, capture(sh["name"]), rect, k, sh.get("z0", 1.0), sh.get("z1", 1.1), pan, alpha, sh.get("label"), focus=sh.get("focus"), ease_fn=ease)
        frame = light_sweep(frame, (local - a) / 0.42, width=0.1, alpha=0.6)
    if entry:
        frame = entry(frame, local)
    frame = title(frame, title_text, t, t0 + 0.05, t1, TITLE_Y, 54 if not VERT else 60, fade_out=0.3)
    if chip_items:
        frame = chips(frame, chip_items, CHIPS_Y, t, t0 + 0.8, 24)
    return frame


def car_entry(frame, local):
    """Transición por movimiento: el auto de competición pasa a toda velocidad y deja paso a la interfaz."""
    if local > 0.85:
        return frame
    k = local / 0.85
    car = car_crop((700, 330, 1260, 640), (W, H), zoom=1.0 + 0.25 * k, shift=(-60 * k, 0))
    car = ImageEnhance_brightness(car, 0.85)
    car = motion_blur_h(car, 4 + 34 * k)
    car = vignette(car, 0.5)
    a = 1 - ease(k * 1.25)
    out = blend(frame, car, a)
    return light_sweep(out, k, width=0.14, alpha=0.9, angle=0.0)


def scene3(t):
    """12–20 s: Flota. Transición desde el auto, travelling sobre tarjetas, acercamiento a foto, tarifa y estado, tabla y ficha."""
    if not VERT:
        shots = [
            dict(name="h-flota-tarjetas.png", a=0.0, b=3.0, label="FLOTA · TARJETAS", z0=1.5, z1=1.5, pan=(0.30, 0.55, 0.78, 0.55)),
            dict(name="h-flota-tarjetas.png", a=3.0, b=4.0, label="FOTO DEL VEHÍCULO", z0=2.4, z1=2.6, pan=(0.57, 0.46, 0.57, 0.46)),
            dict(name="h-flota-tarjetas.png", a=4.0, b=5.0, label="TARIFA Y ESTADO", z0=2.6, z1=2.6, pan=(0.57, 0.52, 0.57, 0.65), focus=[(0.575, 0.665, 0.64, 0.71), (0.652, 0.614, 0.695, 0.64)]),
            dict(name="h-flota-tabla.png", a=5.0, b=6.5, label="FLOTA · LISTADO", z0=1.0, z1=1.4, pan=(0.5, 0.32, 0.62, 0.36), focus=[(0.17, 0.40, 0.97, 0.57)]),
            dict(name="h-vehiculo-detalle.png", a=6.5, b=8.0, label="FICHA DEL VEHÍCULO", z0=1.0, z1=1.55, pan=(0.30, 0.38, 0.30, 0.62), focus=[(0.185, 0.19, 0.46, 0.47)]),
        ]
        chips_ = ["CATEGORÍAS", "ESTADOS", "TARIFAS", "KILOMETRAJE"]
    else:
        shots = [
            dict(name="v-flota-tarjetas.png", a=0.0, b=2.0, label="FLOTA", z0=1.0, z1=1.08, pan=(0.5, 0.45, 0.5, 0.6)),
            dict(name="v-flota-tarjetas.png", a=2.0, b=4.0, detail=True, z0=1.0, z1=1.12, pan=(0.5, 0.75, 0.5, 0.80)),
            dict(name="v-vehiculo-detalle.png", a=4.0, b=6.0, detail=True, z0=1.0, z1=1.0, pan=(0.5, 0.30, 0.5, 0.78)),
            dict(name="v-flota-tabla.png", a=6.0, b=8.0, z0=1.0, z1=1.08, pan=(0.5, 0.40, 0.5, 0.55)),
        ]
        chips_ = ["ESTADOS", "TARIFAS", "KILOMETRAJE"]
    return shots_scene(t, 12.0, 20.0, shots, "CONTROL TOTAL DE TU FLOTA", chips_, entry=car_entry)


def scene4(t):
    """20–28 s: Reservas y operaciones con los pasos REALES del selector inteligente y la tarifa autocompletada."""
    if not VERT:
        shots = [
            dict(name="h-reservas.png", a=0.0, b=1.5, label="RESERVAS", z0=1.0, z1=1.25, pan=(0.5, 0.30, 0.5, 0.40)),
            dict(name="h-form-1-cliente-buscando.png", a=1.5, b=2.5, label="BUSCA AL CLIENTE", z0=2.2, z1=2.3, pan=(0.34, 0.30, 0.34, 0.30), focus=[(0.185, 0.205, 0.45, 0.34)]),
            dict(name="h-form-2-cliente.png", a=2.5, b=3.5, label="CLIENTE SELECCIONADO", z0=2.3, z1=2.2, pan=(0.34, 0.30, 0.40, 0.30)),
            dict(name="h-form-3-vehiculo-buscando.png", a=3.5, b=4.5, label="ELIGE LA UNIDAD", z0=1.8, z1=1.9, pan=(0.72, 0.36, 0.72, 0.36), focus=[(0.71, 0.20, 0.965, 0.33)]),
            dict(name="h-form-4-vehiculo.png", a=4.5, b=6.0, label="TARIFA AUTOCOMPLETADA", z0=1.8, z1=1.7, pan=(0.72, 0.38, 0.72, 0.40), focus=[(0.71, 0.20, 0.97, 0.28), (0.71, 0.30, 0.97, 0.37)]),
            dict(name="h-alquileres.png", a=6.0, b=7.0, label="ALQUILERES", z0=1.0, z1=1.3, pan=(0.5, 0.30, 0.55, 0.34)),
            dict(name="h-inspecciones.png", a=7.0, b=8.0, label="INSPECCIONES", z0=1.0, z1=1.3, pan=(0.5, 0.30, 0.45, 0.34)),
        ]
        chips_ = ["RESERVAS", "CLIENTES", "ALQUILERES", "INSPECCIONES"]
    else:
        shots = [
            dict(name="v-reservas.png", a=0.0, b=1.5, z0=1.0, z1=1.08, pan=(0.5, 0.35, 0.5, 0.5)),
            dict(name="v-form-1-cliente-buscando.png", a=1.5, b=3.5, detail=True, z0=1.0, z1=1.1, pan=(0.5, 0.17, 0.5, 0.17)),
            dict(name="v-form-3-vehiculo-buscando.png", a=3.5, b=5.0, detail=True, z0=1.0, z1=1.1, pan=(0.5, 0.17, 0.5, 0.17)),
            dict(name="v-form-4-vehiculo.png", a=5.0, b=6.5, detail=True, z0=1.0, z1=1.1, pan=(0.5, 0.22, 0.5, 0.30), focus=[(0.05, 0.27, 0.95, 0.37)]),
            dict(name="v-reservas.png", a=6.5, b=8.0, z0=1.0, z1=1.06, pan=(0.5, 0.5, 0.5, 0.6)),
        ]
        chips_ = ["RESERVAS", "ALQUILERES", "INSPECCIONES"]
    return shots_scene(t, 20.0, 28.0, shots, "CADA RESERVA. CADA VEHÍCULO. TODO CONECTADO", chips_)


def scene5(t):
    """28–37 s: Precisión. Tacómetro → barras → indicadores del dashboard real, gráficos, facturación y pagos manuales."""
    local = t - 28.0
    if local < 2.6:
        base = Image.new("RGB", (W, H), (2, 6, 16))
        value = 0.15 + 0.78 * ease(local / 1.6)
        morph = ease((local - 1.5) / 1.0) if local > 1.5 else 0.0
        frame = tach_gauge(base, (W // 2, int(H * (0.5 if not VERT else 0.42))), int(min(W, H) * 0.3), value, bars=morph)
        frame = speed_lines(frame, t, 0.5 * (1 - morph), speed=2500, seed=13)
        frame = flash(frame, 0.7 * max(0, 1 - local / 0.2))
        frame = light_sweep(frame, (local - 2.0) / 0.6, alpha=0.8)
        frame = title(frame, "PRECISIÓN PARA CADA DECISIÓN", t, 28.1, 37.0, TITLE_Y, 54 if not VERT else 60, fade_out=0.3)
        return frame
    if not VERT:
        shots = [
            dict(name="h-dashboard.png", a=2.5, b=4.5, label="INDICADORES", z0=1.0, z1=1.7, pan=(0.5, 0.30, 0.40, 0.42), focus=[(0.175, 0.17, 0.975, 0.66)]),
            dict(name="h-dashboard-graficos.png", a=4.5, b=5.5, label="GRÁFICOS", z0=1.0, z1=1.35, pan=(0.5, 0.45, 0.5, 0.58)),
            dict(name="h-reportes.png", a=5.5, b=6.5, label="REPORTES", z0=1.0, z1=1.5, pan=(0.5, 0.35, 0.45, 0.42), focus=[(0.18, 0.28, 0.98, 0.41)]),
            dict(name="h-facturas.png", a=6.5, b=7.5, label="FACTURACIÓN INTERNA", z0=1.0, z1=1.6, pan=(0.40, 0.32, 0.55, 0.34), focus=[(0.175, 0.30, 0.97, 0.37)]),
            dict(name="h-pagos.png", a=7.5, b=9.0, label="PAGOS MANUALES", z0=1.0, z1=1.6, pan=(0.45, 0.32, 0.60, 0.34), focus=[(0.58, 0.31, 0.97, 0.38)]),
        ]
        chips_ = ["INDICADORES", "REPORTES", "FACTURACIÓN INTERNA", "PAGOS MANUALES"]
    else:
        shots = [
            dict(name="v-dashboard.png", a=2.5, b=4.5, detail=True, z0=1.0, z1=1.1, pan=(0.5, 0.25, 0.5, 0.30)),
            dict(name="v-dashboard-graficos.png", a=4.5, b=6.0, detail=True, z0=1.0, z1=1.1, pan=(0.5, 0.45, 0.5, 0.5)),
            dict(name="v-reportes.png", a=6.0, b=7.5, detail=True, z0=1.0, z1=1.1, pan=(0.5, 0.30, 0.5, 0.40)),
            dict(name="v-facturas.png", a=7.5, b=9.0, detail=True, z0=1.0, z1=1.1, pan=(0.5, 0.30, 0.5, 0.36)),
        ]
        chips_ = ["INDICADORES", "FACTURACIÓN INTERNA"]
    return shots_scene(t, 28.0, 37.0, shots, "PRECISIÓN PARA CADA DECISIÓN", chips_)


def futurist_logo():
    """Logotipo oficial futurista (PNG transparente, trae el lema integrado): assets/brand/."""
    if "flogo" not in CACHE:
        im = Image.open(BASE / "assets" / "brand" / "logo-futurista-con-slogan.png").convert("RGBA")
        bbox = im.getchannel("A").point(lambda v: 255 if v > 20 else 0).getbbox()
        CACHE["flogo"] = im.crop(bbox) if bbox else im
    return CACHE["flogo"]


def scene6(t):
    """37–45 s: Cierre. Clip D (el auto se aleja por el circuito) → logotipo oficial con lema → estado comercial."""
    local = t - 37.0
    frame = clip_frame("clipD-cierre", local, fade_in=0.01)
    frame = vignette(frame, 0.6)
    frame = flash(frame, 0.7 * max(0, 1 - local / 0.3))
    k = clamp01((local - 3.6) / 1.2)
    if k > 0:
        lw = int((W * 0.40 if not VERT else W * 0.86) * (0.95 + 0.05 * ease(k)))
        logo = futurist_logo()
        frame = paste_logo(frame, lw, (W // 2, int(H * (0.31 if not VERT else 0.34))), alpha=ease_out(k), glow_amount=0.6, logo=logo)
        frame = flash(frame, 0.35 * max(0, 1 - abs(local - 3.7) / 0.12))
        frame = light_sweep(frame, (local - 3.8) / 0.9, alpha=0.5)
    req_y = H * 0.93 if not VERT else H * 0.56
    frame = title(frame, "EN DESARROLLO COMERCIAL · PRÓXIMAMENTE", t, 41.6, 45.0, req_y, 28 if not VERT else 34, fade_out=0.8, color=SILVER, tracking=0.08, impact=False, max_chars=60)
    out = ease((local - 7.3) / 0.7)
    return blend(frame, Image.new("RGB", (W, H), (0, 0, 0)), out)


SCENES = [(0.0, 6.0, scene1), (6.0, 12.0, scene2), (12.0, 20.0, scene3), (20.0, 28.0, scene4), (28.0, 37.0, scene5), (37.0, 45.0, scene6)]


def frame_at(t):
    for a, b, fn in SCENES:
        if a <= t < b + 1e-9:
            img = fn(t)
            # transición con barrido en las uniones entre escenas
            return grain(img.convert("RGB"), 5)
    return Image.new("RGB", (W, H), (0, 0, 0))


def main():
    OUT.mkdir(exist_ok=True)
    WORK.mkdir(exist_ok=True)
    if args.frame is not None:
        frame_at(args.frame).save(WORK / f"frame-{args.format}-{args.frame:.2f}.png")
        print("frame guardado")
        return
    T0 = 37.0 if args.final else 0.0
    total = int((8.0 if args.final else (args.preview or DURATION)) * FPS)
    kind = "final" if args.final else "trailer"
    name = f"rentadrive-{kind}-16x9-1920x1080.mp4" if not VERT else f"rentadrive-{kind}-9x16-1080x1920.mp4"
    target = OUT / name if not args.preview else WORK / f"preview-{args.format}.mp4"
    cmd = ["ffmpeg", "-y", "-hide_banner", "-loglevel", "error", "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{W}x{H}", "-r", str(FPS), "-i", "-"]
    audio = WORK / "audio.wav"
    if audio.exists() and not args.no_audio:
        cmd += (["-ss", "37", "-t", "8"] if args.final else []) + ["-i", str(audio), "-af", "loudnorm=I=-15:TP=-2:LRA=11,alimiter=limit=0.8:level=disabled", "-c:a", "aac", "-b:a", "192k", "-ar", "48000", "-shortest"]
    cmd += ["-c:v", "libx264", "-preset", "slow", "-crf", "22", "-pix_fmt", "yuv420p", "-profile:v", "high", "-movflags", "+faststart", "-r", str(FPS), str(target)]
    proc = subprocess.Popen(cmd, stdin=subprocess.PIPE)
    for n in range(total):
        proc.stdin.write(frame_at(T0 + n / FPS).tobytes())
        if n % FPS == 0:
            print(f"\r{n / FPS:5.1f}s / {total / FPS:.0f}s", end="", flush=True, file=sys.stderr)
    proc.stdin.close()
    proc.wait()
    print(f"\nlisto: {target}")


if __name__ == "__main__":
    main()
