/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Livewire/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        surface: {
          DEFAULT: '#ffffff',
          dim: '#dbd9dc',
          bright: '#faf9fc',
          container: {
            lowest: '#ffffff',
            low: '#f5f3f6',
            DEFAULT: '#efedf0',
            high: '#e9e7ea',
            highest: '#e3e2e5',
          },
          tint: '#4a5f7f',
          variant: '#e3e2e5',
        },
        'surface-tint': '#4a5f7f',
        'inverse-surface': '#2e3135',
        'inverse-on-surface': '#f0f0f4',
        'inverse-primary': '#aac7ff',
        'primary-fixed-dim': '#b2c8ed',
        'secondary-fixed-dim': '#f0bf5c',
        primary: {
          DEFAULT: '#001229',
          container: '#0f2744',
          fixed: {
            DEFAULT: '#d4e3ff',
            dim: '#b2c8ed',
          }
        },
        secondary: {
          DEFAULT: '#7b5900',
          container: '#fcca66',
          fixed: {
            DEFAULT: '#ffdea4',
            dim: '#f0bf5c',
          }
        },
        tertiary: {
          DEFAULT: '#1e0e00',
          container: '#3b2000',
        },
        error: {
          DEFAULT: '#ba1a1a',
          container: '#ffdad6',
        },
        success: {
          DEFAULT: '#148545',
          container: '#d1e7dd',
          'on-container': '#0f5132',
        },
        background: '#faf9fc',
        'on-background': '#191c20',
        'on-surface': '#191c20',
        'on-surface-variant': '#44474e',
        'on-primary': '#ffffff',
        'on-secondary': '#ffffff',
        'on-error': '#ffffff',
        'outline': '#74777f',
        'outline-variant': '#c4c6d0',
        gold: {
          DEFAULT: '#C89B3C',
          accent: '#FCCA66',
        },
        navy: {
          DEFAULT: '#0F2744',
          dark: '#001229',
        }
      },
      fontSize: {
        'display-lg': ['3.5rem', { lineHeight: '4.25rem', fontWeight: '700' }],
        'display-lg-mobile': ['2.5rem', { lineHeight: '3.25rem', fontWeight: '700' }],
        'headline-lg': ['2rem', { lineHeight: '2.5rem', fontWeight: '700' }],
        'headline-md': ['1.5rem', { lineHeight: '2rem', fontWeight: '600' }],
        'title-lg': ['1.25rem', { lineHeight: '1.75rem', fontWeight: '600' }],
        'body-lg': ['1.125rem', { lineHeight: '1.75rem', fontWeight: '400' }],
        'body-md': ['1rem', { lineHeight: '1.5rem', fontWeight: '400' }],
        'label-lg': ['0.875rem', { lineHeight: '1.25rem', fontWeight: '600' }],
        'label-sm': ['0.75rem', { lineHeight: '1.125rem', fontWeight: '600' }],
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
      spacing: {
        'base': '4px',
        'xs': '4px',
        'sm': '8px',
        'md': '14px',
        'lg': '20px',
        'xl': '24px',
        'gutter': '24px',
      },
      borderRadius: {
        'sm': '0.25rem',
        DEFAULT: '0.5rem',
        'md': '0.75rem',
        'lg': '1rem',
        'xl': '1.5rem',
        'full': '9999px',
      },
      boxShadow: {
        'ambient': '0px 4px 20px rgba(15, 39, 68, 0.05)',
      }
    },
  },
  plugins: [],
}
