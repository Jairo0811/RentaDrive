<x-public-layout
    :title="$company->name.' · Confirmar reserva'"
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
            class="focus-ring inline-flex items-center gap-2 rounded-lg text-sm font-bold text-blue-600 dark:text-blue-400"
        >
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Volver a disponibilidad
        </a>

        <div class="mt-6 grid gap-8 lg:grid-cols-[.9fr_1.1fr]">
            <aside class="panel h-fit overflow-hidden">
                <div class="grid h-52 place-items-center bg-gradient-to-br from-slate-950 via-[#071a38] to-blue-950 text-white">
                    <i class="fa-solid fa-car-side text-8xl text-blue-200" aria-hidden="true"></i>
                </div>
                <div class="p-6">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-blue-600 dark:text-blue-400">{{ $vehicle->category->name }}</p>
                    <h1 class="mt-2 text-2xl font-black">{{ $vehicle->model->display_name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $branch->name }}{{ $branch->city ? ' · '.$branch->city : '' }}</p>

                    <dl class="mt-6 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Recogida</dt>
                            <dd class="font-bold">{{ $startAt->format('d/m/Y h:i A') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Devolución</dt>
                            <dd class="font-bold">{{ $endAt->format('d/m/Y h:i A') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Duración</dt>
                            <dd class="font-bold">{{ $days }} {{ $days === 1 ? 'día' : 'días' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Tarifa diaria</dt>
                            <dd class="font-bold">{{ $company->currency }} {{ number_format($dailyRate, 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-4 text-base dark:border-slate-800">
                            <dt class="font-black">Total estimado</dt>
                            <dd class="font-black text-blue-600 dark:text-blue-400">{{ $company->currency }} {{ number_format($estimatedTotal, 2) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-4 rounded-xl bg-slate-100 px-4 py-3 text-xs leading-5 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        La reserva se crea como pendiente. El pago en línea y los extras se incorporarán en la siguiente fase comercial.
                    </p>
                </div>
            </aside>

            <section class="panel p-6 sm:p-8">
                <p class="text-sm font-black uppercase tracking-[.16em] text-blue-600 dark:text-blue-400">Datos del conductor</p>
                <h2 class="mt-2 text-3xl font-black">Confirma tu reserva</h2>
                <p class="mt-2 text-sm text-slate-500">Usaremos estos datos únicamente para registrar y gestionar esta solicitud de alquiler.</p>

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
                        <input type="checkbox" name="terms" value="1" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" required>
                        <span>
                            Confirmo que los datos suministrados son correctos y entiendo que esta solicitud queda pendiente de confirmación por {{ $company->name }}.
                        </span>
                    </label>
                    <x-input-error :messages="$errors->get('terms')" class="sm:col-span-2" />

                    <div class="sm:col-span-2">
                        <button type="submit" class="btn-primary w-full py-3">
                            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                            Crear reserva pendiente
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</x-public-layout>
