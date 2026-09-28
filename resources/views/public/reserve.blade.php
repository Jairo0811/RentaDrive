<x-public-layout
    :title="$company->name.' · Confirmar reserva'"
    :company="$company"
    :home-url="route('public.booking.show', ['company' => $company->slug])"
    :badge="$company->name"
>
    <section class="mx-auto max-w-6xl px-5 py-10 sm:px-8 lg:py-14">
        <a
            href="{{ route('public.booking.search', [
                'company' => $company->slug,
                'branch_id' => $branch->id,
                'start_at' => $startAt->format('Y-m-d\TH:i'),
                'end_at' => $endAt->format('Y-m-d\TH:i'),
            ]) }}"
            class="focus-ring brand-text inline-flex items-center gap-2 rounded-lg text-sm font-bold"
        >
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Volver a disponibilidad
        </a>

        <div class="mt-6 grid gap-8 lg:grid-cols-[.9fr_1.1fr]">
            <aside class="space-y-6">
                <section class="panel h-fit overflow-hidden">
                    <div class="grid h-56 place-items-center overflow-hidden bg-gradient-to-br from-slate-950 via-[#071a38] to-blue-950 text-white">
                        @if ($vehicle->photo_url)
                            <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->model->display_name }}" class="h-full w-full object-cover">
                        @else
                            <i class="fa-solid fa-car-side text-8xl text-blue-200" aria-hidden="true"></i>
                        @endif
                    </div>

                    <div class="p-6">
                        <p class="brand-text text-xs font-black uppercase tracking-[.16em]">{{ $vehicle->category->name }}</p>
                        <h1 class="mt-2 text-2xl font-black">{{ $vehicle->model->display_name }}</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $branch->name }}{{ $branch->city ? ' · '.$branch->city : '' }}</p>

                        <dl class="mt-6 space-y-3 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Recogida</dt><dd class="font-bold">{{ $startAt->format('d/m/Y h:i A') }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Devolución</dt><dd class="font-bold">{{ $endAt->format('d/m/Y h:i A') }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Duración</dt><dd class="font-bold">{{ $days }} {{ $days === 1 ? 'día' : 'días' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Tarifa diaria base</dt><dd class="font-bold">{{ $company->currency }} {{ number_format($dailyRate, 2) }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-slate-500">Renta / temporada</dt><dd class="font-bold">{{ $company->currency }} {{ number_format($pricing['seasonal_total'], 2) }}</dd></div>

                            @if ($pricing['duration_discount'] > 0)
                                <div class="flex justify-between gap-4 text-emerald-600">
                                    <dt>Descuento por duración ({{ number_format($pricing['duration_discount_percent'], 0) }}%)</dt>
                                    <dd class="font-bold">- {{ $company->currency }} {{ number_format($pricing['duration_discount'], 2) }}</dd>
                                </div>
                            @endif

                            @if ($pricing['extras_total'] > 0)
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">Extras / seguros</dt><dd class="font-bold">+ {{ $company->currency }} {{ number_format($pricing['extras_total'], 2) }}</dd></div>
                            @endif

                            @if ($pricing['promo_discount'] > 0)
                                <div class="flex justify-between gap-4 text-emerald-600">
                                    <dt>Promoción {{ $pricing['promo_code'] }}</dt>
                                    <dd class="font-bold">- {{ $company->currency }} {{ number_format($pricing['promo_discount'], 2) }}</dd>
                                </div>
                            @endif

                            <div class="flex justify-between gap-4 border-t border-slate-200 pt-4 text-base dark:border-slate-800">
                                <dt class="font-black">Total estimado</dt>
                                <dd class="brand-text font-black">{{ $company->currency }} {{ number_format($estimatedTotal, 2) }}</dd>
                            </div>
                        </dl>

                        @if (! empty($pricing['season_names']))
                            <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                                Temporada aplicada: {{ implode(', ', $pricing['season_names']) }}.
                            </p>
                        @endif
                    </div>
                </section>

                <section class="panel p-5">
                    <h2 class="font-black">Personaliza tu reserva</h2>
                    <p class="mt-1 text-sm text-slate-500">Selecciona extras/seguros o aplica una promoción y recalcula antes de confirmar.</p>

                    <form method="GET" action="{{ route('public.booking.create', ['company' => $company->slug]) }}" class="mt-5 space-y-4">
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                        <input type="hidden" name="start_at" value="{{ $startAt->format('Y-m-d\TH:i') }}">
                        <input type="hidden" name="end_at" value="{{ $endAt->format('Y-m-d\TH:i') }}">

                        @if ($availableExtras !== [])
                            <div class="space-y-2">
                                @foreach ($availableExtras as $extra)
                                    <label class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                                        <span class="flex items-start gap-3">
                                            <input type="checkbox" name="extras[]" value="{{ $extra['code'] }}" @checked(in_array($extra['code'], $selectedExtraCodes, true)) class="mt-1 rounded border-slate-300">
                                            <span>
                                                <strong class="block">{{ $extra['name'] }}</strong>
                                                <span class="text-xs text-slate-500">{{ $extra['type'] === 'insurance' ? 'Seguro' : 'Extra' }} · {{ $extra['billing'] === 'per_day' ? 'por día' : 'cargo único' }}</span>
                                            </span>
                                        </span>
                                        <strong>{{ $company->currency }} {{ number_format($extra['price'], 2) }}</strong>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label for="promo_code" class="form-label">Código promocional</label>
                            <input id="promo_code" name="promo_code" value="{{ $requestedPromoCode }}" class="form-input uppercase" placeholder="WELCOME10">
                            @if ($promoInvalid)
                                <p class="mt-2 text-xs font-bold text-red-600">El código no existe o no está vigente.</p>
                            @elseif ($pricing['promo_code'])
                                <p class="mt-2 text-xs font-bold text-emerald-600">Promoción {{ $pricing['promo_code'] }} aplicada.</p>
                            @endif
                        </div>

                        <button class="btn-secondary w-full">Actualizar cotización</button>
                    </form>
                </section>
            </aside>

            <section class="panel h-fit p-6 sm:p-8">
                <p class="brand-text text-sm font-black uppercase tracking-[.16em]">Datos del conductor</p>
                <h2 class="mt-2 text-3xl font-black">Confirma tu reserva</h2>
                <p class="mt-2 text-sm text-slate-500">El total mostrado será recalculado en el servidor justo antes de registrar la reserva.</p>

                @if ($errors->any())
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300" role="alert">
                        <p class="font-bold">Revisa los campos marcados antes de continuar.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('public.booking.store', ['company' => $company->slug]) }}" class="mt-7 grid gap-5 sm:grid-cols-2">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                    <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                    <input type="hidden" name="start_at" value="{{ $startAt->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="end_at" value="{{ $endAt->format('Y-m-d\TH:i') }}">
                    @foreach ($selectedExtraCodes as $extraCode)
                        <input type="hidden" name="extras[]" value="{{ $extraCode }}">
                    @endforeach
                    @if ($requestedPromoCode)
                        <input type="hidden" name="promo_code" value="{{ $requestedPromoCode }}">
                    @endif

                    <div>
                        <label for="first_name" class="form-label">Nombre</label>
                        <input id="first_name" name="first_name" class="form-input" value="{{ old('first_name') }}" required>
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="last_name" class="form-label">Apellido</label>
                        <input id="last_name" name="last_name" class="form-input" value="{{ old('last_name') }}" required>
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="document_type" class="form-label">Documento</label>
                        <select id="document_type" name="document_type" class="form-input" required>
                            <option value="cedula" @selected(old('document_type') === 'cedula')>Cédula</option>
                            <option value="passport" @selected(old('document_type') === 'passport')>Pasaporte</option>
                            <option value="rnc" @selected(old('document_type') === 'rnc')>RNC</option>
                            <option value="other" @selected(old('document_type') === 'other')>Otro</option>
                        </select>
                    </div>

                    <div>
                        <label for="document_number" class="form-label">Número de documento</label>
                        <input id="document_number" name="document_number" class="form-input" value="{{ old('document_number') }}" required>
                        <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
                    </div>

                    <div>
                        <label for="email" class="form-label">Correo</label>
                        <input id="email" name="email" type="email" class="form-input" value="{{ old('email') }}" required>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="phone" class="form-label">Teléfono</label>
                        <input id="phone" name="phone" class="form-input" value="{{ old('phone') }}" required>
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <label class="sm:col-span-2 flex items-start gap-3 rounded-xl border border-slate-200 p-4 text-sm dark:border-slate-800">
                        <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-slate-300" required>
                        <span>Confirmo que los datos son correctos y entiendo que la solicitud queda pendiente de confirmación por {{ $company->name }}.</span>
                    </label>

                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-primary w-full py-3" @disabled($promoInvalid)>
                            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                            Crear reserva por {{ $company->currency }} {{ number_format($estimatedTotal, 2) }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</x-public-layout>
