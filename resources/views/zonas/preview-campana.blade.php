<!DOCTYPE html>
<html lang="es">
<head>
    <title>Vista previa de campaña - {{ $zona->nombre }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts - Inter y Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    @if($tipoCampana === 'imagenes')
    <!-- Swiper CSS para el carrusel -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    @endif
    
    <style>
        /* Variables de colores personalizables */
        :root {
            --color-background: #f9fafb;
            --color-primary: #ff5e2c; /* Color principal solicitado */
            --color-primary-light: rgba(255, 94, 44, 0.1);
            --color-secondary: #ff8159; /* Variación más clara del color principal */
            --color-secondary-light: rgba(255, 129, 89, 0.15);
            --color-secondary-dark: #e64a1c; /* Variación más oscura del color principal */
            --color-text: #1f2937;
            --color-text-light: #6b7280;
            --color-border: #e5e7eb;
            --color-input-focus: #ffeee8;
            --color-button-hover: #e64a1c;
            --color-success: #10b981;
            --color-success-light: rgba(16, 185, 129, 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-full: 9999px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --animation-speed: 0.3s;
        }

        /* Estilos globales */
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .preview-container {
            max-width: 500px;
            margin: 20px auto;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            background: white;
        }

        /* Responsive design */
        @media (max-width: 640px) {
            .preview-container {
                margin: 10px;
                max-width: none;
            }
        }

        /* Botones y elementos interactivos */
        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            box-shadow: var(--shadow-sm);
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--color-button-hover), var(--color-primary));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Estilos para formularios */
        .form-field {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--color-text);
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 16px;
            transition: all var(--animation-speed) ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
            background-color: var(--color-input-focus);
            box-shadow: 0 0 0 3px rgba(255, 94, 44, 0.1);
        }

        /* Estilos específicos para carrusel */
        @if($tipoCampana === 'imagenes')
        .swiper {
            width: 100%;
            height: 400px;
        }

        .swiper-slide {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-primary-light);
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .swiper-pagination-bullet {
            background: var(--color-primary);
            opacity: 0.5;
        }

        .swiper-pagination-bullet-active {
            opacity: 1;
            background: var(--color-primary);
        }

        .countdown-container {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: var(--radius-full);
            font-weight: 600;
            z-index: 10;
        }

        .carousel-container {
            position: relative;
            background: var(--color-primary-light);
        }
        @endif

        /* Estilos específicos para video */
        @if($tipoCampana === 'video')
        .video-container {
            position: relative;
            background: black;
            height: 400px;
        }

        .video-player {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            z-index: 10;
        }

        .video-overlay.hidden {
            display: none;
        }

        .play-button {
            background: var(--color-primary);
            border: none;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-bottom: 20px;
            transition: all var(--animation-speed) ease;
        }

        .play-button:hover {
            background: var(--color-button-hover);
            transform: scale(1.1);
        }

        .play-icon {
            width: 0;
            height: 0;
            border-left: 20px solid white;
            border-top: 12px solid transparent;
            border-bottom: 12px solid transparent;
            margin-left: 5px;
        }

        .video-message {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
        }
        @endif

        /* Contenedor principal */
        .content-section {
            padding: 30px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .zone-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 8px;
        }

        .zone-subtitle {
            color: var(--color-text-light);
            font-size: 16px;
        }

        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        /* Estado de carga */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Estilos para la fase de conectando */
        .connecting-section {
            display: none;
            text-align: center;
            padding: 50px 30px;
        }

        .connecting-section.active {
            display: block;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--color-success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .checkmark {
            width: 30px;
            height: 30px;
            border: 3px solid white;
            border-radius: 50%;
            position: relative;
        }

        .checkmark::after {
            content: '';
            position: absolute;
            left: 6px;
            top: 2px;
            width: 8px;
            height: 16px;
            border: solid white;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <!-- Sección del formulario -->
        <div id="form-section" class="content-section">
            <div class="header-section fade-in">
                <h1 class="zone-title">{{ $zona->nombre }}</h1>
                <p class="zone-subtitle">Completa los datos para acceder a internet</p>
            </div>

            <form id="captive-form" class="fade-in">
                @foreach($camposHtml as $campoHtml)
                    <div class="form-field">
                        {!! $campoHtml !!}
                    </div>
                @endforeach

                <button type="submit" class="btn-primary" id="connect-btn">
                    <span class="button-text">Conectar</span>
                    <span class="loading-spinner" style="display: none;"></span>
                </button>
            </form>
        </div>

        <!-- Sección de contenido multimedia -->
        <div id="media-section" style="display: none;">
            @if($tipoCampana === 'imagenes')
            <!-- Carrusel de imágenes -->
            <div class="carousel-container">
                <div class="countdown-container">
                    <span id="countdown">{{ $contenido['tiempoVisualizacion'] }}</span>s
                </div>
                
                <div class="swiper">
                    <div class="swiper-wrapper">
                        @foreach($contenido['imagenes'] as $imagen)
                        <div class="swiper-slide">
                            <img src="{{ $imagen }}" alt="Imagen de campaña" />
                        </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            @elseif($tipoCampana === 'video')
            <!-- Reproductor de video -->
            <div class="video-container">
                <video id="campaign-video" class="video-player" preload="metadata">
                    <source src="{{ $contenido['videoUrl'] }}" type="video/mp4">
                    Tu navegador no soporta la reproducción de video.
                </video>
                
                <div id="video-overlay" class="video-overlay">
                    <button id="play-btn" class="play-button">
                        <div class="play-icon"></div>
                    </button>
                    <p class="video-message">Haz clic para reproducir el video</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sección de conectando -->
        <div id="connecting-section" class="connecting-section">
            <div class="success-icon">
                <div class="checkmark"></div>
            </div>
            <h2 style="color: var(--color-success); margin-bottom: 10px;">¡Conectado exitosamente!</h2>
            <p style="color: var(--color-text-light); margin-bottom: 20px;">
                Serás redirigido automáticamente en <span id="redirect-countdown">3</span> segundos...
            </p>
        </div>
    </div>

    <!-- Scripts -->
    @if($tipoCampana === 'imagenes')
    <!-- Swiper JS para el carrusel -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('captive-form');
            const formSection = document.getElementById('form-section');
            const mediaSection = document.getElementById('media-section');
            const connectingSection = document.getElementById('connecting-section');
            const connectBtn = document.getElementById('connect-btn');
            const buttonText = connectBtn.querySelector('.button-text');
            const loadingSpinner = connectBtn.querySelector('.loading-spinner');

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Mostrar estado de carga
                connectBtn.disabled = true;
                buttonText.style.display = 'none';
                loadingSpinner.style.display = 'inline-block';

                // Simular conexión
                setTimeout(function() {
                    formSection.style.display = 'none';
                    mediaSection.style.display = 'block';
                    mediaSection.classList.add('slide-up');

                    @if($tipoCampana === 'imagenes')
                    initCarousel();
                    @elseif($tipoCampana === 'video')
                    initVideo();
                    @endif
                }, 1500);
            });

            @if($tipoCampana === 'imagenes')
            function initCarousel() {
                // Inicializar Swiper
                const swiper = new Swiper('.swiper', {
                    loop: true,
                    autoplay: {
                        delay: 3000,
                        disableOnInteraction: false,
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    effect: 'fade',
                    fadeEffect: {
                        crossFade: true
                    }
                });

                // Iniciar countdown
                let countdown = {{ $contenido['tiempoVisualizacion'] }};
                const countdownElement = document.getElementById('countdown');
                
                const timer = setInterval(function() {
                    countdown--;
                    countdownElement.textContent = countdown;
                    
                    if (countdown <= 0) {
                        clearInterval(timer);
                        showConnectingScreen();
                    }
                }, 1000);
            }
            @elseif($tipoCampana === 'video')
            function initVideo() {
                const video = document.getElementById('campaign-video');
                const overlay = document.getElementById('video-overlay');
                const playBtn = document.getElementById('play-btn');

                playBtn.addEventListener('click', function() {
                    overlay.classList.add('hidden');
                    video.play();
                });

                video.addEventListener('ended', function() {
                    showConnectingScreen();
                });

                // También permitir que el usuario haga clic en el video para reproducir
                video.addEventListener('click', function() {
                    if (video.paused) {
                        overlay.classList.add('hidden');
                        video.play();
                    }
                });
            }
            @endif

            function showConnectingScreen() {
                mediaSection.style.display = 'none';
                connectingSection.classList.add('active');
                
                // Countdown para redirección
                let redirectCount = 3;
                const redirectElement = document.getElementById('redirect-countdown');
                
                const redirectTimer = setInterval(function() {
                    redirectCount--;
                    redirectElement.textContent = redirectCount;
                    
                    if (redirectCount <= 0) {
                        clearInterval(redirectTimer);
                        // Aquí se haría la redirección real
                        window.location.href = '{{ $mikrotikData["link-orig"] }}';
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>
