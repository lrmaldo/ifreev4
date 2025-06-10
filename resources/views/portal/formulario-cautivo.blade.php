<!DOCTYPE html>
<html lang="es">
<head>
    <title>Portal Cautivo - {{ $zona->nombre }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <!-- Tailwind CSS del sistema -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Swiper CSS para carrusel -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <style>
        /* Variables CSS personalizables */
        :root {
            --color-background: #f9fafb;
            --color-primary: #ff5e2c;
            --color-primary-light: rgba(255, 94, 44, 0.1);
            --color-secondary: #ff8159;
            --color-secondary-light: rgba(255, 129, 89, 0.15);
            --color-secondary-dark: #e64a1c;
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

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            line-height: 1.6;
        }

        .portal-container {
            max-width: 500px;
            margin: 20px auto;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: transform var(--animation-speed) ease, box-shadow var(--animation-speed) ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .portal-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
        }

        .portal-header {
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            color: white;
            text-align: center;
            padding: 12px;
            font-size: 15px;
            font-weight: 500;
            letter-spacing: 0.025em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .portal-content {
            padding: 2.5rem;
            background-color: white;
            position: relative;
        }

        .portal-content::before {
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

        /* Estilos para formularios */
        .form-field {
            margin-bottom: 1rem;
        }

        .form-field input,
        .form-field select,
        .form-field textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: border-color var(--animation-speed) ease, box-shadow var(--animation-speed) ease;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-input-focus);
        }

        .form-field label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--color-text);
        }

        /* Estilos específicos para radio buttons y checkboxes */
        .form-field input[type="radio"],
        .form-field input[type="checkbox"] {
            width: auto;
            margin: 0;
            padding: 0;
            position: relative;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid var(--color-border);
            background-color: white;
            cursor: pointer;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
            flex-shrink: 0;
        }

        /* Radio buttons - circular */
        .form-field input[type="radio"] {
            border-radius: 50%;
        }

        /* Checkboxes - square with rounded corners */
        .form-field input[type="checkbox"] {
            border-radius: 3px;
        }

        /* Estado checked para radio buttons */
        .form-field input[type="radio"]:checked {
            border-color: var(--color-primary);
            background-color: var(--color-primary);
        }

        .form-field input[type="radio"]:checked::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background-color: white;
            border-radius: 50%;
        }

        /* Estado checked para checkboxes */
        .form-field input[type="checkbox"]:checked {
            border-color: var(--color-primary);
            background-color: var(--color-primary);
        }

        .form-field input[type="checkbox"]:checked::before {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
        }

        /* Estados hover y focus */
        .form-field input[type="radio"]:hover,
        .form-field input[type="checkbox"]:hover {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px var(--color-input-focus);
        }

        .form-field input[type="radio"]:focus,
        .form-field input[type="checkbox"]:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-input-focus);
        }

        /* Contenedores para opciones de radio y checkbox */
        .radio-group,
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .radio-option,
        .checkbox-option {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            transition: background-color var(--animation-speed) ease;
            cursor: pointer;
        }

        .radio-option:hover,
        .checkbox-option:hover {
            background-color: var(--color-primary-light);
        }

        .radio-option label,
        .checkbox-option label {
            margin: 0;
            cursor: pointer;
            font-weight: 400;
            line-height: 1.4;
            user-select: none;
        }

        /* Para formularios en línea (cuando hay pocas opciones) */
        .inline-options {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .inline-options .radio-option,
        .inline-options .checkbox-option {
            margin-right: 1rem;
            padding: 0.25rem 0;
        }

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
            font-size: 1rem;
        }

        .btn-primary:hover {
            background-color: var(--color-button-hover);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Carrusel de contenido */
        .content-carousel {
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

        .swiper-slide video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Contador */
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

        /* Botón de conexión */
        .connection-button {
            text-align: center;
            margin-top: 1.5rem;
        }

        .btn-connection {
            background: linear-gradient(135deg, var(--color-success), #059669);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all var(--animation-speed) ease;
            border: none;
            cursor: pointer;
        }

        .btn-connection:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        /* Estados del portal */
        .portal-step {
            transition: all 0.3s ease;
        }

        .portal-step.hidden {
            display: none;
        }

        /* Autenticación Mikrotik */
        .auth-form {
            margin-top: 1.5rem;
            padding: 1rem;
            background-color: #f9fafb;
            border-radius: var(--radius-md);
            border: 1px solid var(--color-border);
        }

        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .portal-container {
                margin: 10px;
                width: calc(100% - 20px);
            }

            .portal-content {
                padding: 1.5rem;
            }

            .swiper-container {
                height: 200px;
            }
        }

        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>

    <!-- Scripts de configuración para Mikrotik -->
    {!! $zona->script_head ?? '' !!}
</head>
<body>
    <div class="portal-container">
        <div class="portal-header">
            {{ $zona->nombre }} - Portal WiFi
        </div>

        <div class="portal-content">
            <!-- Paso 1: Formulario (si aplica) -->
            @if($mostrarFormulario)
            <div id="step-form" class="portal-step fade-in">
                <h1 class="text-2xl font-bold mb-6 text-center">Accede a nuestra WiFi</h1>
                <p class="text-gray-600 mb-6 text-center">
                    @if($campanaSeleccionada)
                        {{ $campanaSeleccionada->titulo ?? $campanaSeleccionada->nombre }}
                    @else
                        Completa el formulario para conectarte a internet
                    @endif
                </p>

                <form id="portal-form" class="space-y-4">
                    <input type="hidden" id="zona_id" value="{{ $zona->id }}">
                    <input type="hidden" id="mac_address" value="{{ $mikrotikData['mac'] ?? '' }}">
                    <input type="hidden" id="mikrotik_redirect" value="{{ $mikrotikData['link-orig'] ?? '' }}">

                    @foreach ($camposHtml as $campoHtml)
                        <div class="form-field">
                            {!! $campoHtml !!}
                        </div>
                    @endforeach

                    <button type="submit" class="btn-primary mt-6" id="submit-btn">
                        <span class="button-text">Conectar</span>
                        <span class="loading-spinner hidden" id="loading-spinner"></span>
                    </button>
                </form>
            </div>
            @endif

            <!-- Paso 2: Contenido visual (carrusel/video) -->
            <div id="step-content" class="portal-step {{ $mostrarFormulario ? 'hidden' : 'fade-in' }}">
                @if(!$mostrarFormulario)
                    <h1 class="text-2xl font-bold mb-6 text-center">{{ $zona->nombre }}</h1>
                    <p class="text-gray-600 mb-6 text-center">
                        @if($campanaSeleccionada)
                            {{ $campanaSeleccionada->titulo ?? $campanaSeleccionada->nombre }}
                        @else
                            Preparando tu conexión WiFi...
                        @endif
                    </p>
                @endif

                @if($videoUrl)
                    <!-- Video -->
                    <div class="content-carousel">
                        <video id="campaign-video" controls autoplay muted class="w-full h-full rounded-md">
                            <source src="{{ $videoUrl }}" type="video/mp4">
                            Tu navegador no soporta reproducción de video.
                        </video>
                    </div>
                @elseif(count($imagenes) > 0)
                    <!-- Carrusel de imágenes -->
                    <div class="content-carousel">
                        <div class="swiper-container">
                            <div class="swiper-wrapper">
                                @foreach($imagenes as $imagen)
                                    <div class="swiper-slide">
                                        <img src="{{ $imagen }}" alt="Promoción" loading="lazy"
                                             onerror="this.src='/storage/campanas/imagenes/default.jpg'">
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>
                @endif

                <!-- Contador regresivo -->
                <div class="countdown-container">
                    <div class="countdown" id="countdown">{{ $tiempoVisualizacion }}</div>
                    <div class="ml-2 text-sm">segundos para tu acceso</div>
                </div>

                <!-- Mensaje de estado -->
                <div class="text-center mt-4 text-sm text-gray-500" id="status-message">
                    @if($zona->tipo_autenticacion_mikrotik == 'sin_autenticacion')
                        <p>Serás conectado automáticamente cuando termine la cuenta regresiva</p>
                    @elseif($zona->tipo_autenticacion_mikrotik == 'pin')
                        <p>Cuando termine la cuenta regresiva, ingresa el PIN o conéctate gratis</p>
                    @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                        <p>Cuando termine la cuenta regresiva, ingresa tus credenciales o conéctate gratis</p>
                    @endif
                </div>

                <!-- Botón de conexión gratuita -->
                <div id="connection-container" class="connection-button hidden pulse">
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 mb-4 rounded text-sm">
                        @if($zona->tipo_autenticacion_mikrotik == 'sin_autenticacion')
                            <p>Tu conexión está lista:</p>
                        @else
                            <p>¿No tienes credenciales? Prueba nuestra conexión gratuita:</p>
                        @endif
                    </div>
                    <button id="connect-btn" class="btn-connection">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                        </svg>
                        ¡Conéctate Gratis Aquí!
                    </button>
                </div>

                <!-- Formularios de autenticación Mikrotik -->
                @if($zona->tipo_autenticacion_mikrotik == 'pin')
                    <div class="auth-form hidden" id="pin-form">
                        <h3 class="text-md font-semibold mb-3">Ingresa el PIN de acceso</h3>
                        <form id="mikrotik-pin-form" class="space-y-3">
                            <input type="text" id="pin" name="pin" placeholder="Introduce el PIN"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                            <button type="submit" class="btn-primary">Conectar</button>
                            <div class="text-center text-xs text-gray-500">
                                <p>Solicita tu PIN al establecimiento</p>
                            </div>
                        </form>
                    </div>
                @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                    <div class="auth-form hidden" id="user-form">
                        <h3 class="text-md font-semibold mb-3">Ingresa tus credenciales</h3>
                        <form id="mikrotik-user-form" class="space-y-3">
                            <input type="text" id="username" name="username" placeholder="Usuario"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                            <input type="password" id="password" name="password" placeholder="Contraseña"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                            <button type="submit" class="btn-primary">Conectar</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuración inicial
            const stepForm = document.getElementById('step-form');
            const stepContent = document.getElementById('step-content');
            const portalForm = document.getElementById('portal-form');
            const countdown = document.getElementById('countdown');
            const connectionContainer = document.getElementById('connection-container');
            const statusMessage = document.getElementById('status-message');

            let tiempoRestante = {{ $tiempoVisualizacion }};
            let countdownInterval;
            let tiempoInicio = Date.now();

            // Configurar CSRF token para peticiones AJAX
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Inicializar Swiper si hay imágenes
            @if(count($imagenes) > 0)
            const swiper = new Swiper('.swiper-container', {
                loop: {{ count($imagenes) > 1 ? 'true' : 'false' }},
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                allowTouchMove: {{ count($imagenes) > 1 ? 'true' : 'false' }},
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                }
            });
            @endif

            // Función para enviar el formulario
            function enviarFormulario(formData) {
                const submitBtn = document.getElementById('submit-btn');
                const buttonText = submitBtn.querySelector('.button-text');
                const loadingSpinner = document.getElementById('loading-spinner');

                // Mostrar loading
                buttonText.textContent = 'Conectando...';
                loadingSpinner.classList.remove('hidden');
                submitBtn.disabled = true;

                // Preparar datos
                const data = {
                    zona_id: document.getElementById('zona_id').value,
                    mac_address: document.getElementById('mac_address').value,
                    mikrotik_redirect: document.getElementById('mikrotik_redirect').value,
                    respuestas: formData,
                    tiempo_activo: Math.floor((Date.now() - tiempoInicio) / 1000),
                    dispositivo: navigator.userAgent,
                    navegador: navigator.userAgent,
                    tipo_visual: '{{ $videoUrl ? "video" : (count($imagenes) > 0 ? "carrusel" : "formulario") }}'
                };

                fetch('{{ route("zona.formulario.responder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Ocultar formulario y mostrar contenido
                        if (stepForm) stepForm.classList.add('hidden');
                        stepContent.classList.remove('hidden');
                        stepContent.classList.add('fade-in');

                        // Iniciar cuenta regresiva
                        iniciarContador();

                        // Reiniciar Swiper si existe
                        @if(count($imagenes) > 0)
                        setTimeout(() => {
                            swiper.update();
                            swiper.autoplay.start();
                        }, 100);
                        @endif
                    } else {
                        alert(data.message || 'Error al enviar el formulario');
                        // Restaurar botón
                        buttonText.textContent = 'Conectar';
                        loadingSpinner.classList.add('hidden');
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                    // Restaurar botón
                    buttonText.textContent = 'Conectar';
                    loadingSpinner.classList.add('hidden');
                    submitBtn.disabled = false;
                });
            }

            // Manejar envío del formulario principal
            if (portalForm) {
                portalForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Recopilar datos del formulario
                    const formData = new FormData(portalForm);
                    const respuestas = {};

                    // Procesar campos de texto, email, select, etc.
                    for (let [key, value] of formData.entries()) {
                        if (key !== 'zona_id' && key !== 'mac_address' && key !== 'mikrotik_redirect') {
                            // Verificar si es un campo con estructura form[campo]
                            const match = key.match(/^form\[([^\]]+)\](?:\[([^\]]+)\])?$/);
                            if (match) {
                                const campo = match[1];
                                const subcampo = match[2];
                                
                                if (subcampo) {
                                    // Es un checkbox múltiple: form[campo][opcion]
                                    if (!respuestas[campo]) {
                                        respuestas[campo] = {};
                                    }
                                    respuestas[campo][subcampo] = value;
                                } else {
                                    // Es un campo simple: form[campo]
                                    respuestas[campo] = value;
                                }
                            }
                        }
                    }

                    // Procesar radio buttons específicamente
                    const radioInputs = portalForm.querySelectorAll('input[type="radio"]:checked');
                    radioInputs.forEach(radio => {
                        const match = radio.name.match(/^form\[([^\]]+)\]$/);
                        if (match) {
                            respuestas[match[1]] = radio.value;
                        }
                    });

                    // Procesar checkboxes únicos (no múltiples)
                    const checkboxInputs = portalForm.querySelectorAll('input[type="checkbox"]');
                    checkboxInputs.forEach(checkbox => {
                        const match = checkbox.name.match(/^form\[([^\]]+)\]$/);
                        if (match && !checkbox.name.includes('][')) {
                            // Es un checkbox único, no múltiple
                            respuestas[match[1]] = checkbox.checked ? '1' : '0';
                        }
                    });

                    console.log('Respuestas recopiladas:', respuestas);
                    enviarFormulario(respuestas);
                });
            }

            // Función para iniciar cuenta regresiva
            function iniciarContador() {
                countdownInterval = setInterval(function() {
                    tiempoRestante--;
                    countdown.textContent = tiempoRestante;

                    if (tiempoRestante <= 0) {
                        clearInterval(countdownInterval);
                        mostrarOpcionesConexion();
                    }
                }, 1000);
            }

            // Mostrar opciones de conexión
            function mostrarOpcionesConexion() {
                connectionContainer.classList.remove('hidden');
                statusMessage.style.display = 'none';

                // Mostrar formularios de auth según el tipo
                @if($zona->tipo_autenticacion_mikrotik == 'pin')
                    document.getElementById('pin-form').classList.remove('hidden');
                @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                    document.getElementById('user-form').classList.remove('hidden');
                @endif
            }

            // Manejar conexión gratuita
            document.getElementById('connect-btn').addEventListener('click', function() {
                // Simular conexión (en producción enviaría al router Mikrotik)
                alert('¡Conectado! Ahora tienes acceso a Internet.');
                // window.location.href = document.getElementById('mikrotik_redirect').value;
            });

            // Manejar formularios de autenticación Mikrotik
            @if($zona->tipo_autenticacion_mikrotik == 'pin')
            document.getElementById('mikrotik-pin-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const pin = document.getElementById('pin').value;
                if (!pin) {
                    alert('Por favor ingresa el PIN');
                    return;
                }
                console.log('PIN enviado:', pin);
                alert('Conectado con éxito. Disfruta tu navegación.');
            });
            @endif

            @if($zona->tipo_autenticacion_mikrotik == 'usuario_password')
            document.getElementById('mikrotik-user-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                if (!username || !password) {
                    alert('Por favor ingresa usuario y contraseña');
                    return;
                }
                console.log('Credenciales enviadas:', { username, password });
                alert('Conectado con éxito. Disfruta tu navegación.');
            });
            @endif

            // Iniciar contador si no hay formulario
            @if(!$mostrarFormulario)
                iniciarContador();
            @endif

            // Registrar métrica de entrada
            const metricaData = {
                zona_id: {{ $zona->id }},
                mac_address: '{{ $mikrotikData["mac"] ?? "" }}',
                tipo_visual: '{{ $videoUrl ? "video" : (count($imagenes) > 0 ? "carrusel" : "formulario") }}'
            };

            fetch('/hotspot-metrics/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(metricaData)
            }).catch(error => console.log('Error registrando métrica:', error));
        });
    </script>

    <!-- Scripts de configuración adicionales -->
    {!! $zona->script_body ?? '' !!}
</body>
</html>
