import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

const safeStorageGet = (key) => {
    try {
        return localStorage.getItem(key);
    } catch {
        return null;
    }
};

const safeStorageSet = (key, value) => {
    try {
        localStorage.setItem(key, value);
    } catch {
        // Browser storage can be disabled without breaking the interface.
    }
};

const readBoolean = (key, fallback = false) => {
    const value = safeStorageGet(key);

    if (value === null) {
        return fallback;
    }

    return value === 'true';
};

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const allowedFontScales = [100, 125, 150, 200];
const allowedInterfaceScales = [100, 110, 125];
const allowedColorFilters = ['none', 'grayscale', 'invert'];
const storedFontScale = Number(safeStorageGet('rentadrive-font-scale'));
const storedInterfaceScale = Number(safeStorageGet('rentadrive-interface-scale'));
const storedColorFilter = safeStorageGet('rentadrive-color-filter');
const storedReducedMotion = safeStorageGet('rentadrive-reduced-motion');

Alpine.store('accessibility', {
    fontScale: allowedFontScales.includes(storedFontScale) ? storedFontScale : 100,
    interfaceScale: allowedInterfaceScales.includes(storedInterfaceScale) ? storedInterfaceScale : 100,
    colorFilter: allowedColorFilters.includes(storedColorFilter) ? storedColorFilter : 'none',
    highContrast: readBoolean('rentadrive-high-contrast'),
    reducedMotion: storedReducedMotion === null ? prefersReducedMotion : storedReducedMotion === 'true',
    readableFont: readBoolean('rentadrive-readable-font'),
    textSpacing: readBoolean('rentadrive-text-spacing'),
    enhancedFocus: readBoolean('rentadrive-enhanced-focus', true),
    largeCursor: readBoolean('rentadrive-large-cursor'),

    apply() {
        const root = document.documentElement;
        root.style.setProperty('--rentadrive-font-scale', `${this.fontScale}%`);
        root.style.setProperty('--rentadrive-interface-scale', String(this.interfaceScale / 100));
        root.classList.toggle('high-contrast', this.highContrast);
        root.classList.toggle('reduce-motion', this.reducedMotion);
        root.classList.toggle('readable-font', this.readableFont);
        root.classList.toggle('text-spacing', this.textSpacing);
        root.classList.toggle('enhanced-focus', this.enhancedFocus);
        root.classList.toggle('large-cursor', this.largeCursor);
        root.classList.toggle('filter-grayscale', this.colorFilter === 'grayscale');
        root.classList.toggle('filter-invert', this.colorFilter === 'invert');
        window.dispatchEvent(new CustomEvent('rentadrive:accessibility-changed'));
    },

    persist() {
        safeStorageSet('rentadrive-font-scale', String(this.fontScale));
        safeStorageSet('rentadrive-interface-scale', String(this.interfaceScale));
        safeStorageSet('rentadrive-color-filter', this.colorFilter);
        safeStorageSet('rentadrive-high-contrast', String(this.highContrast));
        safeStorageSet('rentadrive-reduced-motion', String(this.reducedMotion));
        safeStorageSet('rentadrive-readable-font', String(this.readableFont));
        safeStorageSet('rentadrive-text-spacing', String(this.textSpacing));
        safeStorageSet('rentadrive-enhanced-focus', String(this.enhancedFocus));
        safeStorageSet('rentadrive-large-cursor', String(this.largeCursor));
    },

    update() {
        this.persist();
        this.apply();
    },

    setFontScale(value) {
        const parsedValue = Number(value);
        if (! allowedFontScales.includes(parsedValue)) return;
        this.fontScale = parsedValue;
        this.update();
    },

    setInterfaceScale(value) {
        const parsedValue = Number(value);
        if (! allowedInterfaceScales.includes(parsedValue)) return;
        this.interfaceScale = parsedValue;
        this.update();
    },

    setColorFilter(value) {
        if (! allowedColorFilters.includes(value)) return;
        this.colorFilter = value;
        this.update();
    },

    toggleHighContrast() { this.highContrast = ! this.highContrast; this.update(); },
    toggleReducedMotion() { this.reducedMotion = ! this.reducedMotion; this.update(); },
    toggleReadableFont() { this.readableFont = ! this.readableFont; this.update(); },
    toggleTextSpacing() { this.textSpacing = ! this.textSpacing; this.update(); },
    toggleEnhancedFocus() { this.enhancedFocus = ! this.enhancedFocus; this.update(); },
    toggleLargeCursor() { this.largeCursor = ! this.largeCursor; this.update(); },

    applyProfile(profile) {
        const profiles = {
            lowVision: { fontScale: 150, interfaceScale: 110, highContrast: true, enhancedFocus: true, largeCursor: true, readableFont: false, textSpacing: false, reducedMotion: false, colorFilter: 'none' },
            reading: { fontScale: 125, interfaceScale: 100, highContrast: false, enhancedFocus: true, largeCursor: false, readableFont: true, textSpacing: true, reducedMotion: true, colorFilter: 'none' },
            motor: { fontScale: 125, interfaceScale: 125, highContrast: false, enhancedFocus: true, largeCursor: true, readableFont: false, textSpacing: false, reducedMotion: true, colorFilter: 'none' },
            senior: { fontScale: 150, interfaceScale: 110, highContrast: true, enhancedFocus: true, largeCursor: true, readableFont: true, textSpacing: true, reducedMotion: true, colorFilter: 'none' },
        };
        if (! profiles[profile]) return;
        Object.assign(this, profiles[profile]);
        this.update();
        Alpine.store('toast')?.show('Perfil de accesibilidad aplicado.');
    },

    announceSummary() {
        const active = [
            `texto ${this.fontScale}%`,
            `interfaz ${this.interfaceScale}%`,
            this.highContrast ? 'alto contraste' : null,
            this.readableFont ? 'fuente legible' : null,
            this.textSpacing ? 'espaciado amplio' : null,
            this.largeCursor ? 'cursor grande' : null,
            this.reducedMotion ? 'movimiento reducido' : null,
            this.colorFilter !== 'none' ? `filtro ${this.colorFilter}` : null,
        ].filter(Boolean).join(', ');
        Alpine.store('toast')?.show(`Preferencias activas: ${active}.`);
    },

    reset() {
        this.fontScale = 100;
        this.interfaceScale = 100;
        this.colorFilter = 'none';
        this.highContrast = false;
        this.reducedMotion = prefersReducedMotion;
        this.readableFont = false;
        this.textSpacing = false;
        this.enhancedFocus = true;
        this.largeCursor = false;
        this.update();
        Alpine.store('toast')?.show('Preferencias de accesibilidad restablecidas.');
    },
});

