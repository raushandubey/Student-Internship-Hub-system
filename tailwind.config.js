/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#007bff',
          dark: '#0056b3',
          light: '#e7f1ff',
          50: '#f5f7ff',
          100: '#ebf0ff',
          200: '#d6e0ff',
          300: '#b3c7ff',
          400: '#8ca5ff',
          500: '#007bff',
          600: '#0056b3',
          700: '#004494',
          800: '#003275',
          900: '#002056',
        },
        success: {
          DEFAULT: '#28a745',
          light: '#d4edda',
          dark: '#1e7e34',
        },
        warning: {
          DEFAULT: '#ffc107',
          light: '#fff3cd',
          dark: '#d39e00',
        },
        danger: {
          DEFAULT: '#dc3545',
          light: '#f8d7da',
          dark: '#bd2130',
        },
        info: {
          DEFAULT: '#17a2b8',
          light: '#d1ecf1',
          dark: '#117a8b',
        },
        // Application status colors
        pending: '#ffc107',
        'under-review': '#007bff',
        shortlisted: '#8b5cf6',
        interview: '#6366f1',
        approved: '#28a745',
        rejected: '#dc3545',
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
      },
      borderRadius: {
        'card': '0.75rem',  // 12px
        'button': '0.5rem', // 8px
      },
      boxShadow: {
        'card': '0 2px 8px rgba(0, 0, 0, 0.1)',
        'card-hover': '0 4px 16px rgba(0, 0, 0, 0.15)',
        'nav': '0 -2px 8px rgba(0, 0, 0, 0.1)',
      },
      fontFamily: {
        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
      },
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1.25' }],
        'sm': ['0.875rem', { lineHeight: '1.5' }],
        'base': ['1rem', { lineHeight: '1.5' }],
        'lg': ['1.125rem', { lineHeight: '1.5' }],
        'xl': ['1.25rem', { lineHeight: '1.25' }],
        '2xl': ['1.5rem', { lineHeight: '1.25' }],
        '3xl': ['1.875rem', { lineHeight: '1.25' }],
      },
      minHeight: {
        'touch': '44px',
      },
      minWidth: {
        'touch': '44px',
      },
    },
    // Responsive breakpoints
    screens: {
      'sm': '640px',
      'md': '768px',   // Tablet
      'lg': '1024px',  // Desktop
      'xl': '1280px',
      '2xl': '1536px',
    },
  },
  plugins: [
    // Custom utilities plugin
    function({ addUtilities }) {
      const newUtilities = {
        '.touch-target': {
          minWidth: '44px',
          minHeight: '44px',
        },
        '.safe-area-bottom': {
          paddingBottom: 'env(safe-area-inset-bottom)',
        },
        '.card-base': {
          backgroundColor: 'white',
          borderRadius: '0.75rem',
          padding: '1rem',
          boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
        },
        '.btn-base': {
          display: 'inline-flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '0.75rem 1.5rem',
          fontSize: '1rem',
          fontWeight: '600',
          borderRadius: '0.5rem',
          transition: 'all 0.2s ease',
          minHeight: '44px',
          minWidth: '44px',
          cursor: 'pointer',
        },
      }
      addUtilities(newUtilities)
    },
  ],
}