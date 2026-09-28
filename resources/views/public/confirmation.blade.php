<x-public-layout
    :title="$company->name.' · Reserva '.$reservation->code"
    :home-url="route('public.booking.show', ['company' => $company->slug])"
    :badge="$company->name"
>
    <section class="mx-auto max-w-3xl px-5 py-16 sm:px-8 lg:py-24">
        <div class="panel overflow-hidden text-center">
            <div class="bg-gradient-to-br from-emerald-500 to-blue-600 px-6 py-10 text-white">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-white/15 ring-1 ring-inset ring-white/20">
                    <i class="fa-solid fa-check text-2xl" aria-hidden="true"></i>
                </span>
                <p class="mt-5 text-sm font-black uppercase tracking-[.18em] text-emerald-100">Solicitud recibida</p>
                <h1 class="mt-2 text-3xl font-black">Reserva {{ $reservation->code }}</h1>
            </div>

            <div class="p-6 text-left sm:p-8">
                <p class="text-center text-slate-600 dark:text-slate-400">
                    La reserva quedó registrada como <strong class="text-slate-900 dark:text-white">pendiente</strong>. {{ $company->name }} podrá confirmarla desde su panel operativo.
                </p>

                <dl class="mx-auto mt-8 max-w-xl divide-y divide-slate-200 rounded-2xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                    <div class="flex justify-between gap-4 p-4">
                        <dt class="text-slate-500">Vehículo</dt>
                        <dd class="text-right font-bold">{{ $reservation->vehicle->model->display_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 p-4">
                        <dt class="text-slate-500">Recogida</dt>
                        <dd class="text-right font-bold">{{ $reservation->start_at->format('d/m/Y h:i A') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 p-4">
                        <dt class="text-slate-500">Devolución</dt>
                        <dd class="text-right font-bold">{{ $reservation->end_at->format('d/m/Y h:i A') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 p-4">
                        <dt class="text-slate-500">Total estimado</dt>
                        <dd class="text-right font-black text-blue-600 dark:text-blue-400">{{ $company->currency }} {{ number_format((float) $reservation->estimated_total, 2) }}</dd>
                    </div>
                </dl>

                <div class="mt-8 flex justify-center">
                    <a href="{{ route('public.booking.show', ['company' => $company->slug]) }}" class="btn-primary">
                        Volver al portal
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
