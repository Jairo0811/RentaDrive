import Alpine from 'alpinejs';

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
    },
});

document.documentElement.classList.toggle('dark', startsDark);
Alpine.start();

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

populateReservationRate();
populateRentalVehicle();
