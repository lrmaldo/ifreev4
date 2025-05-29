/**
 * CarruselCampanas.js
 * Script para mostrar el carrusel de campañas en el portal cautivo.
 *
 * Uso:
 * 1. Incluir este script en el HTML del portal cautivo
 * 2. Agregar un contenedor con id="campanas-carrusel"
 * 3. Llamar a inicializarCarrusel('ID_DE_LA_ZONA') con el ID de la zona
 */

class CarruselCampanas {
    constructor(zonaId, contenedorId = 'campanas-carrusel') {
        this.zonaId = zonaId;
        this.contenedorId = contenedorId;
        this.contenedor = document.getElementById(contenedorId);
        this.campanas = [];
        this.currentIndex = 0;
        this.autoplayInterval = 5000; // 5 segundos por slide
        this.autoplayTimer = null;
        this.urlBase = window.location.origin; // Para pruebas locales
    }

    /**
     * Carga las campañas activas desde el servidor
     */
    async cargarCampanas() {
        try {
            const response = await fetch(`${this.urlBase}/portal-cautivo/${this.zonaId}/campanas`);
            const data = await response.json();

            if (data.success && data.data && data.data.length > 0) {
                this.campanas = data.data;
                this.renderizarCarrusel();
                this.iniciarAutoplay();
                return true;
            } else {
                console.log('No hay campañas activas');
                this.mostrarMensajeVacio();
                return false;
            }
        } catch (error) {
            console.error('Error al cargar las campañas:', error);
            this.mostrarMensajeVacio();
            return false;
        }
    }

    /**
     * Renderiza el carrusel en el contenedor
     */
    renderizarCarrusel() {
        if (!this.contenedor) {
            console.error(`No se encontró el contenedor con id "${this.contenedorId}"`);
            return;
        }

        // Estructura principal
        this.contenedor.innerHTML = `
            <div class="carrusel-container">
                <div class="carrusel-slides"></div>
                ${this.campanas.length > 1 ? this.renderizarControles() : ''}
                ${this.campanas.length > 1 ? this.renderizarIndicadores() : ''}
            </div>
        `;

        const slidesContainer = this.contenedor.querySelector('.carrusel-slides');

        // Agregar cada slide
        this.campanas.forEach((campana, index) => {
            const slide = document.createElement('div');
            slide.className = `carrusel-slide ${index === 0 ? 'active' : ''}`;
            slide.dataset.index = index;

            if (campana.tipo === 'imagen') {
                const enlaceWrapper = document.createElement('div');
                enlaceWrapper.className = 'slide-content';

                if (campana.enlace) {
                    const enlace = document.createElement('a');
                    enlace.href = campana.enlace;
                    enlace.target = '_blank';
                    enlace.className = 'slide-link';

                    const imagen = document.createElement('img');
                    imagen.src = campana.archivo_url;
                    imagen.alt = campana.titulo;
                    imagen.className = 'slide-image';

                    enlace.appendChild(imagen);
                    enlaceWrapper.appendChild(enlace);
                } else {
                    const imagen = document.createElement('img');
                    imagen.src = campana.archivo_url;
                    imagen.alt = campana.titulo;
                    imagen.className = 'slide-image';

                    enlaceWrapper.appendChild(imagen);
                }

                slide.appendChild(enlaceWrapper);
            } else {
                // Video
                const videoWrapper = document.createElement('div');
                videoWrapper.className = 'slide-content';

                const video = document.createElement('video');
                video.src = campana.archivo_url;
                video.className = 'slide-video';
                video.muted = true;
                video.loop = true;
                video.playsInline = true;

                if (index === 0) {
                    video.autoplay = true;
                }

                videoWrapper.appendChild(video);

                if (campana.enlace) {
                    const enlace = document.createElement('a');
                    enlace.href = campana.enlace;
                    enlace.target = '_blank';
                    enlace.className = 'slide-overlay';
                    videoWrapper.appendChild(enlace);
                }

                slide.appendChild(videoWrapper);
            }

            // Overlay de texto
            const textOverlay = document.createElement('div');
            textOverlay.className = 'slide-text-overlay';
            textOverlay.innerHTML = `
                <h3 class="slide-title">${campana.titulo}</h3>
                ${campana.descripcion ? `<p class="slide-description">${campana.descripcion}</p>` : ''}
            `;

            slide.appendChild(textOverlay);
            slidesContainer.appendChild(slide);
        });

        // Configurar event listeners
        if (this.campanas.length > 1) {
            this.configurarEventListeners();
        }
    }

    /**
     * Renderiza los controles de navegación (flechas prev/next)
     */
    renderizarControles() {
        return `
            <button class="carrusel-control carrusel-prev" aria-label="Anterior">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button class="carrusel-control carrusel-next" aria-label="Siguiente">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        `;
    }

    /**
     * Renderiza los indicadores de navegación (puntos)
     */
    renderizarIndicadores() {
        let indicadores = '<div class="carrusel-indicadores">';

        for (let i = 0; i < this.campanas.length; i++) {
            indicadores += `<button class="carrusel-indicador ${i === 0 ? 'active' : ''}" data-slide="${i}" aria-label="Ir al slide ${i + 1}"></button>`;
        }

        indicadores += '</div>';
        return indicadores;
    }

