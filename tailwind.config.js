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
            },
            colors: {
                primary: {
                    DEFAULT: '#FFC107',
                    hover: '#FFB300',
                    light: '#FFE082',
                    dark: '#FF8F00',
                },
                secondary: {
                    DEFAULT: '#FFA726',
                    hover: '#FF9800',
                    light: '#FFCC80',
                    dark: '#EF6C00',
                },
                surface: '#FFFFFF',
                background: '#F8F9FA',
                'text-primary': '#212529',
                'text-muted': '#6C757D',
                'border-light': '#E9ECEF',
            },
            boxShadow: {
                'card': '0 2px 12px rgba(0, 0, 0, 0.08)',
                'card-hover': '0 8px 24px rgba(255, 193, 7, 0.15)',
                'button': '0 4px 12px rgba(255, 193, 7, 0.25)',
                'button-hover': '0 6px 16px rgba(255, 167, 38, 0.35)',
                'glow': '0 0 20px rgba(255, 193, 7, 0.3)',
            },
            transitionDuration: {
                'fast': '150ms',
                'normal': '250ms',
                'slow': '350ms',
            },
            borderRadius: {
                'xl': '16px',
                '2xl': '24px',
            },
            keyframes: {
                'fade-in': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'slide-up': {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                'bounce-soft': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-5px)' },
                },
                'pulse-glow': {
                    '0%, 100%': { boxShadow: '0 0 20px rgba(255, 193, 7, 0.3)' },
                    '50%': { boxShadow: '0 0 30px rgba(255, 193, 7, 0.5)' },
                },
            },
            animation: {
                'fade-in': 'fade-in 0.3s ease-out',
                'slide-up': 'slide-up 0.4s ease-out',
                'scale-in': 'scale-in 0.25s ease-out',
                'bounce-soft': 'bounce-soft 2s ease-in-out infinite',
                'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
