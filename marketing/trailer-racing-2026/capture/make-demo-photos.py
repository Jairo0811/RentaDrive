"""Genera fotos de demostración de vehículos a partir del arte oficial de marca del repositorio
(public/images/rentadrive-racing.png). Recursos propios: no se usan fotos de terceros."""
from pathlib import Path
from PIL import Image, ImageEnhance, ImageOps

root = Path(__file__).resolve().parents[3]
src = Image.open(root / "public/images/rentadrive-racing.png").convert("RGB")
out = Path(__file__).resolve().parents[1] / "assets/demo-vehicles"
out.mkdir(parents=True, exist_ok=True)

# (caja de recorte, saturación, brillo, espejo)
variants = [
    ((755, 372, 1205, 603), 1.00, 1.00, False),
    ((760, 376, 1200, 602), 0.80, 1.12, True),
    ((750, 370, 1210, 606), 1.20, 0.90, False),
]
for index, (box, sat, bright, flip) in enumerate(variants, start=1):
    image = src.crop(box).resize((1200, 620), Image.LANCZOS)
    if flip:
        image = ImageOps.mirror(image)
    image = ImageEnhance.Color(image).enhance(sat)
    image = ImageEnhance.Brightness(image).enhance(bright)
    image.save(out / f"vehiculo-demo-{index}.jpg", quality=88)
print("ok")
