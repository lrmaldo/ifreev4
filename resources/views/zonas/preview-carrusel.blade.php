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

        /* Ajustes globales de diseño */
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            line-height: 1.6;
        }

        /* Contenedor principal del portal cautivo */
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

        /* Barra superior del portal cautivo */
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

        /* Contenido principal del portal */
        .preview-content {
            padding: 2.5rem;
            background-color: white;
            position: relative;
        }

        /* Decoración de fondo con ondas sutiles */
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

        /* Elementos del formulario mejorados */
        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            outline: none;
            transition: all var(--animation-speed) ease-in-out;
            font-size: 0.95rem;
            background-color: white;
            box-shadow: var(--shadow-sm);
        }

        input:hover, select:hover, textarea:hover {
            border-color: #cbd5e0;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-light);
        }

        /* Checkboxes y radios personalizados */
        input[type="checkbox"], input[type="radio"] {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid var(--color-border);
            background-color: white;
            cursor: pointer;
        }

        input[type="checkbox"]:checked, input[type="radio"]:checked {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }

        /* Etiquetas de campos */
        label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
            color: var(--color-text);
            font-size: 0.9rem;
            transition: color var(--animation-speed) ease;
        }

        /* Botones estilizados */
        button {
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            color: white;
            border-radius: var(--radius-md);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            transition: all var(--animation-speed) ease;
            border: none;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            background: linear-gradient(90deg, var(--color-button-hover), var(--color-secondary-dark));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        button:active {
            transform: translateY(0);
        }

        /* Efecto de onda al hacer click en botones */
        button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        button:focus:not(:active)::after {
            animation: ripple 0.5s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(30, 30);
                opacity: 0;
            }
        }

        /* Elementos decorativos y utilidades */
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, var(--color-border), transparent);
            width: 100%;
            margin: 1.75rem 0;
        }

        /* Icono de WiFi estilizado */
        .wifi-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: var(--radius-full);
            background-color: var(--color-primary-light);
            color: var(--color-primary);
            position: relative;
        }

        /* Animación del icono WiFi */
        .wifi-icon::before {
            content: '';
            position: absolute;
            width: 90%;
            height: 90%;
            border-radius: var(--radius-full);
            border: 3px solid var(--color-primary);
            opacity: 0.7;
            animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        @keyframes ping {
            75%, 100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        /* Estilos para texto y elementos especiales */
        .highlight {
            color: var(--color-primary);
            font-weight: 500;
        }

        .card {
            border-radius: var(--radius-md);
            padding: 1.5rem;
            background-color: white;
            box-shadow: var(--shadow-md);
            transition: box-shadow var(--animation-speed) ease;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        /* Contenedor de mensajes de estado */
        .status-container {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
            background-color: var(--color-success-light);
            border: 1px solid var(--color-success);
            color: var(--color-success);
        }

        .status-container svg {
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        /* Estilos para el carrusel */
        #carrusel-container {
            position: relative;
            width: 100%;
            height: 240px;
            overflow: hidden;
            border-radius: var(--radius-md);
            margin: 1.5rem 0;
            opacity: 0;
            transform: scale(0.95);
            transition: opacity var(--animation-speed) ease-in-out, transform var(--animation-speed) ease-in-out;
        }

        #carrusel-container.active {
            opacity: 1;
            transform: scale(1);
        }

        .carrusel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            object-fit: cover;
            border-radius: var(--radius-md);
        }

        .carrusel-slide.active {
            opacity: 1;
        }

        #contador-container {
            position: relative;
            width: 100%;
            text-align: center;
            margin-top: 1.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--color-primary);
            opacity: 0;
            transform: translateY(10px);
            transition: opacity var(--animation-speed) ease-in-out, transform var(--animation-speed) ease-in-out;
        }

        #contador-container.active {
            opacity: 1;
            transform: translateY(0);
        }

        #mensaje-final {
            text-align: center;
            padding: 2rem;
            opacity: 0;
            transform: scale(0.9);
            transition: opacity var(--animation-speed) ease-in-out, transform var(--animation-speed) ease-in-out;
        }

        #mensaje-final.active {
            opacity: 1;
            transform: scale(1);
        }

        .mensaje-exito {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--color-success);
            margin-bottom: 1rem;
        }

        .dot-container {
            text-align: center;
            margin-top: 0.5rem;
        }

        .dot {
            height: 8px;
            width: 8px;
            margin: 0 5px;
            background-color: var(--color-border);
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.5s ease;
        }

        .dot.active {
            background-color: var(--color-primary);
        }

        /* Media queries para responsive design */
        @media (max-width: 640px) {
            .preview-content {
                padding: 1.75rem 1.5rem;
            }

            .preview-notice {
                padding: 10px;
                font-size: 14px;
            }

            button {
                padding: 0.7rem 1.25rem;
            }

            .wifi-icon {
                width: 60px;
                height: 60px;
                margin-bottom: 1.25rem;
            }

            #carrusel-container {
                height: 180px;
            }
        }

        /* Mejoras de accesibilidad */
        @media (prefers-reduced-motion: reduce) {
            button, .preview-container, input, select, textarea, .wifi-icon::before {
                transition: none;
                animation: none;
            }
        }

        /* Paso entre vistas */
        .step {
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity var(--animation-speed) ease-in-out, transform var(--animation-speed) ease-in-out;
        }

        .step.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        /* Script personalizado para head */
        {!! $zona->script_head !!}
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-2xl font-bold text-gray-800">Vista previa con carrusel: {{ $zona->nombre }}</h1>
            <a href="{{ route('cliente.zonas.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-200 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                </svg>
                Volver a zonas
            </a>
        </div>

        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-r-md shadow-sm" role="alert">
            <p class="font-bold">Modo vista previa - Carrusel</p>
            @if($zona->tipo_registro == 'sin_registro')
            <p>Esta es una simulación de cómo se verá su portal cautivo con carrusel de imágenes. Como la zona está configurada para acceso sin registro, el carrusel se muestra directamente con un contador regresivo.</p>
            @else
            <p>Esta es una simulación de cómo se verá su portal cautivo con carrusel de imágenes. Complete el formulario para ver la demostración del carrusel y el contador regresivo.</p>
            @endif
        </div>

        <div class="preview-container">
            <div class="preview-notice">Vista previa del portal cautivo - Carrusel</div>
            <div class="preview-content">
                @if($zona->tipo_registro == 'sin_registro')
                <!-- Acceso directo sin registro -->
                <div id="paso-carrusel" class="step active">
                    <div class="wifi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.143 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                        </svg>
                    </div>

                    <div class="mb-6 text-center">
                        <h2 class="text-2xl font-bold mb-2" style="color: var(--color-primary)">{{ $zona->nombre }}</h2>
                        <p class="text-gray-600">Mira nuestras promociones mientras preparamos tu conexión</p>
                    </div>

                    <!-- Carrusel de imágenes -->
                    <div id="carrusel-container" class="active">
                        @foreach($imagenes as $index => $imagen)
                            <img src="{{ $imagen }}" alt="Slide {{ $index + 1 }}" class="carrusel-slide {{ $index === 0 ? 'active' : '' }}">
                        @endforeach
                    </div>

                    <!-- Indicadores del carrusel -->
                    <div class="dot-container">
                        @foreach($imagenes as $index => $imagen)
                            <span class="dot {{ $index === 0 ? 'active' : '' }}"></span>
                        @endforeach
                    </div>

                    <!-- Contador regresivo -->
                    <div id="contador-container" class="active">
                        <div>Conectando en <span id="contador">10</span> segundos</div>
                    </div>
                </div>
                @else
                <!-- Paso 1: Formulario inicial -->
                <div id="paso-formulario" class="step active">
                    <div class="wifi-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.143 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                        </svg>
                    </div>

                    <div class="mb-6 text-center">
                        <h2 class="text-2xl font-bold mb-2" style="color: var(--color-primary)">{{ $zona->nombre }}</h2>
                        <p class="text-gray-600">Completa el formulario para conectarte</p>
                    </div>

                    <form id="formulario-wifi" class="space-y-6">
                        <!-- Campos dinámicos del formulario -->
                        @foreach($camposHtml as $campoHtml)
                            {!! $campoHtml !!}
                        @endforeach

                        <div class="divider"></div>

                        <div class="mt-6">
                            <button type="submit" class="w-full py-3">
                                <span class="flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    Registrarse y conectar
                                </span>
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 pt-4 border-t border-gray-100 text-xs text-gray-500 text-center">
                        <p>Al conectarte, aceptas nuestras <a href="#" class="hover:underline" style="color: var(--color-primary)">políticas de uso</a> y <a href="#" class="hover:underline" style="color: var(--color-primary)">privacidad</a></p>
                    </div>
                </div>
                @endif

                <!-- Paso 2: Carrusel con contador -->
                <div id="paso-carrusel" class="step">
                    <div class="mb-6 text-center">
                        <h2 class="text-2xl font-bold mb-2" style="color: var(--color-primary)">¡Gracias por registrarte!</h2>
                        <p class="text-gray-600">Mira nuestras promociones mientras preparamos tu conexión</p>
                    </div>

                    <!-- Carrusel de imágenes -->
                    <div id="carrusel-container" class="active">
                        @foreach($imagenes as $index => $imagen)
                            <img src="{{ $imagen }}" alt="Slide {{ $index + 1 }}" class="carrusel-slide {{ $index === 0 ? 'active' : '' }}">
                        @endforeach
                    </div>

                    <!-- Indicadores del carrusel -->
                    <div class="dot-container">
                        @foreach($imagenes as $index => $imagen)
                            <span class="dot {{ $index === 0 ? 'active' : '' }}"></span>
                        @endforeach
                    </div>

                    <!-- Contador regresivo -->
                    <div id="contador-container" class="active">
                        <div>Conectando en <span id="contador">10</span> segundos</div>
                    </div>
                </div>

                <!-- Paso 3: Mensaje de éxito -->
                <div id="paso-exito" class="step">
                    <div id="mensaje-final" class="active">
                        <div class="wifi-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="mensaje-exito">¡Acceso concedido!</h2>
                        <p class="text-gray-600 mb-4">Ya puedes disfrutar de nuestra conexión WiFi</p>
                        <div class="status-container">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>Conexión establecida correctamente</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">Datos simulados de Mikrotik</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($mikrotikData as $key => $value)
                    <div class="border-b border-gray-100 pb-2">
                        <span class="font-mono text-sm text-gray-600 font-medium">{{ $key }}:</span>
                        <span class="ml-2 font-mono text-sm">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del DOM
            const formulario = document.getElementById('formulario-wifi');
            const pasoFormulario = document.getElementById('paso-formulario');
            const pasoCarrusel = document.getElementById('paso-carrusel');
            const pasoExito = document.getElementById('paso-exito');
            const contador = document.getElementById('contador');
            const slides = document.querySelectorAll('.carrusel-slide');
            const dots = document.querySelectorAll('.dot');

            let currentSlide = 0;
            let tiempoRestante = 10;
            let carruselInterval;
            let contadorInterval;

            // Función para cambiar de slide en el carrusel
            function cambiarSlide() {
                // Quitar la clase active de todos los slides y dots
                slides.forEach(slide => slide.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));

                // Incrementar el índice del slide
                currentSlide = (currentSlide + 1) % slides.length;

                // Añadir la clase active al slide y dot actual
                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');
            }

            // Función para actualizar el contador regresivo
            function actualizarContador() {
                tiempoRestante--;
                contador.textContent = tiempoRestante;

                if (tiempoRestante <= 0) {
                    clearInterval(contadorInterval);
                    clearInterval(carruselInterval);

                    // Cambiar al paso de éxito con transiciones suaves
                    setTimeout(() => {
                        pasoCarrusel.classList.remove('active');
                        setTimeout(() => {
                            pasoExito.classList.add('active');
                        }, 400);
                    }, 500);
                }
            }

            // En caso de acceso sin registro, iniciar directamente el carrusel y el contador
            @if($zona->tipo_registro == 'sin_registro')
                // Iniciar el carrusel automáticamente
                carruselInterval = setInterval(cambiarSlide, 2000);

                // Iniciar el contador automáticamente
                contadorInterval = setInterval(actualizarContador, 1000);
            @else
                // Evento submit del formulario solo si hay formulario
                if(formulario) {
                    formulario.addEventListener('submit', function(e) {
                        e.preventDefault();

                        // Cambiar al paso del carrusel con transiciones suaves
                        pasoFormulario.classList.remove('active');
                        setTimeout(() => {
                            pasoCarrusel.classList.add('active');

                            // Iniciar el carrusel
                            slides[0].classList.add('active');
                            dots[0].classList.add('active');
                            carruselInterval = setInterval(cambiarSlide, 2000);

                            // Iniciar el contador
                            contadorInterval = setInterval(actualizarContador, 1000);
                        }, 400);
                    });
                }
            @endif
        });
    </script>

    <!-- Script personalizado para body -->
    {!! $zona->script_body !!}
</body>
</html>
