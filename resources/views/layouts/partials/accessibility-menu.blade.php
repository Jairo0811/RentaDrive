<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button
        type="button"
        class="focus-ring rounded-xl border border-slate-200 p-2.5 text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
        @click="open = ! open"
        :aria-expanded="open"
        aria-controls="accessibility-menu"
        aria-label="Abrir centro de accesibilidad"
    >
        <i class="fa-solid fa-universal-access h-5 w-5 text-center leading-5" aria-hidden="true"></i>
    </button>

    <div
        id="accessibility-menu"
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 max-h-[calc(100vh-7rem)] w-[22rem] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:w-[26rem]"
        role="dialog"
        aria-modal="false"
        aria-labelledby="accessibility-menu-title"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="accessibility-menu-title" class="text-base font-black text-slate-900 dark:text-white">Centro de accesibilidad</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Personaliza la interfaz. Las preferencias se guardan en este navegador.</p>
            </div>
            <button type="button" class="focus-ring rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="open = false" aria-label="Cerrar centro de accesibilidad">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <section class="mt-5" aria-labelledby="accessibility-profiles-title">
            <h3 id="accessibility-profiles-title" class="text-sm font-bold text-slate-800 dark:text-slate-200">Perfiles rápidos</h3>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <button type="button" class="accessibility-profile" @click="$store.accessibility.applyProfile('lowVision')">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i><span>Visión reducida</span>
                </button>
                <button type="button" class="accessibility-profile" @click="$store.accessibility.applyProfile('reading')">
                    <i class="fa-solid fa-book-open-reader" aria-hidden="true"></i><span>Lectura cómoda</span>
                </button>
                <button type="button" class="accessibility-profile" @click="$store.accessibility.applyProfile('motor')">
                    <i class="fa-solid fa-hand-pointer" aria-hidden="true"></i><span>Movilidad reducida</span>
                </button>
                <button type="button" class="accessibility-profile" @click="$store.accessibility.applyProfile('senior')">
                    <i class="fa-solid fa-person-cane" aria-hidden="true"></i><span>Adulto mayor</span>
                </button>
            </div>
        </section>

        <fieldset class="mt-5">
            <legend class="text-sm font-bold text-slate-800 dark:text-slate-200">Tamaño del texto</legend>
            <div class="mt-2 grid grid-cols-4 gap-2">
                <template x-for="size in [100, 125, 150, 200]" :key="size">
                    <button type="button" class="accessibility-choice" :class="$store.accessibility.fontScale === size ? 'accessibility-choice-active' : ''" @click="$store.accessibility.setFontScale(size)" :aria-pressed="$store.accessibility.fontScale === size" x-text="`${size}%`"></button>
                </template>
            </div>
        </fieldset>

        <fieldset class="mt-5">
            <legend class="text-sm font-bold text-slate-800 dark:text-slate-200">Escala de interfaz</legend>
            <div class="mt-2 grid grid-cols-3 gap-2">
                <template x-for="scale in [100, 110, 125]" :key="scale">
                    <button type="button" class="accessibility-choice" :class="$store.accessibility.interfaceScale === scale ? 'accessibility-choice-active' : ''" @click="$store.accessibility.setInterfaceScale(scale)" :aria-pressed="$store.accessibility.interfaceScale === scale" x-text="`${scale}%`"></button>
                </template>
            </div>
        </fieldset>

        <fieldset class="mt-5">
            <legend class="text-sm font-bold text-slate-800 dark:text-slate-200">Presentación del texto</legend>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <button type="button" class="accessibility-choice" :class="$store.accessibility.readableFont ? 'accessibility-choice-active' : ''" @click="$store.accessibility.toggleReadableFont()" :aria-pressed="$store.accessibility.readableFont">
                    <i class="fa-solid fa-font mr-1" aria-hidden="true"></i> Fuente legible
                </button>
                <button type="button" class="accessibility-choice" :class="$store.accessibility.textSpacing ? 'accessibility-choice-active' : ''" @click="$store.accessibility.toggleTextSpacing()" :aria-pressed="$store.accessibility.textSpacing">
                    <i class="fa-solid fa-text-width mr-1" aria-hidden="true"></i> Espaciado amplio
                </button>
            </div>
        </fieldset>

        <fieldset class="mt-5">
            <legend class="text-sm font-bold text-slate-800 dark:text-slate-200">Filtro visual</legend>
            <div class="mt-2 grid grid-cols-3 gap-2">
                <template x-for="filter in [{ value: 'none', label: 'Normal' }, { value: 'grayscale', label: 'Grises' }, { value: 'invert', label: 'Invertido' }]" :key="filter.value">
                    <button type="button" class="accessibility-choice" :class="$store.accessibility.colorFilter === filter.value ? 'accessibility-choice-active' : ''" @click="$store.accessibility.setColorFilter(filter.value)" :aria-pressed="$store.accessibility.colorFilter === filter.value" x-text="filter.label"></button>
                </template>
            </div>
        </fieldset>

        <div class="mt-5 space-y-3">
            <label class="accessibility-toggle">
                <span><span class="accessibility-toggle-title">Alto contraste</span><span class="accessibility-toggle-help">Aumenta la diferenciación entre fondos, texto y controles.</span></span>
                <input type="checkbox" class="focus-ring h-5 w-5 rounded border-slate-300 text-blue-600" :checked="$store.accessibility.highContrast" @change="$store.accessibility.toggleHighContrast()">
            </label>

            <label class="accessibility-toggle">
                <span><span class="accessibility-toggle-title">Resaltar foco</span><span class="accessibility-toggle-help">Hace más evidente el elemento activo al navegar con teclado.</span></span>
                <input type="checkbox" class="focus-ring h-5 w-5 rounded border-slate-300 text-blue-600" :checked="$store.accessibility.enhancedFocus" @change="$store.accessibility.toggleEnhancedFocus()">
            </label>

            <label class="accessibility-toggle">
                <span><span class="accessibility-toggle-title">Cursor grande</span><span class="accessibility-toggle-help">Amplía el cursor sobre botones, enlaces y formularios.</span></span>
                <input type="checkbox" class="focus-ring h-5 w-5 rounded border-slate-300 text-blue-600" :checked="$store.accessibility.largeCursor" @change="$store.accessibility.toggleLargeCursor()">
            </label>

            <label class="accessibility-toggle">
                <span><span class="accessibility-toggle-title">Reducir movimiento</span><span class="accessibility-toggle-help">Desactiva animaciones y transiciones no esenciales.</span></span>
                <input type="checkbox" class="focus-ring h-5 w-5 rounded border-slate-300 text-blue-600" :checked="$store.accessibility.reducedMotion" @change="$store.accessibility.toggleReducedMotion()">
            </label>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" class="btn-secondary w-full" @click="$store.accessibility.announceSummary()">
                <i class="fa-solid fa-volume-high mr-2" aria-hidden="true"></i>Resumen
            </button>
            <button type="button" class="btn-secondary w-full" @click="$store.accessibility.reset()">
                <i class="fa-solid fa-rotate-left mr-2" aria-hidden="true"></i>Restablecer
            </button>
        </div>
    </div>
</div>
