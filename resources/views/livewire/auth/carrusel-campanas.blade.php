<div
    x-data="{
        currentIndex: @entangle('currentIndex'),
        autoplayInterval: @entangle('autoplayInterval'),
        autoplayTimer: null,
        totalSlides: {{ count($campanas) }},

        init() {
            if (this.totalSlides > 1) {
                this.startAutoplay();
            }
        },

        startAutoplay() {
            this.autoplayTimer = setInterval(() => {
                this.$wire.nextSlide();
            }, this.autoplayInterval);
        },

        stopAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
            }
        },

        resetAutoplay() {
            this.stopAutoplay();
            this.startAutoplay();
        }
    }"
    @mouseenter="stopAutoplay"
    @mouseleave="startAutoplay"
    class="relative w-full h-full overflow-hidden rounded-lg"
>
    @if(count($campanas) > 0)
        <div class="relative w-full h-full">
            @foreach($campanas as $index => $campana)
                <div
                    class="absolute inset-0 w-full h-full transition-opacity duration-500 ease-in-out"
                    x-show="currentIndex === {{ $index }}"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    @if($campana->tipo === 'imagen')
                        <a
                            href="{{ $campana->enlace }}"
                            class="block w-full h-full"
                            @if($campana->enlace) target="_blank" @else onclick="return false;" @endif
                        >
                            <img
                                src="{{ Storage::url($campana->archivo_path) }}"
                                alt="{{ $campana->titulo }}"
                                class="object-cover w-full h-full"
                            >
                        </a>
                    @else
                        <div class="w-full h-full">
                            <video
                                class="object-cover w-full h-full"
                                @if($index === $currentIndex) autoplay @endif
                                muted
                                loop
                                playsinline
                                x-init="$watch('currentIndex', value => {
                                    if (value === {{ $index }}) {
                                        $el.play();
                                    } else {
                                        $el.pause();
                                        $el.currentTime = 0;
                                    }
                                })"
                            >
                                <source src="{{ Storage::url($campana->archivo_path) }}" type="video/mp4">
                                Tu navegador no soporta videos HTML5.
                            </video>
                            @if($campana->enlace)
                                <a
                                    href="{{ $campana->enlace }}"
                                    target="_blank"
                                    class="absolute inset-0 z-10 flex items-center justify-center bg-black bg-opacity-0 hover:bg-opacity-20 transition-opacity"
                                >
                                    <span class="sr-only">{{ $campana->titulo }}</span>
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- Overlay de texto -->
                    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/70 to-transparent text-white">
                        <h3 class="text-xl font-bold">{{ $campana->titulo }}</h3>
                        @if($campana->descripcion)
                            <p class="text-sm mt-1">{{ $campana->descripcion }}</p>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Controles de navegación -->
            @if(count($campanas) > 1)
                <div class="absolute inset-y-0 left-0 flex items-center">
                    <button
                        type="button"
                        class="flex items-center justify-center h-10 w-10 rounded-full bg-white/30 hover:bg-white/50 focus:outline-none ml-2"
                        @click="$wire.prevSlide(); resetAutoplay()"
                    >
                        <span class="sr-only">Anterior</span>
                        <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                </div>
                <div class="absolute inset-y-0 right-0 flex items-center">
                    <button
                        type="button"
                        class="flex items-center justify-center h-10 w-10 rounded-full bg-white/30 hover:bg-white/50 focus:outline-none mr-2"
                        @click="$wire.nextSlide(); resetAutoplay()"
                    >
                        <span class="sr-only">Siguiente</span>
                        <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

                <!-- Indicadores -->
                <div class="absolute bottom-4 left-0 right-0">
                    <div class="flex items-center justify-center gap-2">
                        @foreach($campanas as $index => $campana)
                            <button
                                type="button"
                                class="h-2.5 w-2.5 rounded-full focus:outline-none"
                                :class="currentIndex === {{ $index }} ? 'bg-white' : 'bg-white/50 hover:bg-white/75'"
                                @click="$wire.setSlide({{ $index }}); resetAutoplay()"
                                aria-label="Ir a slide {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="flex items-center justify-center w-full h-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
            Sin campañas activas
        </div>
    @endif
</div>
