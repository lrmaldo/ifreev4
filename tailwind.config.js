/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./vendor/livewire/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: 'rgb(255, 63, 0)',
          50: '#fff5f0',
          100: '#ffece3',
          200: '#ffd5c2',
          300: '#ffb399',
          400: '#ff8056',
          500: '#ff5e2c',
          600: '#ff3f00', // El color primario que especificaste
          700: '#e63300',
          800: '#cc2d00',
          900: '#a82900',
          950: '#591100',
        },
        // Otros colores si los necesitas
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
};
