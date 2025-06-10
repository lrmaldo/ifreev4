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
        if (!this.wrapper || this.slides.length === 0) return;

        this.createPagination();
        this.setupTouchEvents();
        this.setupTransitions();

        if (this.options.autoplay && this.options.autoplay.delay) {
            this.startAutoplay();
        }

        // Mostrar primera slide
        this.goToSlide(0);
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
            this.slides.forEach((slide, index) => {
                slide.style.position = 'absolute';
                slide.style.top = '0';
                slide.style.left = '0';
                slide.style.width = '100%';
                slide.style.height = '100%';
                slide.style.opacity = index === 0 ? '1' : '0';
                slide.style.transition = 'opacity 0.3s ease';
                slide.style.pointerEvents = index === 0 ? 'auto' : 'none';
            });
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

        this.currentIndex = index;

        if (this.options.effect === 'fade') {
            this.slides.forEach((slide, i) => {
                slide.style.opacity = i === index ? '1' : '0';
                slide.style.pointerEvents = i === index ? 'auto' : 'none';
                slide.classList.toggle('swiper-slide-active', i === index);
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
        if (this.slides.length <= 1) return;

        this.isAutoplayRunning = true;
        const delay = this.options.autoplay.delay || 3000;

        this.autoplayTimer = setInterval(() => {
            if (this.isAutoplayRunning) {
                this.slideNext();
            }
        }, delay);
    }

    stopAutoplay() {
        this.isAutoplayRunning = false;
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    update() {
        // Método para compatibilidad con Swiper original
        this.slides = this.container.querySelectorAll('.swiper-slide');
        this.createPagination();
        this.goToSlide(this.currentIndex);
    }

    // Propiedades para compatibilidad
    get autoplay() {
        return {
            start: () => this.startAutoplay(),
            stop: () => this.stopAutoplay()
        };
    }
}

// Hacer disponible globalmente
window.SwiperLocal = SwiperLocal;
