// Importar estilos CSS
import '../css/app.css';

// Importar jQuery y Select2
import jquery from 'jquery';
import 'select2';
import 'select2/dist/css/select2.min.css';

// Importar Chart.js
import Chart from 'chart.js/auto';

// Hacer jQuery y Chart.js disponibles globalmente
window.$ = window.jQuery = jquery;
window.Chart = Chart;

// Nota: Swiper se usa localmente desde public/js/swiper-local.js
// No necesitamos importarlo aquí ya que se carga directamente en las vistas

// Código JavaScript adicional aquí si es necesario
