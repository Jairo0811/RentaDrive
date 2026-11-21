"""Banda sonora ORIGINAL sintetizada con numpy (sin muestras ni música de terceros).
Motor, cambios de marcha, bajo, percusión, arpegio, risers e impactos sincronizados con la línea de tiempo.
Mezcla la voz en off si existe .work/vo/timeline.json. Salida: .work/audio.wav (44,1 kHz estéreo).
"""
import json
from pathlib import Path

import numpy as np
import soundfile as sf
from scipy.signal import butter, sosfilt, sosfilt_zi

SR = 44100
DUR = 45.0
N = int(SR * DUR)
BPM = 120
BEAT = 60 / BPM
rng = np.random.default_rng(2026)
work = Path(__file__).parent / ".work"
work.mkdir(exist_ok=True)


def t_of(n):
    return np.arange(n) / SR


def env_ad(n, attack, decay):
    t = t_of(n)
    return np.minimum(t / max(attack, 1e-4), 1.0) * np.exp(-t / decay)


def add(mix, start, sig, gain=1.0, pan=0.0):
    i = int(start * SR)
    if i >= N or i < 0:
        return
    seg = sig[: N - i]
    left, right = np.cos((pan + 1) * np.pi / 4), np.sin((pan + 1) * np.pi / 4)
    mix[0, i : i + len(seg)] += seg * gain * left
    mix[1, i : i + len(seg)] += seg * gain * right


def lowpass(x, fc, order=2):
    return sosfilt(butter(order, fc, "low", fs=SR, output="sos"), x)


def highpass(x, fc, order=2):
    return sosfilt(butter(order, fc, "high", fs=SR, output="sos"), x)


def swept_noise(duration, f0, f1, q_bw=0.6):
    """Ruido filtrado paso banda con frecuencia central que barre de f0 a f1 (whoosh / riser)."""
    n = int(duration * SR)
    noise = rng.standard_normal(n)
    out = np.zeros(n)
    chunk = 1024
    zi = None
    for k in range(0, n, chunk):
        frac = k / max(n - 1, 1)
        fc = f0 * (f1 / f0) ** frac
        lo, hi = max(fc * (1 - q_bw / 2), 40), min(fc * (1 + q_bw / 2), SR / 2 - 100)
        sos = butter(2, [lo, hi], "band", fs=SR, output="sos")
        if zi is None:
            zi = sosfilt_zi(sos) * 0
        seg, zi = sosfilt(sos, noise[k : k + chunk], zi=zi)
        out[k : k + chunk] = seg
    return out / (np.max(np.abs(out)) + 1e-9)


# ---------------------------------------------------------------- motor
def rpm_profile(t):
    """RPM del motor deportivo a lo largo del tráiler (arranque, aceleraciones, cierre)."""
    pts = [
        (0.0, 0), (0.6, 260), (1.3, 760), (1.8, 1100), (3.8, 1000),
        (4.4, 3600), (5.0, 6200), (5.4, 6400), (5.7, 2600), (6.0, 1500), (8.0, 1300),
        (11.5, 1500), (12.0, 1000), (36.0, 1000), (37.0, 1800), (38.0, 5200),
        (38.7, 3500), (39.4, 6600), (40.2, 4200), (41.0, 7000), (42.0, 5200),
        (43.0, 7200), (43.5, 3000), (44.2, 1200), (45.0, 0),
    ]
    xs, ys = zip(*pts)
    return np.interp(t, xs, ys)


def engine():
    t = t_of(N)
    rpm = rpm_profile(t)
    fire = rpm / 60.0 * 4  # V8: 4 explosiones por revolución
    phase = 2 * np.pi * np.cumsum(fire) / SR
    sig = np.zeros(N)
    for k, a in enumerate([1.0, 0.7, 0.55, 0.4, 0.3, 0.22, 0.16, 0.12, 0.09, 0.07], start=1):
        sig += a * np.sin(k * phase + 0.4 * k)
    # irregularidad de combustión + ruido de admisión
    wobble = 1 + 0.18 * np.sin(2 * np.pi * (fire / 4) * t * 0.5)
    sig *= wobble
    load = np.clip((rpm - 800) / 6000, 0, 1)
    noise = highpass(rng.standard_normal(N), 800) * (0.05 + 0.25 * load)
    sig = np.tanh(2.2 * sig / (np.max(np.abs(sig)) + 1e-9) + noise)
    sig = lowpass(sig, 5200)
    gain = np.clip(rpm / 400, 0, 1)
    return sig * gain * 0.5


