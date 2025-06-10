// Importar estilos CSS
import '../css/app.css';

// Importar jQuery y Select2
import jquery from 'jquery';
import 'select2';
import 'select2/dist/css/select2.min.css';

// Importar Swiper
import { Swiper, Navigation, Pagination, Autoplay, EffectFade } from 'swiper';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/autoplay';
import 'swiper/css/effect-fade';

// Configurar módulos de Swiper
Swiper.use([Navigation, Pagination, Autoplay, EffectFade]);

// Importar Chart.js
import Chart from 'chart.js/auto';

// Hacer jQuery, Chart.js y Swiper disponibles globalmente
window.$ = window.jQuery = jquery;
window.Chart = Chart;
window.Swiper = Swiper;

// Código JavaScript adicional aquí si es necesario
