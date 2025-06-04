<!DOCTYPE html>
<html lang="es">
<head>
    <title>Vista previa con carrusel - {{ $zona->nombre }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts - Inter y Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Swiper CSS para el carrusel -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
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
        }

        .preview-container {
            max-width: 500px;
            margin: 20px auto;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: transform var(--animation-speed) ease, box-shadow var(--animation-speed) ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .preview-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
        }

        /* Barra superior */
        .preview-notice {
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            color: white;
            text-align: center;
            padding: 12px;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.025em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Contenido principal */
        .preview-content {
            padding: 2.5rem;
            background-color: white;
            position: relative;
        }

        /* Decoración de fondo */
        .preview-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 12px;
            background: linear-gradient(135deg, var(--color-secondary-light) 25%, transparent 25%) -10px 0,
                        linear-gradient(225deg, var(--color-secondary-light) 25%, transparent 25%) -10px 0,
                        linear-gradient(315deg, var(--color-secondary-light) 25%, transparent 25%),
                        linear-gradient(45deg, var(--color-secondary-light) 25%, transparent 25%);
            background-size: 20px 20px;
            opacity: 0.5;
        }

        /* Estilos para el formulario */
        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: border-color var(--animation-speed) ease, box-shadow var(--animation-speed) ease;
            margin-bottom: 1rem;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-input-focus);
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--color-text);
        }

        /* Estilo del botón principal */
        .btn-primary {
            display: inline-block;
            width: 100%;
            padding: 0.75rem 1.25rem;
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: background-color var(--animation-speed) ease;
        }

        .btn-primary:hover {
            background-color: var(--color-button-hover);
        }

        /* Estilos del carrusel */
        .carousel-container {
            width: 100%;
            overflow: hidden;
            border-radius: var(--radius-md);
            margin-top: 20px;
        }

        .swiper-container {
            width: 100%;
            height: 300px;
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .campaign-info {
            padding: 1rem;
            background-color: white;
            border-radius: 0 0 var(--radius-md) var(--radius-md);
        }

        /* Contador de tiempo */
        .countdown-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1rem;
        }

        .countdown {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-primary);
            background-color: var(--color-primary-light);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
        }

        /* Estilos responsive */
        @media (max-width: 640px) {
            .preview-container {
                margin: 10px;
                width: calc(100% - 20px);
            }

            .preview-content {
                padding: 1.5rem;
            }

            .swiper-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-notice">
            {{ $zona->nombre }} - Carrusel de campañas
        </div>

        <div class="preview-content">
            @if($zona->tipo_registro == 'sin_registro' || !$mostrarFormulario)
                <!-- Sin registro o sin campos configurados -->
                <div id="carousel-section">
                    <h1 class="text-2xl font-bold mb-6">Accede a nuestra WiFi</h1>
                    <p class="text-gray-600 mb-6">
                        @if($campanaSeleccionada)
                            {{ $campanaSeleccionada->titulo }}
                        @else
                            Mira nuestras promociones mientras preparamos tu conexión
                        @endif
                    </p>
            @else
                <!-- Con registro -->
                <div id="form-section" class="mb-8">
                    <h1 class="text-2xl font-bold mb-6">Accede a nuestra WiFi</h1>
                    <p class="text-gray-600 mb-6">
                        @if($campanaSeleccionada)
                            {{ $campanaSeleccionada->titulo }}
                        @else
                            Mira nuestras promociones mientras preparamos tu conexión
                        @endif
                    </p>

                    <form id="login-form" class="space-y-4">
                        @foreach ($zona->campos as $campo)
                            <div class="mb-4">
                                {!! $camposHtml[$loop->index] !!}
                            </div>
                        @endforeach

                        <button type="submit" class="btn-primary">
                            Acceder
                        </button>
                    </form>
                </div>

                <div id="carousel-section" class="hidden">
                    <h2 class="text-xl font-bold mb-4">
                        @if($campanaSeleccionada)
                            {{ $campanaSeleccionada->titulo }}
                        @else
                            Promociones mientras te conectamos
                        @endif
                    </h2>
            @endif

                <div class="carousel-container">

                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            @if(count($imagenes) > 0)
                                @foreach ($imagenes as $imagen)
                                <div class="swiper-slide">
                                    <img src="{{ $imagen }}" alt="Imagen promocional"
                                         onerror="this.onerror=null; this.src='/storage/campanas/imagenes/default.jpg'; console.error('Error cargando imagen: {{ $imagen }}');"
                                         style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                                @endforeach
                            @else
                                <!-- Imagen por defecto si no hay ninguna -->
                                <div class="swiper-slide">
                                    <img src="/storage/campanas/imagenes/default.jpg" alt="Imagen por defecto"
                                         style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            @endif
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>

                <div class="countdown-container mt-4">
                    <div class="countdown" id="countdown">{{ $tiempoVisualizacion }}</div>
                    <div class="ml-2">segundos para tu acceso</div>
                </div>

                @if($zona->tipo_registro == 'sin_registro' || !$mostrarFormulario)
                <div class="text-center mt-6 text-sm text-gray-500">
                    <p>Serás conectado automáticamente cuando termine la cuenta regresiva</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formSection = document.getElementById('form-section');
            const carouselSection = document.getElementById('carousel-section');
            const loginForm = document.getElementById('login-form');
            const countdown = document.getElementById('countdown');
            let tiempoRestante = {{ $tiempoVisualizacion }};
            let countdownInterval;

            // Inicializar Swiper cuando se muestre el carrusel
            let swiper = new Swiper('.swiper-container', {
                loop: {{ count($imagenes) > 1 ? 'true' : 'false' }}, // Solo activar loop si hay más de una imagen
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                // Si solo hay una imagen, desactivamos la navegación
                allowTouchMove: {{ count($imagenes) > 1 ? 'true' : 'false' }}
            });

            // Función para iniciar la cuenta regresiva
            function iniciarContador() {
                countdownInterval = setInterval(function() {
                    tiempoRestante--;
                    countdown.textContent = tiempoRestante;

                    if (tiempoRestante <= 0) {
                        clearInterval(countdownInterval);
                        // En un entorno real, aquí redireccionaríamos al usuario
                        // o lo autenticaríamos en el router Mikrotik
                        alert('¡Conectado! Ahora tienes acceso a Internet.');

                        // Podríamos usar una llamada AJAX para registrar la visualización
                        // y autorizar al usuario en el router
                        /*
                        fetch('/portal-cautivo/{{ $zona->id }}/video-completado', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                campana_id: {{ $campanaSeleccionada ? $campanaSeleccionada->id : 'null' }},
                                mac: '{{ $mikrotikData["mac"] ?? "" }}',
                                ip: '{{ $mikrotikData["ip"] ?? "" }}'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success && data.redirect_url) {
                                window.location.href = data.redirect_url;
                            }
                        });
                        */
                    }
                }, 1000);
            }

            // Si hay formulario, configurarlo para que al enviarlo muestre el carrusel
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Ocultar formulario y mostrar carrusel
                    formSection.classList.add('hidden');
                    carouselSection.classList.remove('hidden');

                    // Iniciar cuenta regresiva
                    iniciarContador();
                });
            }
            // Si es modo sin registro o no hay campos configurados, iniciar contador automáticamente
            @if($zona->tipo_registro == 'sin_registro' || !$mostrarFormulario)
                // Iniciar cuenta regresiva directamente
                iniciarContador();
            @endif
        });
    </script>
</body>
</html>