Alpine.store('toast', {
    visible: false,
    message: '',
    type: 'success',
    timeout: null,

    show(message, type = 'success') {
        this.message = message;
        this.type = type;
        this.visible = true;
        window.clearTimeout(this.timeout);
        this.timeout = window.setTimeout(() => {
            this.visible = false;
        }, 4500);
    },
});

Alpine.store('accessibility').apply();
Alpine.data('lookupSelect', (config) => ({
    url: config.url,
    scope: config.scope,
    name: config.name,
    value: config.value,
    label: config.label,
    query: config.label,
    items: [],
    more: false,
    open: false,
    active: -1,
    status: '',
    controller: null,

    openList() {
        this.open = true;
        if (this.items.length === 0) this.search();
    },

    close() {
        this.open = false;
        this.active = -1;
    },

    onInput() {
        if (this.query.trim() === '' && this.value !== null) {
            this.setValue(null, '', null);
        }
        this.open = true;
        this.search();
    },

    onBlur() {
        // Si no se eligió ninguna opción, se restaura el texto de la selección vigente.
        window.setTimeout(() => {
            if (this.query !== this.label) this.query = this.label;
            this.close();
        }, 120);
    },

    async search() {
        this.controller?.abort();
        this.controller = new AbortController();
        this.status = 'Buscando…';
        const params = new URLSearchParams();
        const term = this.query === this.label ? '' : this.query.trim();
        if (term !== '') params.set('q', term);
        if (this.scope) params.set('scope', this.scope);

        try {
            const response = await fetch(`${this.url}?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: this.controller.signal,
            });
            if (! response.ok) throw new Error(String(response.status));
            const payload = await response.json();
            this.items = payload.data ?? [];
            this.more = Boolean(payload.more);
            this.active = this.items.length > 0 ? 0 : -1;
            this.status = this.items.length === 0
                ? 'Sin resultados.'
                : `${this.items.length} ${this.items.length === 1 ? 'resultado disponible' : 'resultados disponibles'}.`;
        } catch (error) {
            if (error.name === 'AbortError') return;
            this.items = [];
            this.more = false;
            this.status = 'No se pudo cargar la lista. Intenta de nuevo.';
        }
    },

    move(step) {
        if (! this.open) {
            this.openList();
            return;
        }
        if (this.items.length === 0) return;
        this.active = (this.active + step + this.items.length) % this.items.length;
        this.$nextTick(() => document.getElementById(`${config.id}-option-${this.active}`)?.scrollIntoView({ block: 'nearest' }));
    },

    choose(item) {
        if (! item) return;
        this.setValue(item.id, item.label, item);
        this.close();
    },

    clear() {
        this.setValue(null, '', null);
        this.items = [];
    },

    setValue(value, label, item) {
        this.value = value;
        this.label = label;
        this.query = label;
        this.$root.dispatchEvent(new CustomEvent('lookup:selected', { bubbles: true, detail: { name: this.name, item } }));
    },
}));

document.addEventListener('lookup:selected', (event) => {
    const { name, item } = event.detail;
    if (name !== 'vehicle_id' || ! item) return;

    const meta = item.meta ?? {};
    const category = document.querySelector('#vehicle_category_id');
    const rate = document.querySelector('#daily_rate');
    const mileage = document.querySelector('#opening_mileage');
    const deposit = document.querySelector('#deposit_amount');

    if (category && meta.category) category.value = String(meta.category);

    if (mileage) {
        // Apertura de alquiler: solo se completan los campos que sigan vacíos.
        if (! mileage.value && meta.mileage != null) mileage.value = meta.mileage;
        if (rate && ! rate.value && meta.rate) rate.value = meta.rate;
        if (deposit && ! deposit.value && meta.deposit) deposit.value = meta.deposit;
    } else if (rate && meta.rate) {
        rate.value = meta.rate;
    }
});

Alpine.start();

const chartTextColor = () => '#94a3b8';
const chartGridColor = () => 'rgba(148, 163, 184, .12)';
const charts = [];

const parseData = (element, attribute) => {
    try {
        return JSON.parse(element.dataset[attribute] ?? '[]');
    } catch {
        return [];
    }
};

const buildDashboardCharts = () => {
    const incomeCanvas = document.querySelector('#incomeChart');
    const fleetCanvas = document.querySelector('#fleetChart');

    if (incomeCanvas) {
        charts.push(new Chart(incomeCanvas, {
            type: 'line',
            data: {
                labels: parseData(incomeCanvas, 'labels'),
                datasets: [{
                    label: 'Ingresos',
                    data: parseData(incomeCanvas, 'values'),
                    borderColor: '#168ce8',
                    backgroundColor: 'rgba(22, 140, 232, .14)',
                    fill: true,
                    tension: 0.38,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: ! Alpine.store('accessibility').reducedMotion,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => `RD$ ${Number(context.raw).toLocaleString('es-DO', { minimumFractionDigits: 2 })}`,
                        },
                    },
                },
                scales: {
                    x: { ticks: { color: chartTextColor() }, grid: { display: false } },
                    y: { ticks: { color: chartTextColor() }, grid: { color: chartGridColor() }, beginAtZero: true },
                },
            },
        }));
    }

    if (fleetCanvas) {
        charts.push(new Chart(fleetCanvas, {
            type: 'doughnut',
            data: {
                labels: parseData(fleetCanvas, 'labels'),
                datasets: [{
                    data: parseData(fleetCanvas, 'values'),
                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: ! Alpine.store('accessibility').reducedMotion,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: chartTextColor(), usePointStyle: true, padding: 18 },
                    },
                },
            },
        }));
    }
};

const buildBarChart = (canvas, horizontal, color) => {
    if (! canvas) return;

    charts.push(new Chart(canvas, {
        type: 'bar',
        data: {
            labels: parseData(canvas, 'labels'),
            datasets: [{ data: parseData(canvas, 'values'), backgroundColor: color, borderRadius: 6, maxBarThickness: 28 }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            animation: ! Alpine.store('accessibility').reducedMotion,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: chartTextColor(), precision: 0 }, grid: { display: horizontal, color: chartGridColor() }, beginAtZero: true },
                y: { ticks: { color: chartTextColor(), precision: 0 }, grid: { display: ! horizontal, color: chartGridColor() }, beginAtZero: true },
            },
        },
    }));
};

const buildOperationCharts = () => {
    buildBarChart(document.querySelector('#reservationStatusChart'), false, '#3b82f6');
    buildBarChart(document.querySelector('#categoryChart'), true, '#10b981');
};

const refreshCharts = () => {
    charts.forEach((chart) => {
        chart.options.animation = ! Alpine.store('accessibility').reducedMotion;

        if (chart.options.scales?.x) {
            chart.options.scales.x.ticks.color = chartTextColor();
            chart.options.scales.y.ticks.color = chartTextColor();
            chart.options.scales.y.grid.color = chartGridColor();
        }

        if (chart.options.plugins?.legend) {
            chart.options.plugins.legend.labels.color = chartTextColor();
        }

        chart.update();
    });
};

const populateReservationRate = () => {
    const category = document.querySelector('#vehicle_category_id');
    const rate = document.querySelector('#daily_rate');
    if (! category || ! rate) return;
    const option = category.options[category.selectedIndex];
    if (option?.dataset.rate && ! rate.value) rate.value = option.dataset.rate;
};

document.querySelector('#vehicle_category_id')?.addEventListener('change', populateReservationRate);

const askConfirmation = (message, confirmLabel) => new Promise((resolve) => {
    const dialog = document.createElement('dialog');
    dialog.className = 'confirm-dialog';
    dialog.setAttribute('aria-labelledby', 'confirm-dialog-message');

    const body = document.createElement('div');
    body.className = 'p-6';
    const text = document.createElement('p');
    text.id = 'confirm-dialog-message';
    text.className = 'text-base font-bold text-white';
    text.textContent = message;
    const note = document.createElement('p');
    note.className = 'mt-2 text-sm text-slate-400';
    note.textContent = 'Esta acción quedará registrada.';
    body.append(text, note);

    const actions = document.createElement('div');
    actions.className = 'flex justify-end gap-3 border-t border-slate-800 px-6 py-4';
    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'btn-secondary';
    cancel.textContent = 'Cancelar';
    const accept = document.createElement('button');
    accept.type = 'button';
    accept.className = 'btn-danger';
    accept.textContent = confirmLabel;
    actions.append(cancel, accept);

    dialog.append(body, actions);
    document.body.append(dialog);

    let result = false;
    cancel.addEventListener('click', () => dialog.close());
    accept.addEventListener('click', () => { result = true; dialog.close(); });
    dialog.addEventListener('close', () => { dialog.remove(); resolve(result); });
    dialog.showModal();
    cancel.focus();
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (! (form instanceof HTMLFormElement) || ! form.dataset.confirm || form.dataset.confirmed === '1') return;

    event.preventDefault();
    const submitter = event.submitter;
    askConfirmation(form.dataset.confirm, form.dataset.confirmLabel || 'Confirmar').then((accepted) => {
        if (! accepted) return;
        form.dataset.confirmed = '1';
        form.requestSubmit(submitter ?? undefined);
    });
});

window.addEventListener('rentadrive:accessibility-changed', refreshCharts);
window.addEventListener('DOMContentLoaded', buildDashboardCharts);
window.addEventListener('DOMContentLoaded', buildOperationCharts);

if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Offline support is optional and must never block the application.
        });
    });
}

populateReservationRate();
