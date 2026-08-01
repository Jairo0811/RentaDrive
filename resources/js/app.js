import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
let storedTheme = null;

try {
    storedTheme = localStorage.getItem('rentadrive-theme');
} catch {
    // Browser storage can be disabled without breaking the interface.
}

const startsDark = storedTheme === 'dark' || (storedTheme === null && prefersDark);

Alpine.store('theme', {
    dark: startsDark,

    toggle() {
        this.dark = ! this.dark;
        document.documentElement.classList.toggle('dark', this.dark);

        try {
            localStorage.setItem('rentadrive-theme', this.dark ? 'dark' : 'light');
        } catch {
            // Keep the current theme even when the preference cannot be persisted.
        }

        window.dispatchEvent(new CustomEvent('rentadrive:theme-changed'));
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

const refreshChartTheme = () => {
    charts.forEach((chart) => {
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

    if (! category || ! rate) {
        return;
    }

    const option = category.options[category.selectedIndex];

    if (option?.dataset.rate && ! rate.value) {
        rate.value = option.dataset.rate;
    }
};

const populateReservationVehicle = () => {
    const vehicle = document.querySelector('#vehicle_id');
    const category = document.querySelector('#vehicle_category_id');
    const rate = document.querySelector('#daily_rate');

    if (! vehicle || ! category || ! rate) {
        return;
    }

    const option = vehicle.options[vehicle.selectedIndex];

    if (option?.dataset.category) {
        category.value = option.dataset.category;
    }

    if (option?.dataset.rate) {
        rate.value = option.dataset.rate;
    }
};

const populateRentalVehicle = () => {
    const vehicle = document.querySelector('#vehicle_id');
    const mileage = document.querySelector('#opening_mileage');
    const rate = document.querySelector('#daily_rate');
    const deposit = document.querySelector('#deposit_amount');

    if (! vehicle || ! mileage || ! rate || ! deposit) {
        return;
    }

    const option = vehicle.options[vehicle.selectedIndex];

    if (option?.dataset.mileage && ! mileage.value) {
        mileage.value = option.dataset.mileage;
    }

    if (option?.dataset.rate && ! rate.value) {
        rate.value = option.dataset.rate;
    }

    if (option?.dataset.deposit && ! deposit.value) {
        deposit.value = option.dataset.deposit;
    }
};

document.querySelector('#vehicle_category_id')?.addEventListener('change', populateReservationRate);
document.querySelector('#vehicle_id')?.addEventListener('change', () => {
    populateReservationVehicle();
    populateRentalVehicle();
});

window.addEventListener('rentadrive:theme-changed', refreshChartTheme);
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