def starter(mix):
    t = t_of(int(0.9 * SR))
    pulse = (np.sin(2 * np.pi * 9 * t) > 0).astype(float)
    tone = np.sin(2 * np.pi * (60 + 90 * t) * t) * pulse
    add(mix, 0.05, lowpass(tone + 0.3 * rng.standard_normal(len(t)) * pulse, 900) * np.minimum(t * 12, 1) * np.exp(-t * 0.8), 0.45)


# ---------------------------------------------------------------- música
def kick(freq0=140, freq1=44, dur=0.45):
    t = t_of(int(dur * SR))
    f = freq1 + (freq0 - freq1) * np.exp(-t * 28)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 7.5) * 1.0 + 0.2 * np.exp(-t * 60) * rng.standard_normal(len(t))


def snare(dur=0.28):
    t = t_of(int(dur * SR))
    body = np.sin(2 * np.pi * 190 * t) * np.exp(-t * 26) * 0.5
    nz = highpass(rng.standard_normal(len(t)), 1500) * np.exp(-t * 18)
    return body + nz * 0.9


def hat(dur=0.08, open_=False):
    t = t_of(int(dur * SR * (3 if open_ else 1)))
    return highpass(rng.standard_normal(len(t)), 7000) * np.exp(-t * (22 if not open_ else 9)) * 0.5


def bass_note(freq, dur):
    t = t_of(int(dur * SR))
    saw = 2 * ((t * freq) % 1) - 1
    sq = np.sign(np.sin(2 * np.pi * freq * t))
    raw = 0.6 * saw + 0.4 * sq
    s = lowpass(raw, 600) + 0.6 * np.exp(-t * 9) * (lowpass(raw, 1500) - lowpass(raw, 600))
    return s * np.minimum(t * 200, 1) * np.exp(-t * 3.2)


def pluck(freq, dur=0.3):
    t = t_of(int(dur * SR))
    s = sum(np.sin(2 * np.pi * freq * k * t) / k for k in range(1, 6))
    return lowpass(s, 3200) * np.exp(-t * 9) * 0.35


def pad(freqs, dur, gain=0.2):
    t = t_of(int(dur * SR))
    s = np.zeros(len(t))
    for f in freqs:
        for detune in (-0.004, 0.0, 0.004):
            saw = 2 * ((t * f * (1 + detune)) % 1) - 1
            s += saw
    s = lowpass(s / (3 * len(freqs)), 1400)
    fade = np.minimum(t / 1.2, 1) * np.minimum((dur - t) / 1.0, 1)
    return s * fade * gain


def impact(dur=2.2):
    t = t_of(int(dur * SR))
    boom = np.sin(2 * np.pi * (50 * np.exp(-t * 1.2) + 28) * t) * np.exp(-t * 2.2)
    crack = highpass(rng.standard_normal(len(t)), 400) * np.exp(-t * 11) * 0.7
    tail = lowpass(rng.standard_normal(len(t)), 2500) * np.exp(-t * 2.5) * 0.25
    return np.tanh(1.6 * (boom + crack + tail))


