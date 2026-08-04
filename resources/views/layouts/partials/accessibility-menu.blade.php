<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button
        type="button"
        class="focus-ring rounded-xl border border-slate-200 p-2.5 text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
        @click="open = ! open"
        :aria-expanded="open"
        aria-controls="accessibility-menu"
        aria-label="Abrir opciones de accesibilidad"
    >
        <i class="fa-solid fa-universal-access h-5 w-5 text-center leading-5" aria-hidden="true"></i>
    </button>

    <div
        id="accessibility-menu"
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        role="dialog"
        aria-modal="false"
        aria-labelledby="accessibility-menu-title"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="accessibility-menu-title" class="text-base font-black text-slate-900 dark:text-white">
                    Accesibilidad
                </h2>
                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    Ajusta la presentación sin afectar tus datos.
                </p>
            </div>

            <button type="button" class="focus-ring rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="open = false" aria-label="Cerrar opciones de accesibilidad">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <fieldset class="mt-5">
            <legend class="text-sm font-bold text-slate-800 dark:text-slate-200">Tamaño del texto</legend>
            <div class="mt-2 grid grid-cols-4 gap-2">
                <template x-for="size in [100, 125, 150, 200]" :key="size">
                    <button
                        type="button"
                        class="focus-ring rounded-lg border px-2 py-2 text-sm font-bold transition"
                        :class="$store.accessibility.fontScale === size
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800'"
                        @click="$store.accessibility.setFontScale(size)"
                        :aria-pressed="$store.accessibility.fontScale === size"
                        x-text="`${size}%`"
                    ></button>
                </template>
            </div>
        </fieldset>

        <div class="mt-5 space-y-3">
            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                <span>
                    <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">Alto contraste</span>
                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Aumenta la diferenciación visual.</span>
                </span>
                <input
                    type="checkbox"
                    class="focus-ring h-5 w-5 rounded border-slate-300 text-blue-600"
                    :checked="$store.accessibility.highContrast"
                    @change="$store.accessibility.toggleHighContrast()"
                >
            </label>

            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                <span>
                    <span class="block text-sm font-bold text-slate-800 dark:text-slate-200">Reducir movimiento</span>
                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Desactiva animaciones no esenciales.</span>
                </span>
                <input
                    type="checkbox"
                    class="focus-ring h-5 w-5 rounded border-slate-300 text-blue-600"
                    :checked="$store.accessibility.reducedMotion"
                    @change="$store.accessibility.toggleReducedMotion()"
                >
            </label>
        </div>

        <button type="button" class="btn-secondary mt-5 w-full" @click="$store.accessibility.reset()">
            <i class="fa-solid fa-rotate-left mr-2" aria-hidden="true"></i>
            Restablecer preferencias
        </button>
    </div>
</div>
