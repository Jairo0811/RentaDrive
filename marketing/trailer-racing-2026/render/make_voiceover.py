"""Voz en off en español (TTS Kokoro-82M, licencia Apache-2.0, voz masculina `em_alex`).
Es una VOZ SINTÉTICA DE REFERENCIA: puede sustituirse por un locutor profesional manteniendo los tiempos.

Uso: python make_voiceover.py --model kokoro.onnx --voices voices.bin
Salida: .work/vo/*.wav y .work/vo/timeline.json
"""
import argparse
import json
from pathlib import Path

import numpy as np
import soundfile as sf
from kokoro_onnx import Kokoro

# (id, texto, inicio en segundos, velocidad)
LINES = [
    ("l1", "Cada segundo cuenta.", 2.0, 0.90),
    ("l2", "Cada vehículo representa una oportunidad.", 6.5, 0.92),
    ("l3", "Cada reserva mueve tu negocio.", 9.5, 0.92),
    ("l4", "Por eso, el control debe estar a otro nivel.", 12.4, 0.92),
    ("l5", "Flota. Reservas. Clientes. Operaciones.", 20.4, 0.88),
    ("l6", "Todo conectado. Todo bajo control.", 24.6, 0.90),
    ("l7", "RentaDrive.", 38.0, 0.85),
    ("l8", "Gestiona tu flota. Impulsa tu negocio.", 39.9, 0.90),
    ("l9", "Próximamente.", 43.2, 0.85),
]

ap = argparse.ArgumentParser()
ap.add_argument("--model", required=True)
ap.add_argument("--voices", required=True)
args = ap.parse_args()

out = Path(__file__).parent / ".work/vo"
out.mkdir(parents=True, exist_ok=True)
tts = Kokoro(args.model, args.voices)
timeline = []
for key, text, start, speed in LINES:
    samples, rate = tts.create(text, voice="em_alex", speed=speed, lang="es")
    samples = np.asarray(samples, dtype=np.float32)
    sf.write(out / f"{key}.wav", samples, rate)
    timeline.append({"id": key, "text": text, "start": start, "duration": round(len(samples) / rate, 2), "rate": rate})
    print(key, f"{start:.1f}s +{len(samples) / rate:.2f}s", text)
(out / "timeline.json").write_text(json.dumps(timeline, ensure_ascii=False, indent=2), encoding="utf-8")
