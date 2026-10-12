import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

const rentadriveUtilities = plugin(({ addUtilities }) => {
    addUtilities({
        '.focus-ring': {
            '&:focus': {
                outline: '2px solid transparent',
                outlineOffset: '2px',
            },
            '&:focus-visible': {
                '--tw-ring-offset-width': '2px',
                '--tw-ring-color': '#facc15',
                boxShadow: 'var(--tw-ring-inset,) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color)',
            },
        },
        '.panel': {
            borderRadius: '1rem',
            borderWidth: '1px',
            borderColor: '#e2e8f0',
            backgroundColor: '#ffffff',
            boxShadow: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        },
    });
});

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, rentadriveUtilities],
};
