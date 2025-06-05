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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
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

        /* Animación de pulso personalizada */
        @keyframes custom-pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .animate-pulse {
            animation: custom-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
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
            object-fit: contain;
        }

        /* Estilos para animación fade */
        .swiper-container-fade .swiper-slide {
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .swiper-container-fade .swiper-slide-active {
            opacity: 1;
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

                <div id="carousel-section" class="{{ $zona->tipo_registro != 'sin_registro' && $mostrarFormulario ? 'hidden' : '' }}">
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
                                    <img id="slide-img-{{ $loop->index }}"
                                         src="{{ $imagen }}"
                                         alt="Imagen promocional"
                                         onerror="handleImageError(this, '{{ $imagen }}', {{ $loop->index }})"
                                         style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                                @endforeach
                            @else
                                <!-- Imagen por defecto si no hay ninguna -->
                                <div class="swiper-slide">
                                    <img src="/storage/campanas/imagenes/default.jpg"
                                         alt="Imagen por defecto"
                                         style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            @endif
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>

                    <div id="debug-info" class="text-xs text-gray-400 mt-2" style="display: none;">
                        <div>Total imágenes: {{ count($imagenes) }}</div>
                        <div id="loaded-images">Imágenes cargadas: 0</div>
                    </div>
                </div>

                <div class="countdown-container mt-4">
                    <div class="countdown" id="countdown">{{ $tiempoVisualizacion }}</div>
                    <div class="ml-2">segundos para tu acceso</div>
                </div>

                <!-- Mensaje de espera visible para todos los tipos de autenticación -->
                <div class="text-center mt-2 text-sm text-gray-500">
                    @if($zona->tipo_autenticacion_mikrotik == 'sin_autenticacion')
                        <p id="auto-connect-message">Serás conectado automáticamente cuando termine la cuenta regresiva</p>
                    @elseif($zona->tipo_autenticacion_mikrotik == 'pin')
                        <p>Cuando termine la cuenta regresiva, ingresa el PIN o conéctate gratis</p>
                    @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                        <p>Cuando termine la cuenta regresiva, ingresa tus credenciales o conéctate gratis</p>
                    @endif
                </div>

                <!-- Botón de conexión gratuita para todos los tipos de autenticación (oculto inicialmente) -->
                <div id="free-connection-container" class="text-center mt-6 mb-4 animate-pulse" style="display: none;">
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-2 mb-3 rounded">
                        @if($zona->tipo_autenticacion_mikrotik == 'sin_autenticacion')
                            <p class="text-sm">Tu conexión estará lista en unos instantes:</p>
                        @else
                            <p class="text-sm">¿No tienes credenciales? Prueba nuestra conexión gratuita:</p>
                        @endif
                    </div>
                    <button id="free-connection-button" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg shadow-lg transform hover:scale-105 transition-all duration-300 text-lg flex items-center mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                        </svg>
                        ¡Conéctate Gratis Aquí!
                    </button>
                </div>

                @if($zona->tipo_registro == 'sin_registro' || !$mostrarFormulario)
                    @if($zona->tipo_autenticacion_mikrotik == 'pin')
                        <!-- Formulario para autenticación con PIN -->
                        <div class="mt-6 bg-gray-50 rounded-md p-4 border border-gray-200">
                            <h3 class="text-md font-semibold mb-3">Ingresa el PIN de acceso</h3>
                            <form id="mikrotik-pin-form" class="space-y-2">
                                <div>
                                    <label for="pin" class="sr-only">PIN</label>
                                    <input type="text" id="pin" name="pin" placeholder="Introduce el PIN"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                                </div>
                                <button type="submit" class="btn-primary py-2">
                                    Conectar
                                </button>
                                <div class="text-center text-xs text-gray-500 mt-2">
                                    <p>Solicita tu PIN al establecimiento</p>
                                </div>
                            </form>
                        </div>
                    @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                        <!-- Formulario para autenticación con Usuario y Contraseña -->
                        <div class="mt-6 bg-gray-50 rounded-md p-4 border border-gray-200">
                            <h3 class="text-md font-semibold mb-3">Ingresa tus credenciales</h3>
                            <form id="mikrotik-user-form" class="space-y-2">
                                <div>
                                    <label for="username" class="sr-only">Usuario</label>
                                    <input type="text" id="username" name="username" placeholder="Usuario"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary mb-2">
                                </div>
                                <div>
                                    <label for="password" class="sr-only">Contraseña</label>
                                    <input type="password" id="password" name="password" placeholder="Contraseña"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                                </div>
                                <button type="submit" class="btn-primary py-2">
                                    Conectar
                                </button>
                                <div class="text-center text-xs text-gray-500 mt-2">
                                    <p>Utiliza tus credenciales de acceso</p>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        // Función para manejar errores de carga de imágenes con múltiples rutas alternativas
        function handleImageError(img, originalSrc, index) {
            console.error('Error cargando imagen:', originalSrc);

            // Incrementar contador de intentos
            img.dataset.attempts = (parseInt(img.dataset.attempts || '0') + 1);

            // Si ya hemos intentado demasiadas veces, usar la imagen por defecto
            if (parseInt(img.dataset.attempts) > 3) {
                console.warn('Demasiados intentos fallidos, usando imagen por defecto');
                img.src = '/storage/campanas/imagenes/default.jpg';
                img.onerror = null; // Evitar bucle infinito
                return;
            }

            // Array de transformaciones alternativas para intentar
            const alternativeRoutes = [
                // 1. Intentar con /storage/ prefijado si no lo tiene
                src => src.startsWith('/storage/') ? src : `/storage/${src}`,
                // 2. Intentar con carpeta campañas/imagenes
                src => `/storage/campanas/imagenes/${src.split('/').pop()}`,
                // 3. Eliminar /storage/ si lo tiene
                src => src.replace('/storage/', ''),
            ];

            // Obtener la siguiente transformación según el número de intentos
            const currentAttempt = parseInt(img.dataset.attempts) - 1;
            if (currentAttempt < alternativeRoutes.length) {
                const newSrc = alternativeRoutes[currentAttempt](originalSrc);
                console.log(`Intento #${img.dataset.attempts}: cambiando ruta a ${newSrc}`);
                img.src = newSrc;
            } else {
                // Si ya probamos todas las transformaciones, usar imagen por defecto
                img.src = '/storage/campanas/imagenes/default.jpg';
                img.onerror = null; // Evitar bucle infinito
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const formSection = document.getElementById('form-section');
            const carouselSection = document.getElementById('carousel-section');
            const loginForm = document.getElementById('login-form');
            const countdown = document.getElementById('countdown');
            const debugInfo = document.getElementById('debug-info');
            let tiempoRestante = {{ $tiempoVisualizacion }};
            let countdownInterval;

            // Mostrar información de depuración con doble clic
            document.addEventListener('dblclick', function() {
                debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
            });

            // Inicializar Swiper cuando se muestre el carrusel
            const swiperConfig = {
                loop: {{ count($imagenes) > 1 ? 'true' : 'false' }}, // Solo activar loop si hay más de una imagen
                autoplay: {
                    delay: 4000, // 4 segundos entre imágenes
                    disableOnInteraction: false
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                // Si solo hay una imagen, desactivamos la navegación
                allowTouchMove: {{ count($imagenes) > 1 ? 'true' : 'false' }},
                effect: 'fade', // Efecto de transición más suave
                fadeEffect: {
                    crossFade: true
                }
            };

            console.log('Inicializando Swiper con configuración:', swiperConfig);
            let swiper = new Swiper('.swiper-container', swiperConfig);

            // Forzar inicio de autoplay
            if ({{ count($imagenes) > 1 ? 'true' : 'false' }}) {
                swiper.autoplay.start();
                console.log('Autoplay iniciado');
            }

            // Función para iniciar la cuenta regresiva
            function iniciarContador() {
                countdownInterval = setInterval(function() {
                    tiempoRestante--;
                    countdown.textContent = tiempoRestante;

                    if (tiempoRestante <= 0) {
                        clearInterval(countdownInterval);

                        // Mostrar botón de conexión gratuita en todos los casos
                        const freeConnectionContainer = document.getElementById('free-connection-container');
                        if (freeConnectionContainer) {
                            freeConnectionContainer.style.display = 'block';
                        }

                        // Ocultar mensaje automático si existe
                        const autoConnectMsg = document.getElementById('auto-connect-message');
                        if (autoConnectMsg) {
                            autoConnectMsg.style.display = 'none';
                        }

                        // Adicionalmente, dependiendo del tipo de autenticación, resaltar los formularios
                        @if($zona->tipo_autenticacion_mikrotik == 'pin')
                            // Mostrar el formulario de PIN con algún efecto
                            const pinForm = document.getElementById('mikrotik-pin-form');
                            if (pinForm) {
                                // Si hay un contenedor padre, podemos añadir efectos
                                const pinContainer = pinForm.closest('.mt-6');
                                if (pinContainer) {
                                    pinContainer.classList.add('shadow-lg');
                                    pinContainer.classList.add('border-green-400');
                                    pinContainer.style.transition = 'all 0.3s ease';
                                    pinContainer.style.transform = 'scale(1.03)';
                                    setTimeout(() => {
                                        pinContainer.style.transform = 'scale(1)';
                                    }, 500);
                                }
                            }
                        @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                            // Mostrar el formulario de usuario/contraseña con algún efecto
                            const userForm = document.getElementById('mikrotik-user-form');
                            if (userForm) {
                                // Si hay un contenedor padre, podemos añadir efectos
                                const userContainer = userForm.closest('.mt-6');
                                if (userContainer) {
                                    userContainer.classList.add('shadow-lg');
                                    userContainer.classList.add('border-green-400');
                                    userContainer.style.transition = 'all 0.3s ease';
                                    userContainer.style.transform = 'scale(1.03)';
                                    setTimeout(() => {
                                        userContainer.style.transform = 'scale(1)';
                                    }, 500);
                                }
                            }
                        @endif

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

            // Eventos de Swiper para monitorear su funcionamiento
            swiper.on('slideChange', function() {
                console.log('Slide cambiado a:', swiper.activeIndex);
            });

            swiper.on('autoplayStart', function() {
                console.log('Autoplay iniciado');
            });

            swiper.on('autoplayStop', function() {
                console.log('Autoplay detenido');
            });

            // Verificar que el carrusel esté funcionando
            setTimeout(function() {
                if (swiper.autoplay && swiper.autoplay.running) {
                    console.log('El carrusel está funcionando correctamente');
                } else {
                    console.warn('El carrusel no está reproduciendo automáticamente. Reiniciando...');
                    swiper.autoplay.start();
                }
            }, 5000);

            // Si hay formulario, configurarlo para que al enviarlo muestre el carrusel
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Ocultar formulario y mostrar carrusel
                    formSection.classList.add('hidden');
                    carouselSection.classList.remove('hidden');

                    // Reiniciar Swiper para asegurar que funcione después de mostrar el contenedor
                    setTimeout(function() {
                        swiper.update();
                        swiper.autoplay.start();
                    }, 100);

                    // Iniciar cuenta regresiva
                    iniciarContador();
                });
            }
            // Configuración para formularios de autenticación Mikrotik
            const mikrotikPinForm = document.getElementById('mikrotik-pin-form');
            const mikrotikUserForm = document.getElementById('mikrotik-user-form');

            // Si existe el formulario de PIN
            if (mikrotikPinForm) {
                mikrotikPinForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const pin = document.getElementById('pin').value;

                    if (!pin) {
                        alert('Por favor ingresa el PIN');
                        return;
                    }

                    // En producción, aquí enviaríamos el PIN al router Mikrotik
                    // Por ahora mostramos un mensaje de éxito
                    console.log('PIN enviado:', pin);
                    alert('Conectado con éxito. Disfruta tu navegación.');

                    // Simulamos la redirección
                    // window.location.href = '{{ $mikrotikData["link-orig"] ?? "https://www.google.com" }}';
                });
            }

            // Si existe el formulario de usuario y contraseña
            if (mikrotikUserForm) {
                mikrotikUserForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const username = document.getElementById('username').value;
                    const password = document.getElementById('password').value;

                    if (!username || !password) {
                        alert('Por favor ingresa usuario y contraseña');
                        return;
                    }

                    // En producción, aquí enviaríamos las credenciales al router Mikrotik
                    console.log('Credenciales enviadas:', { username, password });
                    alert('Conectado con éxito. Disfruta tu navegación.');

                    // Simulamos la redirección
                    // window.location.href = '{{ $mikrotikData["link-orig"] ?? "https://www.google.com" }}';
                });
            }

            // Iniciar contador automáticamente en todos los casos
            @if($zona->tipo_registro == 'sin_registro' || !$mostrarFormulario)
                // Iniciar cuenta regresiva directamente para todos los tipos de autenticación
                iniciarContador();
            @endif

            // Configurar botón de conexión gratuita
            const freeConnectionButton = document.getElementById('free-connection-button');
            if (freeConnectionButton) {
                freeConnectionButton.addEventListener('click', function() {
                    // En producción, aquí enviaríamos la solicitud al router Mikrotik
                    console.log('Conexión gratuita solicitada');
                    alert('¡Conectado! Ahora tienes acceso a Internet.');

                    // Simulamos la redirección
                    // window.location.href = '{{ $mikrotikData["link-orig"] ?? "https://www.google.com" }}';
                });
            }
        });
    </script>
</body>
</html>