    /**
     * Configura los event listeners para la navegación
     */
    configurarEventListeners() {
        const prevBtn = this.contenedor.querySelector('.carrusel-prev');
        const nextBtn = this.contenedor.querySelector('.carrusel-next');
        const indicadores = this.contenedor.querySelectorAll('.carrusel-indicador');

        // Botón anterior
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.prevSlide();
        });

        // Botón siguiente
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.nextSlide();
        });

        // Indicadores
        indicadores.forEach(indicador => {
            indicador.addEventListener('click', (e) => {
                e.preventDefault();
                const slideIndex = parseInt(indicador.dataset.slide);
                this.goToSlide(slideIndex);
            });
        });

        // Pausar/reanudar autoplay al hover
        this.contenedor.addEventListener('mouseenter', () => this.detenerAutoplay());
        this.contenedor.addEventListener('mouseleave', () => this.iniciarAutoplay());

        // Gestionar videos
        const videos = this.contenedor.querySelectorAll('video');
        videos.forEach(video => {
            video.addEventListener('play', () => {
                // Asegurarse de que otros videos estén pausados
                videos.forEach(v => {
                    if (v !== video && !v.paused) {
                        v.pause();
                    }
                });
            });
        });
    }

    /**
     * Ir al slide anterior
     */
    prevSlide() {
        if (this.campanas.length <= 1) return;

        this.currentIndex = (this.currentIndex - 1 + this.campanas.length) % this.campanas.length;
        this.actualizarSlides();
        this.reiniciarAutoplay();
    }

    /**
     * Ir al slide siguiente
     */
    nextSlide() {
        if (this.campanas.length <= 1) return;

        this.currentIndex = (this.currentIndex + 1) % this.campanas.length;
        this.actualizarSlides();
        this.reiniciarAutoplay();
    }

    /**
     * Ir a un slide específico
     */
    goToSlide(index) {
        if (index >= 0 && index < this.campanas.length) {
            this.currentIndex = index;
            this.actualizarSlides();
            this.reiniciarAutoplay();
        }
    }

    /**
     * Actualiza la visualización de los slides
     */
    actualizarSlides() {
        // Actualizar la clase active en slides
        const slides = this.contenedor.querySelectorAll('.carrusel-slide');
        slides.forEach((slide, index) => {
            if (index === this.currentIndex) {
                slide.classList.add('active');

                // Si es un video, reproducirlo
                const video = slide.querySelector('video');
                if (video) {
                    video.currentTime = 0;
                    video.play();
                }
            } else {
                slide.classList.remove('active');

                // Si es un video, pausarlo
                const video = slide.querySelector('video');
                if (video) {
                    video.pause();
                    video.currentTime = 0;
                }
            }
        });

        // Actualizar la clase active en indicadores
        const indicadores = this.contenedor.querySelectorAll('.carrusel-indicador');
        indicadores.forEach((indicador, index) => {
            if (index === this.currentIndex) {
                indicador.classList.add('active');
            } else {
                indicador.classList.remove('active');
            }
        });
    }

    /**
     * Inicia el autoplay del carrusel
     */
    iniciarAutoplay() {
        if (this.campanas.length <= 1) return;

        if (this.autoplayTimer) {
            this.detenerAutoplay();
        }

        this.autoplayTimer = setInterval(() => {
            this.nextSlide();
        }, this.autoplayInterval);
    }

    /**
     * Detiene el autoplay del carrusel
     */
    detenerAutoplay() {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    /**
     * Reinicia el autoplay (útil después de una interacción manual)
     */
    reiniciarAutoplay() {
        this.detenerAutoplay();
        this.iniciarAutoplay();
    }

    /**
     * Muestra un mensaje cuando no hay campañas disponibles
     */
    mostrarMensajeVacio() {
        if (!this.contenedor) return;

        this.contenedor.innerHTML = `
            <div class="carrusel-vacio">
                <p>No hay contenido disponible en este momento.</p>
            </div>
        `;
    }
}

/**
 * Función para inicializar el carrusel desde el exterior
 */
function inicializarCarrusel(zonaId, contenedorId = 'campanas-carrusel') {
    const carrusel = new CarruselCampanas(zonaId, contenedorId);
    carrusel.cargarCampanas();
    return carrusel;
}

// Estilos CSS a inyectar
const estilosCarrusel = `
.carrusel-container {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    border-radius: 8px;
}

.carrusel-slides {
    position: relative;
    width: 100%;
    height: 100%;
}

.carrusel-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease;
    overflow: hidden;
}

.carrusel-slide.active {
    opacity: 1;
    z-index: 1;
}

.slide-content {
    position: relative;
    width: 100%;
    height: 100%;
}

.slide-image, .slide-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.slide-link, .slide-overlay {
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    cursor: pointer;
}

.slide-text-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px;
    background: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0));
    color: white;
    z-index: 3;
}

.slide-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 4px;
}

.slide-description {
    font-size: 14px;
    margin: 0;
}

.carrusel-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255,255,255,0.3);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    z-index: 10;
    transition: background-color 0.3s ease;
}

.carrusel-control:hover {
    background-color: rgba(255,255,255,0.5);
}

.carrusel-prev {
    left: 10px;
}

.carrusel-next {
    right: 10px;
}

.carrusel-control svg {
    width: 20px;
    height: 20px;
}

.carrusel-indicadores {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 10;
}

.carrusel-indicador {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: rgba(255,255,255,0.5);
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.carrusel-indicador.active {
    background-color: white;
}

.carrusel-vacio {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background-color: #f5f5f5;
    color: #666;
    font-size: 14px;
}

@media (max-width: 640px) {
    .slide-title {
        font-size: 16px;
    }

    .slide-description {
        font-size: 12px;
    }

    .carrusel-control {
        width: 32px;
        height: 32px;
    }

    .carrusel-control svg {
        width: 16px;
        height: 16px;
    }
}
`;

// Inyectar estilos CSS
function inyectarEstilos() {
    const styleElement = document.createElement('style');
    styleElement.textContent = estilosCarrusel;
    document.head.appendChild(styleElement);
}

// Inyectar estilos cuando se carga el documento
document.addEventListener('DOMContentLoaded', inyectarEstilos);
