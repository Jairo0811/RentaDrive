<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Firma digital</p>
            <p class="text-xs text-slate-500">{{ $rental->code }}</p>
        </div>
    </x-slot>

    <x-page-header :title="'Firmar contrato '.$rental->code" :subtitle="$rental->customer->full_name">
        <x-slot name="actions">
            <a href="{{ route('rentals.contract', $rental) }}" target="_blank" class="btn-secondary">Ver contrato</a>
            <a href="{{ route('rentals.show', $rental) }}" class="btn-secondary">Volver</a>
        </x-slot>
    </x-page-header>

    @if ($rental->renterSignature)
        <section class="panel p-6">
            <p class="font-black text-emerald-700 dark:text-emerald-300">Contrato ya firmado</p>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                {{ $rental->renterSignature->signer_name }} · {{ $rental->renterSignature->signed_at->format('d/m/Y h:i A') }}
            </p>
            <p class="mt-3 break-all font-mono text-xs text-slate-500">{{ $rental->renterSignature->contract_hash }}</p>
        </section>
    @else
        <form
            method="POST"
            action="{{ route('rentals.signature.store', $rental) }}"
            class="grid gap-6 lg:grid-cols-[.8fr_1.2fr]"
            x-data="signaturePad()"
        >
            @csrf

            <section class="panel p-5 sm:p-6">
                <h2 class="font-black text-slate-950 dark:text-white">Confirmación</h2>
                <div class="mt-5 space-y-4 text-sm">
                    <p><span class="text-slate-500">Arrendatario:</span> <strong>{{ $rental->customer->full_name }}</strong></p>
                    <p><span class="text-slate-500">Documento:</span> <strong>{{ $rental->customer->document_number }}</strong></p>
                    <p><span class="text-slate-500">Vehículo:</span> <strong>{{ $rental->vehicle->display_name }}</strong></p>
                    <p><span class="text-slate-500">Total:</span> <strong>RD$ {{ number_format((float) $rental->total, 2) }}</strong></p>
                    <p class="break-all font-mono text-xs text-slate-400">Hash contractual: {{ $contractHash }}</p>
                </div>

                <div class="mt-6">
                    <label class="form-label" for="signed_name">Nombre del firmante</label>
                    <input id="signed_name" name="signed_name" class="form-input" value="{{ old('signed_name', $rental->customer->full_name) }}" required>
                </div>

                <label class="mt-5 flex items-start gap-3 text-sm">
                    <input type="checkbox" name="accept_terms" value="1" class="mt-1" required>
                    <span>Acepto los términos del contrato y confirmo que la firma capturada corresponde al arrendatario identificado.</span>
                </label>
            </section>

            <section class="panel p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="font-black text-slate-950 dark:text-white">Firma del arrendatario</h2>
                        <p class="mt-1 text-sm text-slate-500">Firma con el dedo, stylus o mouse dentro del recuadro.</p>
                    </div>
                    <button type="button" class="btn-secondary" @click="clear()">Limpiar</button>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-white dark:border-slate-700">
                    <canvas
                        x-ref="canvas"
                        class="block h-72 w-full touch-none"
                        aria-label="Área de firma"
                    ></canvas>
                </div>

                <input type="hidden" name="signature_data" x-ref="signatureData">
                <x-input-error :messages="$errors->get('signature_data')" class="mt-2" />

                <button type="submit" class="btn-primary mt-6 w-full py-3" @click="capture()">
                    Firmar y sellar contrato
                </button>
            </section>
        </form>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('signaturePad', () => ({
                    drawing: false,
                    ctx: null,
                    init() {
                        const canvas = this.$refs.canvas;
                        const resize = () => {
                            const ratio = window.devicePixelRatio || 1;
                            const rect = canvas.getBoundingClientRect();
                            canvas.width = Math.max(1, Math.floor(rect.width * ratio));
                            canvas.height = Math.max(1, Math.floor(rect.height * ratio));
                            this.ctx = canvas.getContext('2d');
                            this.ctx.scale(ratio, ratio);
                            this.ctx.lineWidth = 2.4;
                            this.ctx.lineCap = 'round';
                            this.ctx.strokeStyle = '#0f172a';
                        };
                        resize();

                        const point = (event) => {
                            const rect = canvas.getBoundingClientRect();
                            return { x: event.clientX - rect.left, y: event.clientY - rect.top };
                        };

                        canvas.addEventListener('pointerdown', (event) => {
                            this.drawing = true;
                            canvas.setPointerCapture(event.pointerId);
                            const p = point(event);
                            this.ctx.beginPath();
                            this.ctx.moveTo(p.x, p.y);
                        });
                        canvas.addEventListener('pointermove', (event) => {
                            if (!this.drawing) return;
                            const p = point(event);
                            this.ctx.lineTo(p.x, p.y);
                            this.ctx.stroke();
                        });
                        canvas.addEventListener('pointerup', () => this.drawing = false);
                        canvas.addEventListener('pointercancel', () => this.drawing = false);
                    },
                    clear() {
                        const canvas = this.$refs.canvas;
                        this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                        this.$refs.signatureData.value = '';
                    },
                    capture() {
                        this.$refs.signatureData.value = this.$refs.canvas.toDataURL('image/png');
                    },
                }));
            });
        </script>
    @endif
</x-app-layout>
