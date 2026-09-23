/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/**/*.latte",
    "./app/**/*.php",
    "./www/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0075b5',
          light: '#0fa6db',
          dark: '#005a8c',
        },
        accent: {
          DEFAULT: '#ffd600',
          hover: '#e6c200',
        },
        forest: {
          DEFAULT: '#009043',
          light: '#78ba63',
        },
        earth: {
          DEFAULT: '#764c24',
          light: '#a67c52',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Montserrat', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
      },
      boxShadow: {
        'card': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        'card-hover': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
      },
    },
  },
  plugins: [],
}
