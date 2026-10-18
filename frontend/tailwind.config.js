/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        ink: {
          DEFAULT: 'rgb(var(--c-ink) / <alpha-value>)',
          soft: 'rgb(var(--c-ink-soft) / <alpha-value>)',
          muted: 'rgb(var(--c-ink-muted) / <alpha-value>)',
          faint: 'rgb(var(--c-ink-faint) / <alpha-value>)',
        },
        surface: 'rgb(var(--c-surface) / <alpha-value>)',
        line: 'rgb(var(--c-line) / <alpha-value>)',
        primary: {
          DEFAULT: 'rgb(var(--c-primary) / <alpha-value>)',
          dark: 'rgb(var(--c-primary-dark) / <alpha-value>)',
          soft: 'rgb(var(--c-primary-soft) / <alpha-value>)',
          faint: 'rgb(var(--c-primary-faint) / <alpha-value>)',
        },
        emerald: {
          DEFAULT: '#059669',
          soft: '#D1FAE5',
        },
        amber: {
          DEFAULT: '#D97706',
          soft: '#FEF3C7',
        },
        danger: {
          DEFAULT: '#DC2626',
          soft: '#FEE2E2',
        },
      },
      fontFamily: {
        sans: ['"IBM Plex Sans Arabic"', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        card: '0 1px 2px rgba(41,37,36,.05), 0 1px 3px rgba(41,37,36,.06)',
        pop: '0 8px 30px rgba(41,37,36,.10)',
      },
      borderRadius: {
        xl2: '0.875rem',
      },
      maxWidth: {
        site: '1200px',
      },
    },
  },
  plugins: [],
};
