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
