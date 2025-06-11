/**
 * Swiper Local - Implementación mínima para portal cautivo
 * Funcionalidad básica de carrusel sin dependencias externas
 */

class SwiperLocal {
    constructor(selector, options = {}) {
        this.container = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!this.container) return;

        this.options = {
            loop: false,
            autoplay: false,
            pagination: true,
            allowTouchMove: true,
            effect: 'slide',
            fadeEffect: { crossFade: true },
            ...options
        };

        this.wrapper = this.container.querySelector('.swiper-wrapper');
        this.slides = this.container.querySelectorAll('.swiper-slide');
        this.currentIndex = 0;
        this.isAutoplayRunning = false;
        this.autoplayTimer = null;

        this.init();
    }

    init() {
        if (!this.wrapper) {
            console.error('SwiperLocal: No se encontró el wrapper');
            return;
        }

        if (this.slides.length === 0) {
            console.error('SwiperLocal: No se encontraron diapositivas');
            return;
        }

        console.log(`SwiperLocal: Inicializando con ${this.slides.length} diapositivas`);

        // Añadir clase para identificar el modo de efecto
        if (this.options.effect === 'fade') {
            this.container.classList.add('swiper-container-fade');
        }

        this.createPagination();
        this.setupTouchEvents();
        this.setupTransitions();

        // Asegurar que las imágenes se han cargado antes de iniciar el autoplay
        this.preloadImages().then(() => {
            if (this.options.autoplay && this.options.autoplay.delay && this.slides.length > 1) {
                this.startAutoplay();
            }

            // Mostrar primera slide
            this.goToSlide(0);
        });
    }

    /**
     * Precargar imágenes para evitar problemas de visualización
     */
    preloadImages() {
        console.log('SwiperLocal: Precargando imágenes...');
        const promises = Array.from(this.slides).map((slide, index) => {
            return new Promise((resolve) => {
                const img = slide.querySelector('img');
                if (img) {
                    if (img.complete) {
                        console.log(`SwiperLocal: Imagen ${index} ya cargada`);
                        resolve();
                    } else {
                        console.log(`SwiperLocal: Cargando imagen ${index}`);
                        img.onload = () => {
                            console.log(`SwiperLocal: Imagen ${index} cargada correctamente`);
                            resolve();
                        };
                        img.onerror = () => {
                            console.warn(`SwiperLocal: Error al cargar la imagen ${index}`);
                            resolve();
                        };
                    }
                } else {
                    resolve();
                }
            });
        });

        return Promise.all(promises);
    }

    createPagination() {
        if (!this.options.pagination || this.slides.length <= 1) return;

        const paginationEl = this.container.querySelector('.swiper-pagination');
        if (!paginationEl) return;

        paginationEl.innerHTML = '';

        for (let i = 0; i < this.slides.length; i++) {
            const bullet = document.createElement('span');
            bullet.className = 'swiper-pagination-bullet';
            bullet.addEventListener('click', () => this.goToSlide(i));
            paginationEl.appendChild(bullet);
        }

        this.paginationBullets = paginationEl.querySelectorAll('.swiper-pagination-bullet');
    }

    setupTouchEvents() {
        if (!this.options.allowTouchMove) return;

        let startX = 0;
        let startY = 0;
        let diffX = 0;
        let diffY = 0;

        this.container.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });

        this.container.addEventListener('touchmove', (e) => {
            if (!startX || !startY) return;

            diffX = startX - e.touches[0].clientX;
            diffY = startY - e.touches[0].clientY;
        });

        this.container.addEventListener('touchend', () => {
            if (!diffX || !diffY) return;

            if (Math.abs(diffX) > Math.abs(diffY)) {
                if (diffX > 0) {
                    this.slideNext();
                } else {
                    this.slidePrev();
                }
            }

            startX = 0;
            startY = 0;
            diffX = 0;
            diffY = 0;
        });
    }

    setupTransitions() {
        if (this.options.effect === 'fade') {
            // Establecer el contenedor con posicionamiento relativo para que los slides absolutos funcionen bien
            this.wrapper.style.position = 'relative';
            this.wrapper.style.overflow = 'hidden';
            this.wrapper.style.width = '100%';
            this.wrapper.style.height = '100%';

            this.slides.forEach((slide, index) => {
                slide.style.position = 'absolute';
                slide.style.top = '0';
                slide.style.left = '0';
                slide.style.width = '100%';
                slide.style.height = '100%';
                slide.style.opacity = index === 0 ? '1' : '0';
                slide.style.transition = 'opacity 0.5s ease';
                slide.style.pointerEvents = index === 0 ? 'auto' : 'none';
                slide.style.zIndex = index === 0 ? '2' : '1'; // Asegurar que la diapositiva activa esté encima
            });

            // Verificar que el setup se ha realizado correctamente
            console.log(`SwiperLocal: Configurado modo 'fade' para ${this.slides.length} diapositivas`);
        } else {
            this.wrapper.style.display = 'flex';
            this.wrapper.style.transition = 'transform 0.3s ease';
            this.slides.forEach(slide => {
                slide.style.flexShrink = '0';
                slide.style.width = '100%';
            });
        }
    }

    goToSlide(index) {
        if (index < 0 || index >= this.slides.length) return;

        // Registro para depuración
        console.log(`SwiperLocal: Cambiando a slide ${index} de ${this.slides.length}`);

        this.currentIndex = index;

        if (this.options.effect === 'fade') {
            this.slides.forEach((slide, i) => {
                // Aplicar transición suave de opacidad
                slide.style.opacity = i === index ? '1' : '0';
                slide.style.pointerEvents = i === index ? 'auto' : 'none';
                slide.style.zIndex = i === index ? '2' : '1'; // Asegurar que el slide activo esté encima
                slide.classList.toggle('swiper-slide-active', i === index);

                // Verificar que se haya aplicado el cambio de opacidad
                if (i === index) {
                    console.log(`SwiperLocal: Slide ${i} activado con opacidad ${slide.style.opacity}`);
                }
            });
        } else {
            const translateX = -index * 100;
            this.wrapper.style.transform = `translateX(${translateX}%)`;

            this.slides.forEach((slide, i) => {
                slide.classList.toggle('swiper-slide-active', i === index);
            });
        }

        this.updatePagination();
    }

    slideNext() {
        const nextIndex = this.options.loop
            ? (this.currentIndex + 1) % this.slides.length
            : Math.min(this.currentIndex + 1, this.slides.length - 1);

        this.goToSlide(nextIndex);
    }

    slidePrev() {
        const prevIndex = this.options.loop
            ? (this.currentIndex - 1 + this.slides.length) % this.slides.length
            : Math.max(this.currentIndex - 1, 0);

        this.goToSlide(prevIndex);
    }

    updatePagination() {
        if (!this.paginationBullets) return;

        this.paginationBullets.forEach((bullet, index) => {
            bullet.classList.toggle('swiper-pagination-bullet-active', index === this.currentIndex);
        });
    }

    startAutoplay() {
        // No iniciar autoplay si solo hay un slide
        if (this.slides.length <= 1) {
            console.log('SwiperLocal: No se inicia autoplay porque solo hay un slide');
            return;
        }

        // Si ya está corriendo, detenerlo primero
        if (this.isAutoplayRunning) {
            this.stopAutoplay();
        }

        this.isAutoplayRunning = true;
        const delay = this.options.autoplay.delay || 3000;

        console.log(`SwiperLocal: Iniciando autoplay con ${this.slides.length} slides, retraso ${delay}ms`);

        this.autoplayTimer = setInterval(() => {
            if (this.isAutoplayRunning) {
                console.log(`SwiperLocal: Autoplay cambiando de slide ${this.currentIndex} a ${(this.currentIndex + 1) % this.slides.length}`);
                this.slideNext();
            }
        }, delay);
    }

    stopAutoplay() {
        console.log('SwiperLocal: Deteniendo autoplay');
        this.isAutoplayRunning = false;
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    update() {
        // Método para compatibilidad con Swiper original
        console.log('SwiperLocal: Actualizando carrusel');

        // Obtener los slides actualizados
        this.slides = this.container.querySelectorAll('.swiper-slide');
        console.log(`SwiperLocal: Se encontraron ${this.slides.length} slides en la actualización`);

        // Recrear la paginación
        this.createPagination();

        // Establecer los estilos apropiados para los slides
        this.setupTransitions();

        // Asegurar que estemos en un slide válido
        const validIndex = Math.min(this.currentIndex, Math.max(this.slides.length - 1, 0));
        if (validIndex !== this.currentIndex) {
            console.log(`SwiperLocal: Ajustando índice actual de ${this.currentIndex} a ${validIndex}`);
            this.currentIndex = validIndex;
        }

        // Ir al slide actual
        this.goToSlide(this.currentIndex);

        return this; // Para encadenamiento de métodos
    }

    // Propiedades para compatibilidad
    get autoplay() {
        return {
            start: () => {
                console.log('SwiperLocal: Iniciando autoplay');
                this.startAutoplay();
                return this; // Para encadenamiento
            },
            stop: () => {
                console.log('SwiperLocal: Deteniendo autoplay');
                this.stopAutoplay();
                return this; // Para encadenamiento
            },
            running: this.isAutoplayRunning
        };
    }
}

// Hacer disponible globalmente
window.SwiperLocal = SwiperLocal;
