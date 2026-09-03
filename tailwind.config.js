import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Base: #0F172A page, #1E293B surfaces
                ink: {
                    DEFAULT: '#0F172A',
                    950: '#080D18',
                    900: '#0F172A',
                    800: '#1E293B',
                    700: '#334155',
                },
                // Primary: #0D9488 / #14B8A6
                brand: {
                    900: '#134E4A',
                    800: '#115E59',
                    700: '#0F766E',
                    600: '#0D9488',
                    500: '#14B8A6',
                    400: '#2DD4BF',
                    300: '#5EEAD4',
                    200: '#99F6E4',
                    100: '#CCFBF1',
                },
                // Accent / danger: #F43F5E
                flare: {
                    900: '#881337',
                    800: '#9F1239',
                    700: '#BE123C',
                    600: '#E11D48',
                    500: '#F43F5E',
                    400: '#FB7185',
                    300: '#FDA4AF',
                    200: '#FECDD3',
                    100: '#FFE4E6',
                },
            },

            boxShadow: {
                glow: '0 0 0 1px rgba(20, 184, 166, .35), 0 8px 30px -10px rgba(13, 148, 136, .55)',
                'glow-sm': '0 0 18px -6px rgba(20, 184, 166, .55)',
                'glow-flare': '0 0 22px -8px rgba(244, 63, 94, .6)',
                card: '0 1px 2px rgba(0, 0, 0, .35), 0 12px 32px -18px rgba(0, 0, 0, .9)',
                lift: '0 2px 4px rgba(0, 0, 0, .3), 0 24px 48px -24px rgba(0, 0, 0, .95)',
            },

            backgroundImage: {
                'brand-gradient': 'linear-gradient(135deg, #14B8A6 0%, #0D9488 100%)',
                'brand-text': 'linear-gradient(100deg, #5EEAD4 0%, #14B8A6 45%, #0D9488 100%)',
                'flare-gradient': 'linear-gradient(135deg, #FB7185 0%, #F43F5E 100%)',
                grid: 'linear-gradient(rgba(148,163,184,.045) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,.045) 1px, transparent 1px)',
            },

            backgroundSize: {
                grid: '44px 44px',
            },

            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                sweep: {
                    '0%': { transform: 'rotate(0deg)' },
                    '100%': { transform: 'rotate(360deg)' },
                },
                'pulse-ring': {
                    '0%': { transform: 'scale(.85)', opacity: '.6' },
                    '70%': { transform: 'scale(1.35)', opacity: '0' },
                    '100%': { transform: 'scale(1.35)', opacity: '0' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },

            animation: {
                'fade-up': 'fade-up .5s cubic-bezier(.21,1,.21,1) both',
                sweep: 'sweep 4s linear infinite',
                'pulse-ring': 'pulse-ring 2.4s cubic-bezier(.24,.8,.3,1) infinite',
                shimmer: 'shimmer 2.5s linear infinite',
            },
        },
    },

    plugins: [forms],
};
