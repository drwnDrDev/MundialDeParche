import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            colors: {
                navy:  '#0a1f3d',
                cream: '#f4ecd8',
                ink:   '#0e1320',
                pop: {
                    red:  '#ff3d3d',
                    yel:  '#ffd23f',
                    teal: '#00c2a8',
                },
            },
            fontFamily: {
                sans:    ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                display: ['Bungee', 'system-ui', 'sans-serif'],
                body:    ['"Space Grotesk"', 'system-ui', 'sans-serif'],
                pixel:   ['VT323', 'monospace'],
                mono:    ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
            },
            boxShadow: {
                'pop-sm':  '2px 2px 0 #0e1320',
                'pop':     '3px 3px 0 #0e1320',
                'pop-md':  '4px 4px 0 #0e1320',
                'pop-lg':  '5px 5px 0 #0e1320',
                'pop-xl':  '6px 6px 0 #0e1320',
                'pop-cta': '8px 8px 0 #0e1320',
            },
            borderWidth: {
                '2.5': '2.5px',
            },
        },
    },

    plugins: [forms],
};
