<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-white">Edición Dominicana</p><p class="text-xs text-slate-400">Preparación fiscal por empresa</p></div></x-slot>
    <x-page-header title="Perfil fiscal dominicano" subtitle="Datos internos para preparar comprobantes; todavía no se emiten NCF ni e-CF." />
    <section class="panel mb-5 p-5 sm:p-6" aria-labelledby="ecf-integration-title">
        <h2 id="ecf-integration-title" class="text-lg font-black text-white">Facturación electrónica · Integración e-CF</h2>
        <p class="mt-2 text-sm text-slate-300">Preparación técnica para conectarse a un proveedor de facturación electrónica autorizado. RentaDrive aún no firma, asigna e-NCF ni envía facturas a la DGII.</p>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3"><dt class="text-slate-400">Proveedor</dt><dd class="mt-1 font-semibold text-amber-300">{{ $ecfReadiness['provider'] }}</dd></div>
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3"><dt class="text-slate-400">Emisor</dt><dd class="mt-1 font-semibold text-slate-200">{{ $ecfReadiness['issuer_authorized'] ? 'Marcado como autorizado; pendiente de verificación' : 'Sin habilitación confirmada' }}</dd></div>
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 sm:col-span-2 xl:col-span-1"><dt class="text-slate-400">Emisión e-CF</dt><dd class="mt-1 font-semibold text-amber-300">Deshabilitada</dd></div>
        </dl>
        <p class="mt-3 text-sm leading-relaxed text-slate-300">Los borradores pueden identificarse por tipo candidato E31 o E32; no tienen validez como comprobantes electrónicos.</p>
        <a href="#fiscal-profile-form" class="btn-secondary mt-4 w-full min-h-11 sm:w-auto">Completar perfil fiscal <span class="sr-only">de la empresa</span></a>
        <div class="mt-5 border-t border-slate-700 pt-5">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <h3 class="text-base font-bold text-white">Ruta de habilitación e-CF</h3>
                <p class="text-xs font-semibold text-slate-400">{{ $ecfReadiness['completed_steps'] }} de {{ $ecfReadiness['total_steps'] }} pasos preparatorios</p>
            </div>
            <p class="mt-2 text-xs text-slate-400">Esta lista es orientativa: un perfil completo no acredita habilitación fiscal ni autoriza emitir comprobantes.</p>
            <ol class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach ($ecfReadiness['checks'] as $check)
                    <li class="min-w-0 rounded-xl border border-slate-700 bg-slate-950 p-4 {{ $loop->last ? 'lg:col-span-2' : '' }}">
                        <div class="flex flex-col items-start gap-2 sm:flex-row sm:justify-between">
                            <p class="font-semibold text-slate-100">{{ $loop->iteration }}. {{ $check['label'] }}</p>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-bold',
                                'bg-emerald-950 text-emerald-300' => $check['complete'],
                                'bg-amber-950 text-amber-300' => ! $check['complete'],
                            ])>{{ $check['complete'] ? 'Datos presentes' : 'Pendiente' }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-400">{{ $check['detail'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
    <section id="fiscal-profile-form" tabindex="-1" class="panel scroll-mt-5 p-5 sm:p-6" aria-labelledby="fiscal-profile-heading">
        <h2 id="fiscal-profile-heading" class="mb-2 text-lg font-black text-white">Datos fiscales de la empresa</h2>
        <p class="mb-4 text-sm text-slate-300">Completa estos datos para preparar borradores internos. No habilitan la emisión electrónica.</p>
        <div class="mb-5 rounded-xl border border-amber-500/40 bg-amber-950/30 p-4 text-sm text-amber-200" role="alert">
            La DGII requiere autorización/certificación antes de emitir comprobantes fiscales electrónicos.
            Este módulo solo prepara borradores y no transmite documentos a la DGII.
        </div>
        <form method="POST" action="{{ route('fiscal.update') }}" class="grid gap-4 md:grid-cols-2">
            @csrf @method('PUT')
            <div><label for="legal_name" class="form-label">Razón social</label><input id="legal_name" name="legal_name" class="form-input" autocomplete="organization" required maxlength="255" value="{{ old('legal_name', $profile?->legal_name ?? '') }}"><x-input-error :messages="$errors->get('legal_name')" /></div>
            <div><label for="rnc" class="form-label">RNC (9 dígitos)</label><input id="rnc" name="rnc" class="form-input" inputmode="numeric" pattern="[0-9]{9}" autocomplete="off" maxlength="9" required value="{{ old('rnc', $profile?->rnc ?? '') }}"><x-input-error :messages="$errors->get('rnc')" /></div>
            <div><label for="address" class="form-label">Dirección fiscal</label><input id="address" name="address" class="form-input" autocomplete="street-address" maxlength="255" value="{{ old('address', $profile?->address ?? '') }}"></div>
            <div><label for="fiscal_mode" class="form-label">Régimen interno de referencia</label><select id="fiscal_mode" name="fiscal_mode" class="form-input"><option value="standard" @selected(old('fiscal_mode', $profile?->fiscal_mode ?? 'standard') === 'standard')>General</option><option value="exempt" @selected(old('fiscal_mode', $profile?->fiscal_mode) === 'exempt')>Exento (requiere revisión tributaria)</option></select></div>
            <div class="md:col-span-2"><button class="btn-primary w-full sm:w-auto">Guardar perfil fiscal</button></div>
        </form>
    </section>
    <section class="table-shell mt-6">
        <div class="p-5"><h2 class="font-bold text-white">Borradores preparados</h2><p class="text-sm text-slate-400">Estos registros no tienen valor de comprobante fiscal autorizado.</p></div>
        @if ($drafts->isEmpty())
            <x-empty-state title="No hay borradores" message="Puedes preparar un borrador desde el detalle de una factura." />
        @else
            <div class="grid gap-3 px-4 pb-4 sm:hidden" aria-label="Borradores fiscales">
                @foreach ($drafts as $draft)
                    <article class="min-w-0 rounded-xl border border-slate-700 bg-slate-950 p-4">
                        <h3 class="break-words font-bold text-slate-100">
                            <a class="focus-ring text-blue-300 underline-offset-4 hover:underline" href="{{ route('invoices.show', $draft->invoice) }}">{{ $draft->invoice->number }}</a>
                        </h3>
                        <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-slate-400">Tipo</dt><dd class="mt-1 font-semibold text-slate-100">{{ $draft->kind }}</dd></div>
                            <div><dt class="text-slate-400">Estado</dt><dd class="mt-1 font-semibold text-amber-300">Sin emitir</dd></div>
                            <div class="col-span-2"><dt class="text-slate-400">Total</dt><dd class="mt-1 font-bold text-white">RD$ {{ number_format((float) $draft->total, 2) }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
            <div class="hidden overflow-x-auto sm:block">
                <table class="data-table">
                    <thead><tr><th>Factura</th><th>Tipo</th><th>Estado</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach ($drafts as $draft)
                            <tr>
                                <td><a href="{{ route('invoices.show', $draft->invoice) }}" class="focus-ring text-blue-400 hover:underline">{{ $draft->invoice->number }}</a></td>
                                <td>{{ $draft->kind }}</td>
                                <td>Borrador (no emitido)</td>
                                <td>RD$ {{ number_format((float) $draft->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-800 p-4">{{ $drafts->links() }}</div>
        @endif
    </section>
</x-app-layout>
