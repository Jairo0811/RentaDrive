@props([
    'url' => null,
    'alt' => '',
    'emptyLabel' => 'Sin fotografía',
    'failedLabel' => 'Fotografía no disponible',
])

<div {{ $attributes->class(['relative aspect-video w-full overflow-hidden rounded-xl border border-slate-800 bg-slate-950'])->merge(['x-data' => '{ failed: false }']) }}>
    @if ($url)
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            class="absolute inset-0 h-full w-full object-cover"
            loading="lazy"
            x-show="!failed"
            x-on:error="failed = true"
        >
    @endif

    <div
        class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-950 px-3 text-center text-slate-400"
        @if ($url) x-cloak x-show="failed" @endif
    >
        <svg class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <circle cx="8.5" cy="9" r="1.5"/>
            <path d="m4 17 5-5 4 4 3-3 4 4"/>
        </svg>
        <span class="text-sm font-semibold">{{ $url ? $failedLabel : $emptyLabel }}</span>
    </div>
</div>
