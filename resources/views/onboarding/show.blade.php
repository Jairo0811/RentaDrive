<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-lg font-black text-slate-950 dark:text-white">Onboarding SaaS</p>
            <p class="text-xs text-slate-500">{{ $company->name }}</p>
        </div>
    </x-slot>

    <x-page-header title="Prepara tu operación" subtitle="Completa los pasos esenciales antes de iniciar un piloto comercial." />

    <section class="panel p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-600">Progreso</p>
                <p class="mt-2 text-4xl font-black text-slate-950 dark:text-white">{{ $progress }}%</p>
            </div>
            @if ($currentSubscription)
                <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm dark:bg-slate-950/50">
                    <p class="font-bold">{{ config('rentadrive.plans.'.$currentSubscription->plan_code.'.name', $currentSubscription->plan_code) }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ strtoupper($currentSubscription->status) }}
                        @if ($currentSubscription->trial_ends_at)
                            · trial hasta {{ $currentSubscription->trial_ends_at->format('d/m/Y') }}
                        @endif
                    </p>
                </div>
            @endif
        </div>

        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div class="h-full rounded-full bg-blue-600" style="width: {{ $progress }}%"></div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach ($items as $item)
            <article class="panel p-5 sm:p-6">
                <div class="flex items-start gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $item['complete'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                        <i class="fa-solid {{ $item['complete'] ? 'fa-check' : 'fa-hourglass-half' }}" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="font-black text-slate-950 dark:text-white">{{ $item['label'] }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $item['complete'] ? 'Completado' : 'Pendiente' }}</p>
                        @if (!$item['complete'] && $item['route'])
                            <a href="{{ $item['route'] }}" class="mt-4 inline-flex text-sm font-bold text-blue-600">Resolver ahora</a>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <form method="POST" action="{{ route('onboarding.complete') }}" class="mt-6 flex justify-end">
        @csrf
        <button class="btn-primary" @disabled($progress < 100)>Finalizar onboarding</button>
    </form>
</x-app-layout>
