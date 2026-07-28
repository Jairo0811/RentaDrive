<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-blue-600 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-700 dark:focus:ring-offset-slate-900']) }}>
    {{ $slot }}
</button>
