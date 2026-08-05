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

const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const storedTheme = safeStorageGet('rentadrive-theme');
const startsDark = storedTheme === 'dark' || (storedTheme === null && prefersDark);

Alpine.store('theme', {
    dark: startsDark,

    toggle() {
        this.dark = ! this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        safeStorageSet('rentadrive-theme', this.dark ? 'dark' : 'light');
        window.dispatchEvent(new CustomEvent('rentadrive:theme-changed'));
    },
});

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

document.documentElement.classList.toggle('dark', startsDark);
Alpine.store('accessibility').apply();
Alpine.start();

const chartTextColor = () => document.documentElement.classList.contains('dark') ? '#94a3b8' : '#475569';
const chartGridColor = () => document.documentElement.classList.contains('dark') ? 'rgba(148, 163, 184, .12)' : 'rgba(100, 116, 139, .14)';
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

const populateReservationVehicle = () => {
    const vehicle = document.querySelector('#vehicle_id');
    const category = document.querySelector('#vehicle_category_id');
    const rate = document.querySelector('#daily_rate');
    if (! vehicle || ! category || ! rate) return;
    const option = vehicle.options[vehicle.selectedIndex];
    if (option?.dataset.category) category.value = option.dataset.category;
    if (option?.dataset.rate) rate.value = option.dataset.rate;
};

const populateRentalVehicle = () => {
    const vehicle = document.querySelector('#vehicle_id');
    const mileage = document.querySelector('#opening_mileage');
    const rate = document.querySelector('#daily_rate');
    const deposit = document.querySelector('#deposit_amount');
    if (! vehicle || ! mileage || ! rate || ! deposit) return;
    const option = vehicle.options[vehicle.selectedIndex];
    if (option?.dataset.mileage && ! mileage.value) mileage.value = option.dataset.mileage;
    if (option?.dataset.rate && ! rate.value) rate.value = option.dataset.rate;
    if (option?.dataset.deposit && ! deposit.value) deposit.value = option.dataset.deposit;
};

document.querySelector('#vehicle_category_id')?.addEventListener('change', populateReservationRate);
document.querySelector('#vehicle_id')?.addEventListener('change', () => {
    populateReservationVehicle();
    populateRentalVehicle();
});

window.addEventListener('rentadrive:theme-changed', refreshCharts);
window.addEventListener('rentadrive:accessibility-changed', refreshCharts);
window.addEventListener('DOMContentLoaded', buildDashboardCharts);

if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Offline support is optional and must never block the application.
        });
    });
}

populateReservationRate();
populateRentalVehicle();
