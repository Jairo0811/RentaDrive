<x-app-layout>
    <x-slot name="header"><div><p class="text-lg font-black text-white">Edición Dominicana</p><p class="text-xs text-slate-400">Preparación fiscal por empresa</p></div></x-slot>
    <x-page-header title="Perfil fiscal dominicano" subtitle="Datos internos para preparar comprobantes; todavía no se emiten NCF ni e-CF." />
    <section class="panel mb-5 p-5 sm:p-6" aria-labelledby="ecf-integration-title">
        <h2 id="ecf-integration-title" class="text-lg font-black text-white">Facturación electrónica · Integración e-CF</h2>
        <p class="mt-2 text-sm text-slate-300">Preparación técnica para conectarse a un proveedor de facturación electrónica autorizado. RentaDrive aún no firma, asigna e-NCF ni envía facturas a la DGII.</p>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3"><dt class="text-slate-400">Proveedor</dt><dd class="mt-1 font-semibold text-amber-300">{{ $ecfReadiness['provider'] }}</dd></div>
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3"><dt class="text-slate-400">Emisor</dt><dd class="mt-1 font-semibold text-slate-200">{{ $ecfReadiness['issuer_authorized'] ? 'Marcado como autorizado; pendiente de verificación' : 'Sin habilitación confirmada' }}</dd></div>
            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3"><dt class="text-slate-400">Emisión e-CF</dt><dd class="mt-1 font-semibold text-amber-300">Deshabilitada</dd></div>
        </dl>
        <p class="mt-3 text-xs text-slate-400">Los borradores pueden identificarse por tipo candidato E31 o E32; no tienen validez como comprobantes electrónicos.</p>
    </section>
    <section class="panel p-5 sm:p-6">
        <div class="mb-5 rounded-xl border border-amber-500/40 bg-amber-950/30 p-4 text-sm text-amber-200" role="alert">
            La DGII requiere autorización/certificación antes de emitir comprobantes fiscales electrónicos.
            Este módulo solo prepara borradores y no transmite documentos a la DGII.
        </div>
        <form method="POST" action="{{ route('fiscal.update') }}" class="grid gap-4 md:grid-cols-2">
            @csrf @method('PUT')
            <div><label for="legal_name" class="form-label">Razón social</label><input id="legal_name" name="legal_name" class="form-input" required maxlength="255" value="{{ old('legal_name', $profile?->legal_name ?? '') }}"><x-input-error :messages="$errors->get('legal_name')" /></div>
            <div><label for="rnc" class="form-label">RNC (9 dígitos)</label><input id="rnc" name="rnc" class="form-input" inputmode="numeric" maxlength="9" required value="{{ old('rnc', $profile?->rnc ?? '') }}"><x-input-error :messages="$errors->get('rnc')" /></div>
            <div><label for="address" class="form-label">Dirección fiscal</label><input id="address" name="address" class="form-input" maxlength="255" value="{{ old('address', $profile?->address ?? '') }}"></div>
            <div><label for="fiscal_mode" class="form-label">Régimen interno de referencia</label><select id="fiscal_mode" name="fiscal_mode" class="form-input"><option value="standard" @selected(old('fiscal_mode', $profile?->fiscal_mode ?? 'standard') === 'standard')>General</option><option value="exempt" @selected(old('fiscal_mode', $profile?->fiscal_mode) === 'exempt')>Exento (requiere revisión tributaria)</option></select></div>
            <div class="md:col-span-2"><button class="btn-primary">Guardar perfil fiscal</button></div>
        </form>
    </section>
    <section class="table-shell mt-6">
        <div class="p-5"><h2 class="font-bold text-white">Borradores preparados</h2><p class="text-sm text-slate-400">Estos registros no tienen valor de comprobante fiscal autorizado.</p></div>
        @if($drafts->isEmpty())<x-empty-state title="No hay borradores" message="Puedes preparar un borrador desde el detalle de una factura." />@else
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Factura</th><th>Tipo</th><th>Estado</th><th>Total</th></tr></thead><tbody>
            @foreach($drafts as $draft)<tr><td><a href="{{ route('invoices.show', $draft->invoice) }}" class="text-blue-400">{{ $draft->invoice->number }}</a></td><td>{{ $draft->kind }}</td><td>Borrador (no emitido)</td><td>RD$ {{ number_format((float) $draft->total, 2) }}</td></tr>@endforeach
        </tbody></table></div><div class="p-4">{{ $drafts->links() }}</div>@endif
    </section>
</x-app-layout>
