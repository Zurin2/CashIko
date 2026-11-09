import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // CashIko tokens (spec §5.1): cream base, forest brand, mango accent.
                surface: 'hsl(44 43% 96.5%)',
                forest: {
                   DEFAULT: 'hsl(145 48% 26%)',
                    dark: 'hsl(150 18% 14%)',
                    deep: 'hsl(150 18% 10%)',
                    muted: 'hsl(150 14% 31%)',
                },
                cream: 'hsl(44 30% 92%)',
                mango: 'hsl(26 88% 56%)',
                warm: 'hsl(44 22% 78%)',
            },
            boxShadow: {
                card: '0 1px 2px 0 hsl(145 48% 26% / 0.06), 0 4px 14px -4px hsl(145 48% 26% / 0.10)',
                cardlg: '0 8px 30px -6px hsl(145 48% 26% / 0.25)',
            },
        },
    },

    plugins: [forms],
};
