import defaultTheme from 'tailwindcss-preset-carbon';
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
                sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
                serif: ['Playfair Display', 'Georgia', 'serif'],
                display: ['Playfair Display', 'Georgia', 'serif'],
                body: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                rose: {
                    50: '#FFF5F7',
                    100: '#FFEAEF',
                    200: '#FDD5DF',
                    300: '#F9B0C3',
                    400: '#F08DA8',
                    500: '#E3607F',
                    600: '#C94368',
                    700: '#A42E52',
                    800: '#812344',
                    900: '#5F1A38',
                    gold: '#B76E79',
                },
                champagne: {
                    50: '#FDF9F0',
                    100: '#FBF0DC',
                    200: '#F6E0B8',
                    300: '#EFC98A',
                    400: '#E5AD5A',
                    500: '#C9A96E',
                    600: '#A88840',
                    700: '#856A30',
                    800: '#6B5428',
                    900: '#4E3D1E',
                },
                cream: {
                    DEFAULT: '#FFFAF5',
                    50: '#FFFFFF',
                    100: '#FFFCF9',
                    200: '#FFF8F0',
                    300: '#FFF3E6',
                    400: '#FFEED9',
                    500: '#FDE8CC',
                },
                plum: {
                    DEFAULT: '#3D1F2B',
                    50: '#F8E8EE',
                    100: '#E8C4D1',
                    200: '#D49DB3',
                    300: '#B87493',
                    400: '#994D74',
                    500: '#7A2D58',
                    600: '#5F1A38',
                    700: '#4B142D',
                    800: '#3D1F2B',
                    900: '#2A0F1C',
                },
                surface: {
                    50: '#FFFFFF',
                    100: '#FEFCFA',
                    200: '#FBF6F0',
                    300: '#F5EDE3',
                    400: '#E8DDD1',
                    500: '#B8A99A',
                    600: '#8C7D6E',
                    700: '#655849',
                    800: '#443A2E',
                    900: '#2C231A',
                },
            },
            boxShadow: {
                soft: '0 1px 3px rgba(183, 110, 121, 0.06), 0 4px 12px rgba(0,0,0,0.03)',
                card: '0 2px 8px rgba(183, 110, 121, 0.06), 0 1px 3px rgba(0,0,0,0.04)',
                'card-hover': '0 8px 24px rgba(183, 110, 121, 0.1), 0 2px 8px rgba(0,0,0,0.04)',
                elevated: '0 12px 40px rgba(183, 110, 121, 0.12), 0 4px 12px rgba(0,0,0,0.06)',
                glow: '0 0 20px rgba(183, 110, 121, 0.15)',
                'glow-lg': '0 0 40px rgba(183, 110, 121, 0.2)',
                inner: 'inset 0 1px 3px rgba(0,0,0,0.04)',
            },
            backgroundImage: {
                'gradient-rose': 'linear-gradient(135deg, #B76E79 0%, #E3607F 100%)',
                'gradient-gold': 'linear-gradient(135deg, #C9A96E 0%, #E5AD5A 100%)',
                'gradient-plum': 'linear-gradient(135deg, #3D1F2B 0%, #5F1A38 100%)',
                'gradient-cream': 'linear-gradient(135deg, #FFFAF5 0%, #FFF5F7 50%, #FDF9F0 100%)',
                'gradient-subtle': 'linear-gradient(180deg, rgba(255,250,245,0) 0%, rgba(183,110,121,0.03) 100%)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.25rem',
                '4xl': '1.5rem',
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-out',
                'slide-up': 'slideUp 0.4s ease-out',
                'slide-right': 'slideRight 0.3s ease-out',
                'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                shimmer: 'shimmer 2s linear infinite',
                float: 'float 6s ease-in-out infinite',
                'scale-in': 'scaleIn 0.3s ease-out',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideRight: {
                    '0%': { opacity: '0', transform: 'translateX(-20px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.6' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
            },
        },
    },
    plugins: [forms],
};
