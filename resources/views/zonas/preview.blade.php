<!DOCTYPE html>
<html lang="es">
<head>
    <title>Vista previa de portal cautivo - {{ $zona->nombre }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts - Inter y Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Variables de colores personalizables
         * Puedes cambiar estos valores para personalizar todo el diseño del portal
         */
        :root {
            --color-background: #f9fafb;
            --color-primary: #021869;
            --color-primary-light: rgba(2, 24, 105, 0.1);
            --color-secondary: #04a4e2;
            --color-secondary-light: rgba(4, 164, 226, 0.15);
            --color-secondary-dark: #0388c1;
            --color-text: #1f2937;
            --color-text-light: #6b7280;
            --color-border: #e5e7eb;
            --color-input-focus: #dbeafe;
            --color-button-hover: #031352;
            --color-success: #10b981;
            --color-success-light: rgba(16, 185, 129, 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-full: 9999px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --animation-speed: 0.2s;
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 3px var(--color-secondary-light);
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
            background-color: var(--color-secondary);
            border-color: var(--color-secondary);
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
            background-color: var(--color-secondary-light);
            color: var(--color-secondary);
            position: relative;
        }

        /* Animación del icono WiFi */
        .wifi-icon::before {
            content: '';
            position: absolute;
            width: 90%;
            height: 90%;
            border-radius: var(--radius-full);
            border: 3px solid var(--color-secondary);
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
            color: var(--color-secondary);
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
        }

        /* Mejoras de accesibilidad */
        @media (prefers-reduced-motion: reduce) {
            button, .preview-container, input, select, textarea, .wifi-icon::before {
                transition: none;
                animation: none;
            }
        }

        /* Script personalizado para head */
        {!! $zona->script_head !!}
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h1 class="text-2xl font-bold text-gray-800">Vista previa: {{ $zona->nombre }}</h1>
            <a href="{{ route('cliente.zonas.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-200 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                </svg>
                Volver a zonas
            </a>
        </div>

        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-r-md shadow-sm" role="alert">
            <p class="font-bold">Modo vista previa</p>
            <p>Esta es una simulación de cómo se verá su portal cautivo. Los datos de Mikrotik son ficticios y no representa exactamente cómo se verá en un dispositivo real.</p>
        </div>

        <div class="preview-container">
            <div class="preview-notice">Vista previa del portal cautivo</div>
            <div class="preview-content">
                <div class="wifi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.143 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                    </svg>
                </div>

                <div class="mb-6 text-center">
                    <h2 class="text-2xl font-bold text-primary mb-2" style="color: var(--color-primary)">{{ $zona->nombre }}</h2>
                    <p class="text-gray-600">Conéctate a nuestra red WiFi</p>
                </div>

                @if($zona->tipo_registro == 'sin_registro' || !$mostrarFormulario)
                    <div class="status-container">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>Esta zona está configurada para acceso sin registro.</p>
                    </div>

                    <div class="mt-6 text-center">
                        <button class="w-full sm:w-auto px-6 py-3">
                            <span class="flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Conectar ahora
                            </span>
                        </button>
                    </div>
                @else
                    <form action="#" method="post" class="space-y-6">
                        <!-- Campos dinámicos del formulario -->
                        @foreach($camposHtml as $campoHtml)
                            {!! $campoHtml !!}
                        @endforeach

                        <div class="divider"></div>

                        @if($zona->login_sin_registro)
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
                            <div class="mt-4 text-center">
                                <a href="#" class="text-sm text-gray-600 hover:text-gray-800 underline transition duration-150">No quiero registrarme, solo conectar</a>
                            </div>
                        @else
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
                        @endif
                    </form>
                @endif

                <div class="mt-8 pt-4 border-t border-gray-100 text-xs text-gray-500 text-center">
                    <p>Al conectarte, aceptas nuestras <a href="#" class="text-secondary hover:underline" style="color: var(--color-secondary)">políticas de uso</a> y <a href="#" class="text-secondary hover:underline" style="color: var(--color-secondary)">privacidad</a></p>
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

        <!-- Sección de ayuda para personalización -->
        <div class="mt-8 bg-blue-50 p-6 rounded-lg shadow-sm border border-blue-100">
            <h3 class="text-lg font-semibold mb-2 text-blue-800">Guía de personalización</h3>
            <p class="text-sm text-blue-700 mb-3">Para personalizar este portal, puedes editar las variables CSS en la zona y añadir scripts personalizados.</p>

            <details class="text-sm text-blue-600">
                <summary class="font-medium cursor-pointer mb-2">Ver consejos de personalización</summary>
                <ul class="list-disc pl-5 space-y-2 text-blue-700">
                    <li>Modifica las variables CSS en <code>:root</code> para cambiar colores, bordes y sombras</li>
                    <li>Añade tu logotipo en la zona de scripts personalizados</li>
                    <li>Personaliza los textos desde el panel de administración</li>
                    <li>Modifica los estilos de formulario según tu identidad visual</li>
                </ul>
            </details>
        </div>
    </div>

    <!-- Script personalizado para body -->
    {!! $zona->script_body !!}
</body>
</html>