def music(mix):
    root = {"A": 55.0, "F": 43.65, "C": 65.41, "G": 49.0}
    prog = ["A", "A", "F", "G"]  # un compás por acorde (2 s)
    chords = {"A": [110, 164.8, 220], "F": [87.3, 130.8, 174.6], "G": [98, 146.8, 196]}
    # pad envolvente desde 6 s
    for bar in range(3, 23):
        name = prog[bar % 4]
        add(mix, bar * 2.0, pad(chords[name], 2.3, 0.22 if bar < 14 else 0.28), 1.0, pan=-0.2)
    # bajo en corcheas 6–45 s
    t0 = 6.0
    while t0 < 44.0:
        bar = int(t0 // 2.0)
        name = prog[bar % 4]
        f = root[name] * (2 if int(t0 / (BEAT / 2)) % 4 == 3 else 1)
        gain = 0.55 if t0 < 12 else 0.75
        add(mix, t0, bass_note(f, BEAT / 2 * 0.95), gain)
        t0 += BEAT / 2
    # bombo, hats, caja
    t0 = 12.0
    while t0 < 44.0:
        beat_idx = int(round((t0 - 12.0) / BEAT))
        if t0 < 43.0:
            add(mix, t0, kick(), 0.9)
            add(mix, t0 + BEAT / 2, hat(), 0.5, pan=0.3)
            if t0 >= 20.0 and beat_idx % 2 == 1:
                add(mix, t0, snare(), 0.65)
            if t0 >= 28.0:
                add(mix, t0 + BEAT / 4, hat(), 0.3, pan=-0.3)
                add(mix, t0 + 3 * BEAT / 4, hat(open_=True), 0.28, pan=0.3)
        t0 += BEAT
    # redoble de caja que sube de 34 a 37
    for k in range(24):
        tt = 34.0 + k * (3.0 / 24) * (1 - 0.01 * k)
        add(mix, tt, snare(0.15), 0.25 + 0.5 * k / 24)
    # arpegio desde 20 s
    scale = [220, 261.6, 329.6, 392, 440, 523.3, 659.3, 784]
    t0 = 20.0
    i = 0
    while t0 < 43.0:
        f = scale[[0, 2, 4, 7, 4, 2, 5, 3][i % 8]] * (0.75 if prog[int(t0 // 2) % 4] == "F" else 1)
        add(mix, t0, pluck(f), 0.55 if t0 < 28 else 0.75, pan=0.4 if i % 2 else -0.4)
        t0 += BEAT / 2
        i += 1


def sfx(mix):
    # destello azul / aceleración (4,2–6,0) y barridos de luz
    add(mix, 4.2, swept_noise(1.8, 400, 6500) * np.linspace(0, 1, int(1.8 * SR)) ** 1.5, 0.5)
    for start, dur in [(11.2, 0.9), (19.2, 0.9), (27.0, 1.0), (35.0, 2.0), (6.0, 0.6)]:
        add(mix, start, swept_noise(dur, 600, 5000) * np.linspace(0, 1, int(dur * SR)) ** 2, 0.35)
    # impactos sincronizados con textos y escenas
    for hit, g in [(6.0, 0.9), (12.0, 0.7), (20.0, 0.7), (28.0, 0.75), (37.0, 1.0), (40.0, 0.7)]:
        add(mix, hit, impact(), g)
    # cambios de marcha (bajada de rpm + soplido)
    for tt in [5.7, 39.4, 41.0, 43.0]:
        add(mix, tt, highpass(swept_noise(0.35, 3000, 1200), 800) * np.exp(-t_of(int(0.35 * SR)) * 7), 0.5)
    # cierre: golpe final y cola
    add(mix, 43.4, impact(1.6), 1.0)


def voice(mix):
    timeline = work / "vo/timeline.json"
    if not timeline.exists():
        print("sin voz en off (.work/vo/timeline.json no existe)")
        return np.zeros(N)
    duck = np.zeros(N)
    for line in json.loads(timeline.read_text(encoding="utf-8")):
        samples, rate = sf.read(work / f"vo/{line['id']}.wav")
        if rate != SR:
            idx = np.linspace(0, len(samples) - 1, int(len(samples) * SR / rate))
            samples = np.interp(idx, np.arange(len(samples)), samples)
        samples = highpass(samples, 90) * 1.4
        add(mix, line["start"], samples, 1.0)
        i = int(line["start"] * SR)
        duck[i : i + len(samples)] = 1.0
    return duck


def main():
    music_bus = np.zeros((2, N))
    starter(music_bus)
    eng = engine()
    music(music_bus)
    sfx(music_bus)
    music_bus[0] += eng * 0.9
    music_bus[1] += eng * 0.9

    vo_bus = np.zeros((2, N))
    duck = voice(vo_bus)
    # ducking suave de la música mientras habla la voz
    smooth = lowpass(duck, 4, order=1)
    gain = 1.0 - 0.45 * np.clip(smooth * 2.5, 0, 1)
    mix = music_bus * gain + vo_bus

    # fundido de entrada/salida y limitador
    t = t_of(N)
    mix *= np.minimum(t / 0.15, 1) * np.clip((DUR - t) / 0.8, 0, 1)
    mix = np.tanh(mix / (np.percentile(np.abs(mix), 99.5) * 1.4 + 1e-9)) * 0.92
    sf.write(work / "audio.wav", mix.T.astype(np.float32), SR, subtype="PCM_16")
    print("audio ok", mix.shape, "pico", float(np.max(np.abs(mix))))


if __name__ == "__main__":
    main()
