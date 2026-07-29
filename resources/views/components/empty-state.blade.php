@props(['title', 'message', 'action' => null, 'href' => null])

<div class="grid min-h-56 place-items-center px-6 py-12 text-center">
    <div>
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5v14"/>
            </svg>
        </span>
        <h2 class="mt-4 font-bold text-slate-900 dark:text-white">{{ $title }}</h2>
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $message }}</p>
        @if ($action && $href)
            <a href="{{ $href }}" class="btn-primary mt-5">{{ $action }}</a>
        @endif
    </div>
</div>
