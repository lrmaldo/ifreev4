<!DOCTYPE html>
<html lang="es">
<head>
    <title>Portal Cautivo - {{ $zona->nombre }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Fuentes locales (reemplazan Google Fonts para funcionar sin internet) -->
    <link rel="stylesheet" href="{{ asset('css/fonts-local.css') }}">

    <!-- Google Fonts -->
    {{-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"> --}}

    <!-- Tailwind CSS del sistema -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Swiper CSS local -->
    <link rel="stylesheet" href="{{ asset('css/swiper-local.css') }}">

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
            font-family: var(--font-inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Open Sans', 'Helvetica Neue', sans-serif);
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
            position: relative;
        }

        /* Indicadores de tipo de contenido */
        .content-type-indicator {
            position: absolute;
            top: 0;
            right: 0;
            padding: 3px 6px;
            font-size: 10px;
            border-radius: 0 0 0 5px;
            font-weight: bold;
            opacity: 0.85;
        }

        .video-indicator {
            background-color: #3b82f6; /* azul */
            display: {{ !empty($videoUrl) ? 'block' : 'none' }};
        }

        .image-indicator {
            background-color: #10b981; /* verde */
            display: {{ !empty($imagenes) && empty($videoUrl) ? 'block' : 'none' }};
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

        /* Botón para enlace de campaña */
        .btn-enlace-campana {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: white;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: all var(--animation-speed) ease;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-enlace-campana:hover {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        .btn-enlace-campana:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        .btn-enlace-campana svg {
            transition: transform var(--animation-speed) ease;
        }

        .btn-enlace-campana:hover svg {
            transform: translateX(2px);
        }

        /* Responsive para botón de enlace */
        @media (max-width: 480px) {
            .btn-enlace-campana {
                font-size: 0.8rem;
                padding: 0.625rem 0.875rem;
            }
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
            position: relative;
            overflow: hidden;
        }

        .swiper-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .swiper-slide {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Reproductor de video personalizado */
        .video-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            border-radius: var(--radius-md);
        }

        .video-player {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Ocultar controles nativos del video */
        .video-player::-webkit-media-controls {
            display: none !important;
        }

        .video-player::-webkit-media-controls-enclosure {
            display: none !important;
        }

        .video-player::-webkit-media-controls-panel {
            display: none !important;
        }

        .video-player::-webkit-media-controls-play-button {
            display: none !important;
        }

        .video-player::-webkit-media-controls-timeline {
            display: none !important;
        }

        /* Controles personalizados */
        .video-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            padding: 10px;
            display: flex;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .video-container:hover .video-controls {
            opacity: 1;
        }

        .mute-btn {
            background: transparent;
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            padding: 6px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mute-btn:hover {
            background-color: var(--color-primary);
        }

        .mute-btn svg {
            transition: all 0.2s ease;
        }

        .mute-btn:hover svg {
            transform: scale(1.1);
        }

        .progress-container {
            flex-grow: 1;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            position: relative;
            cursor: default;
            pointer-events: none;
            margin-left: 10px;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--color-primary);
            border-radius: 2px;
            width: 0;
        }

        /* Estilos para slides activos/inactivos en modo fade */
        .swiper-container-fade .swiper-slide {
            opacity: 0 !important;
            transition: opacity 0.5s ease;
        }

        .swiper-container-fade .swiper-slide-active {
            opacity: 1 !important;
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
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
            padding: 0.85rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1.05rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            transition: all var(--animation-speed) ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .btn-connection:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--color-secondary-dark), var(--color-primary));
        }

        /* Efecto de animación pulsante para el botón de conexión cuando termine el video */
        @keyframes animatePulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 94, 44, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 94, 44, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 94, 44, 0);
            }
        }

        /* Efecto de onda al hacer click */
        .btn-connection::after {
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

        .btn-connection:focus:not(:active)::after {
            animation: ripple 0.8s ease-out;
        }

        /* Animación cuando termina el video */
        .animate-pulse {
            animation: animatePulse 1.5s infinite;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.5;
            }
            100% {
                transform: scale(50, 50);
                opacity: 0;
            }
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

        .auth-form h3 {
            color: var(--color-primary);
            margin-bottom: 1rem;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
        }

        .auth-form .form-field {
            margin-bottom: 1rem;
        }

        .auth-form input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: border-color var(--animation-speed) ease, box-shadow var(--animation-speed) ease;
            background-color: white;
        }

        .auth-form input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-input-focus);
        }

        .auth-form button {
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

        .auth-form button:hover {
            background-color: var(--color-button-hover);
        }

        .auth-form .text-red-500 {
            color: #dc2626;
            font-size: 0.75rem;
            text-align: center;
            margin-top: 0.5rem;
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

        /* Estilos para modal de enlace */
        .modal-enlace {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeInModal 0.3s ease-out;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            box-sizing: border-box;
        }

        .modal-enlace-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: -1;
        }

        .modal-enlace-content {
            position: relative;
            width: 100%;
            max-width: 1200px;
            height: 90vh;
            max-height: calc(100vh - 20px);
            background: white;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideInModal 0.3s ease-out;
            display: flex;
            flex-direction: column;
        }

        .modal-enlace-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            min-height: 60px;
        }

        .modal-enlace-titulo {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
            flex: 1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 1rem;
        }

        .modal-enlace-cerrar {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 36px;
            height: 36px;
        }

        .modal-enlace-cerrar:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }

        .modal-enlace-cerrar:active {
            transform: scale(0.95);
        }

        .modal-enlace-body {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .modal-enlace-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
            background: #f8f9fa;
        }

        /* Animaciones del modal */
        @keyframes fadeInModal {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideInModal {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Responsive para el modal */
        @media (max-width: 768px) {
            .modal-enlace {
                padding: 5px;
            }

            .modal-enlace-content {
                width: 100%;
                height: 95vh;
                max-height: calc(100vh - 10px);
                border-radius: 8px;
            }

            .modal-enlace-header {
                padding: 0.75rem 1rem;
                min-height: 50px;
            }

            .modal-enlace-titulo {
                font-size: 1rem;
                padding-right: 0.5rem;
            }

            .modal-enlace-cerrar {
                padding: 0.25rem;
                width: 32px;
                height: 32px;
            }
        }

        @media (max-width: 480px) {
            .modal-enlace {
                padding: 0;
            }

            .modal-enlace-content {
                width: 100%;
                height: 100vh;
                max-height: 100vh;
                border-radius: 0;
            }

            .modal-enlace-header {
                padding: 0.5rem 0.75rem;
                min-height: 48px;
            }

            .modal-enlace-titulo {
                font-size: 0.9rem;
                padding-right: 0.25rem;
            }

            .modal-enlace-cerrar {
                width: 28px;
                height: 28px;
            }
        }

            .modal-enlace-header {
                padding: 0.5rem 0.75rem;
            }

            .modal-enlace-titulo {
                font-size: 0.9rem;
            }

            .modal-enlace-body {
                height: calc(100% - 55px);
            }
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

            <!-- Indicadores visuales mejorados del tipo de contenido -->
            <div class="content-type-indicator video-indicator">
                📹 Video
            </div>
            <div class="content-type-indicator image-indicator">
                🖼️ Imágenes ({{ count($imagenes ?? []) }})
            </div>
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

                <!-- DEBUG: Información de campaña (temporal) -->
                @if(config('app.debug'))
                    <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px; border-radius: 4px;">
                        <strong>DEBUG - Información de campaña:</strong><br>
                        Campaña seleccionada: {{ $campanaSeleccionada ? 'SÍ' : 'NO' }}<br>
                        @if($campanaSeleccionada)
                            ID: {{ $campanaSeleccionada->id ?? 'N/A' }}<br>
                            Título: {{ $campanaSeleccionada->titulo ?? 'N/A' }}<br>
                            Enlace: {{ $campanaSeleccionada->enlace ?? 'NO HAY ENLACE' }}<br>
                            Tipo: {{ $campanaSeleccionada->tipo ?? 'N/A' }}<br>
                        @endif
                    </div>
                @endif

                <!-- Enlace de la campaña en formulario -->
                @if($campanaSeleccionada && $campanaSeleccionada->enlace)
                    <div class="text-center mb-6">
                        <button
                            onclick="abrirModalEnlace('{{ $campanaSeleccionada->enlace }}', '{{ $campanaSeleccionada->titulo ?? $campanaSeleccionada->nombre }}')"
                            class="btn-enlace-campana"
                            type="button"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            Ver más información
                        </button>
                    </div>
                @else
                    <!-- DEBUG: Mostrar por qué no aparece el botón -->
                    @if(config('app.debug'))
                        <div style="background: #ffebee; padding: 10px; margin: 10px 0; font-size: 12px; border-radius: 4px; color: #c62828;">
                            <strong>DEBUG - ¿Por qué no aparece el botón?</strong><br>
                            @if(!$campanaSeleccionada)
                                ❌ No hay campaña seleccionada
                            @elseif(!$campanaSeleccionada->enlace)
                                ❌ La campaña no tiene enlace configurado
                            @else
                                ❓ Razón desconocida
                            @endif
                        </div>
                    @endif
                @endif

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

                    <!-- DEBUG: Información de campaña (temporal) -->
                    @if(config('app.debug'))
                        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px; border-radius: 4px;">
                            <strong>DEBUG - Información de campaña (sin formulario):</strong><br>
                            Campaña seleccionada: {{ $campanaSeleccionada ? 'SÍ' : 'NO' }}<br>
                            @if($campanaSeleccionada)
                                ID: {{ $campanaSeleccionada->id ?? 'N/A' }}<br>
                                Título: {{ $campanaSeleccionada->titulo ?? 'N/A' }}<br>
                                Enlace: {{ $campanaSeleccionada->enlace ?? 'NO HAY ENLACE' }}<br>
                                Tipo: {{ $campanaSeleccionada->tipo ?? 'N/A' }}<br>
                            @endif
                        </div>
                    @endif

                    <!-- Enlace de la campaña -->
                    @if($campanaSeleccionada && $campanaSeleccionada->enlace)
                        <div class="text-center mb-4">
                            <button
                                onclick="abrirModalEnlace('{{ $campanaSeleccionada->enlace }}', '{{ $campanaSeleccionada->titulo ?? $campanaSeleccionada->nombre }}')"
                                class="btn-enlace-campana"
                                type="button"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Ver más información
                            </button>
                        </div>
                    @else
                        <!-- DEBUG: Mostrar por qué no aparece el botón -->
                        @if(config('app.debug'))
                            <div style="background: #ffebee; padding: 10px; margin: 10px 0; font-size: 12px; border-radius: 4px; color: #c62828;">
                                <strong>DEBUG - ¿Por qué no aparece el botón? (sin formulario)</strong><br>
                                @if(!$campanaSeleccionada)
                                    ❌ No hay campaña seleccionada
                                @elseif(!$campanaSeleccionada->enlace)
                                    ❌ La campaña no tiene enlace configurado
                                @else
                                    ❓ Razón desconocida
                                @endif
                            </div>
                        @endif
                    @endif
                @endif

                <!-- BOTÓN DE PRUEBA TEMPORAL (solo en modo debug) -->
                @if(config('app.debug'))
                    <div class="text-center mb-6" style="background: #e3f2fd; padding: 15px; border-radius: 8px;">
                        <strong>🧪 PRUEBA TEMPORAL - Modal de Enlace</strong><br>
                        <div class="flex flex-wrap gap-2 justify-center mt-2">
                            <button
                                onclick="abrirModalEnlace('https://www.google.com', 'Google - Prueba Modal')"
                                class="btn-enlace-campana"
                                type="button"
                                style="margin: 5px;"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                🧪 Google
                            </button>

                            <button
                                onclick="abrirModalEnlace('https://example.com', 'Example.com - Prueba Modal')"
                                class="btn-enlace-campana"
                                type="button"
                                style="margin: 5px;"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                🧪 Example.com
                            </button>

                            <button
                                onclick="abrirModalEnlace('http://sattlink.com/', 'Sattlink - Prueba')"
                                class="btn-enlace-campana"
                                type="button"
                                style="margin: 5px; background-color: #ff5e2c;"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                🧪 Sattlink
                            </button>
                        </div>
                        <div class="text-sm text-gray-600 mt-2">
                            Botones de prueba para verificar el comportamiento del modal con diferentes URLs
                        </div>
                    </div>
                @endif

                @if($videoUrl)
                    <!-- Video -->
                    <div class="content-carousel">
                        <div class="video-container">
                            <video id="campaign-video" autoplay muted playsinline class="video-player" src="{{ $videoUrl }}" controlsList="nodownload noplaybackrate">
                                Tu navegador no soporta reproducción de video.
                            </video>
                            <div class="video-controls">
                                <button id="mute-btn" class="mute-btn" title="Activar/Desactivar audio">
                                    <svg id="volume-on-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#ff5e2c" style="display: none;">
                                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                                    </svg>
                                    <svg id="volume-off-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#ff5e2c">
                                        <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                                    </svg>
                                </button>
                                <div class="progress-container">
                                    <div id="progress-bar" class="progress-bar"></div>
                                </div>
                            </div>
                        </div>
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

                <!-- Contador regresivo (solo para carrusel) -->
                @if(!$videoUrl)
                <div class="countdown-container">
                    <div class="countdown" id="countdown">{{ $tiempoVisualizacion }}</div>
                    <div class="ml-2 text-sm">segundos para tu acceso</div>
                </div>
                @endif

                <!-- Mensaje de estado -->
                <div class="text-center mt-4 text-sm text-gray-500" id="status-message">
                    @if($videoUrl)
                        <p>Mira el video completo para conectarte <span id="video-progress"></span></p>
                    @elseif($zona->tipo_autenticacion_mikrotik == 'sin_autenticacion')
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
                        @elseif($videoUrl)
                            <p>¡Video completado! Ya puedes conectarte:</p>
                        @else
                            <p>¿No tienes credenciales? Prueba nuestra conexión gratuita:</p>
                        @endif
                    </div>
                    <button type="button" onclick="doTrial()" class="btn-connection" id="gratis">
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
                        <form name="login" action="{{ $mikrotikData['link-login-only'] ?? '' }}" method="post" class="space-y-3" onSubmit="return doLogin()">
                            <input type="hidden" name="dst" value="{{ $mikrotikData['link-orig'] ?? '' }}" />
                            <input type="hidden" name="popup" value="true" />

                            <div class="form-field">
                                <input type="text" name="username" id="pin-username" placeholder="Introduce el PIN"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                            </div>

                            <button type="submit" class="btn-primary w-full">Conectar con PIN</button>

                            @if(!empty($mikrotikData['error']))
                                <div class="text-center text-xs text-red-500 mt-2">
                                    {{ $mikrotikData['error'] }}
                                </div>
                            @endif

                            <div class="text-center text-xs text-gray-500">
                                <p>Solicita tu PIN al establecimiento</p>
                            </div>
                        </form>
                    </div>
                @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                    <div class="auth-form hidden" id="user-form">
                        <h3 class="text-md font-semibold mb-3">Ingresa tus credenciales</h3>
                        <form name="login" action="{{ $mikrotikData['link-login-only'] ?? '' }}" method="post" class="space-y-3" onSubmit="return doLogin()">
                            <input type="hidden" name="dst" value="{{ $mikrotikData['link-orig'] ?? '' }}" />
                            <input type="hidden" name="popup" value="true" />

                            <div class="form-field">
                                <input type="text" name="username" id="username" placeholder="Usuario"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                            </div>

                            <div class="form-field">
                                <input type="password" name="password" id="password" placeholder="Contraseña"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">
                            </div>

                            <button type="submit" class="btn-primary w-full">Entrar</button>

                            @if(!empty($mikrotikData['error']))
                                <div class="text-center text-xs text-red-500 mt-2">
                                    {{ $mikrotikData['error'] }}
                                </div>
                            @endif
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal para mostrar enlace de campaña -->
    <div id="modalEnlace" class="modal-enlace" style="display: none;">
        <div class="modal-enlace-overlay" onclick="cerrarModalEnlace()"></div>
        <div class="modal-enlace-content">
            <div class="modal-enlace-header">
                <h3 id="modalEnlaceTitulo" class="modal-enlace-titulo"></h3>
                <button onclick="cerrarModalEnlace()" class="modal-enlace-cerrar" aria-label="Cerrar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="modal-enlace-body">
                <iframe id="modalEnlaceIframe" src="" frameborder="0" class="modal-enlace-iframe"></iframe>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para autenticación CHAP con Mikrotik -->
    <form name="sendin" action="{{ $mikrotikData['link-login-only'] ?? '' }}" method="post" style="display: none;">
        <input type="hidden" name="username" />
        <input type="hidden" name="password" />
        <input type="hidden" name="dst" value="{{ $mikrotikData['link-orig'] ?? '' }}" />
    </form>

    <!-- Scripts -->
    <script src="{{ asset('js/md5.js') }}"></script>
    <script src="{{ asset('js/swiper-local.js') }}"></script>
    <script>
        // Función de log personalizada para depuración
        function logDebug(message, type = 'info') {
            const prefix = '[Portal Cautivo] ';
            if (type === 'error') {
                console.error(prefix + message);
            } else if (type === 'warn') {
                console.warn(prefix + message);
            } else {
                console.log(prefix + message);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Configuración inicial
            const stepForm = document.getElementById('step-form');
            const stepContent = document.getElementById('step-content');
            const portalForm = document.getElementById('portal-form');
            const countdown = document.getElementById('countdown');
            const connectionContainer = document.getElementById('connection-container');
            const statusMessage = document.getElementById('status-message');

            // Log información inicial
            logDebug('Portal cautivo inicializado');
            logDebug('Imágenes disponibles: {{ count($imagenes) }}');

            @foreach($imagenes as $index => $imagen)
                logDebug('Imagen {{ $index + 1 }}: {{ $imagen }}');
            @endforeach

            let tiempoRestante = {{ $tiempoVisualizacion }};
            let countdownInterval;
            let tiempoInicio = Date.now();

            // Configurar CSRF token para peticiones AJAX
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Inicializar reproductor de video personalizado si existe
            @if($videoUrl)
            const video = document.getElementById('campaign-video');
            const progressBar = document.getElementById('progress-bar');
            const muteBtn = document.getElementById('mute-btn');
            const volumeOnIcon = document.getElementById('volume-on-icon');
            const volumeOffIcon = document.getElementById('volume-off-icon');

            // Configurar el estado inicial de los iconos de volumen
            volumeOnIcon.style.display = 'none';
            volumeOffIcon.style.display = 'block';

            // Configuración inicial del video
            video.addEventListener('loadedmetadata', function() {
                logDebug('Video cargado: duración ' + video.duration + ' segundos');

                // Registrar métrica de video iniciado
                actualizarMetrica({
                    tipo_visual: 'video',
                    detalle: 'video_iniciado',
                    duracion_visual: 0
                });

                // Asegurarse de que el video comience a reproducirse
                video.play().catch(error => {
                    logDebug('Error al reproducir video automáticamente: ' + error.message);
                    // Muchos navegadores requieren interacción del usuario para reproducir video con audio

                    // Mostrar mensaje pidiendo interacción
                    const statusMsg = document.getElementById('status-message');
                    if (statusMsg) {
                        statusMsg.innerHTML = '<p>Toca la pantalla para iniciar el video</p>';

                        // Añadir evento click al video para iniciarlo
                        video.addEventListener('click', function videoClickHandler() {
                            video.play().catch(e => logDebug('Error al reproducir: ' + e));
                            statusMsg.innerHTML = '<p>Mira el video completo para conectarte <span id="video-progress"></span></p>';

                            // Remover este evento después del primer clic
                            video.removeEventListener('click', videoClickHandler);
                        });
                    }

                    // Registrar error de reproducción
                    actualizarMetrica({
                        tipo_visual: 'video',
                        detalle: 'error_reproduccion_automatica',
                        error: error.message
                    });
                });
            });

            // Detectar errores de video
            video.addEventListener('error', function() {
                logDebug('Error en la reproducción de video: ' + (video.error ? video.error.message : 'desconocido'));

                // Registrar error
                actualizarMetrica({
                    tipo_visual: 'video',
                    detalle: 'error_video',
                    error: video.error ? video.error.code : 'error_desconocido'
                });

                // Mostrar mensaje de error y opciones de conexión
                const statusMsg = document.getElementById('status-message');
                if (statusMsg) {
                    statusMsg.innerHTML = '<p class="text-red-500">Error al reproducir el video</p>';
                    // Mostrar opciones de conexión sin esperar
                    mostrarOpcionesConexion();
                }
            });

            // Prevenir que se pueda saltar adelante en el video
            video.addEventListener('seeking', function() {
                if (video.currentTime > video.lastPlayedTime) {
                    video.currentTime = video.lastPlayedTime || 0;
                }
            });

            // Registrar la última posición reproducida
            video.addEventListener('timeupdate', function() {
                // Actualizar barra de progreso
                const percentage = (video.currentTime / video.duration) * 100;
                progressBar.style.width = percentage + '%';

                // Guardar última posición reproducida legítimamente
                video.lastPlayedTime = video.currentTime;

                // Actualizar indicador de progreso (opcional)
                const videoProgress = document.getElementById('video-progress');
                if (videoProgress) {
                    // Mostrar solo cuando el video lleva más de 3 segundos
                    if (video.currentTime > 3) {
                        const remainingTime = Math.ceil(video.duration - video.currentTime);
                        videoProgress.textContent = `(${remainingTime}s)`;
                    }
                }

                // Registrar métricas de progreso cada 10 segundos
                if (Math.floor(video.currentTime) % 10 === 0 && video.lastLoggedTime !== Math.floor(video.currentTime)) {
                    video.lastLoggedTime = Math.floor(video.currentTime);
                    actualizarMetrica({
                        tipo_visual: 'video',
                        duracion_visual: Math.floor(video.currentTime),
                        detalle: 'video_progreso_' + Math.round(video.currentTime)
                    });
                }
            });

            // Al terminar el video, mostrar botón de conexión
            video.addEventListener('ended', function() {
                logDebug('Video completado');
                mostrarOpcionesConexion();

                // Hacer que el botón de conexión destaque más
                const gratuito = document.getElementById('gratis');
                if (gratuito) {
                    gratuito.classList.add('animate-pulse');
                    gratuito.style.boxShadow = '0 0 10px var(--color-primary)';
                }

                // Registrar métrica de finalización
                actualizarMetrica({
                    tipo_visual: 'video',
                    duracion_visual: Math.floor(video.duration),
                    detalle: 'video_completado'
                });
            });

            // Control de volumen
            muteBtn.addEventListener('click', function() {
                if (video.muted) {
                    video.muted = false;
                    volumeOffIcon.style.display = 'none';
                    volumeOnIcon.style.display = 'block';

                    // Registrar métrica de activación de audio
                    actualizarMetrica({
                        tipo_visual: 'video',
                        detalle: 'audio_activado'
                    });
                } else {
                    video.muted = true;
                    volumeOnIcon.style.display = 'none';
                    volumeOffIcon.style.display = 'block';

                    // Registrar métrica de silencio
                    actualizarMetrica({
                        tipo_visual: 'video',
                        detalle: 'audio_silenciado'
                    });
                }
            });
            @endif

            // Inicializar Swiper si hay imágenes
            @if(count($imagenes) > 0)
            const swiper = new SwiperLocal('.swiper-container', {
                loop: {{ count($imagenes) > 1 ? 'true' : 'false' }},
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false
                },
                pagination: true,
                allowTouchMove: {{ count($imagenes) > 1 ? 'true' : 'false' }},
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                }
            });

            // Verificación adicional para debugging
            console.log('Imágenes en carrusel:', {{ count($imagenes) }});
            console.log('Slides en carrusel:', document.querySelectorAll('.swiper-slide').length);

            // Forzar actualización del carrusel para asegurar que todas las diapositivas son visibles
            setTimeout(() => {
                swiper.update();
                if ({{ count($imagenes) > 1 ? 'true' : 'false' }}) {
                    swiper.autoplay.start();
                    console.log('Autoplay iniciado para múltiples imágenes');
                }
            }, 100);
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
                            console.log('Actualizando swiper después de enviar formulario');
                            swiper.update();

                            // Forzar actualización de slides
                            const slides = document.querySelectorAll('.swiper-slide');
                            console.log('Slides disponibles:', slides.length);

                            // Asegurar que la primera diapositiva esté visible
                            if (slides.length > 0) {
                                swiper.goToSlide(0);
                            }

                            // Iniciar autoplay solo si hay más de una imagen
                            if ({{ count($imagenes) > 1 ? 'true' : 'false' }}) {
                                swiper.autoplay.start();
                                console.log('Autoplay iniciado');
                            }
                        }, 200);
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

            // Función para iniciar cuenta regresiva (solo para carrusel de imágenes)
            function iniciarContador() {
                @if(!$videoUrl)
                const countdown = document.getElementById('countdown');
                if (countdown) {
                    countdownInterval = setInterval(function() {
                        tiempoRestante--;
                        countdown.textContent = tiempoRestante;

                        if (tiempoRestante <= 0) {
                            clearInterval(countdownInterval);
                            mostrarOpcionesConexion();
                        }
                    }, 1000);
                }
                @endif
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

                // Animar el botón de conexión
                const gratuito = document.getElementById('gratis');
                if (gratuito) {
                    gratuito.classList.add('animate-pulse');

                    // Si es video, hacer más prominente la animación
                    @if($videoUrl)
                    gratuito.style.boxShadow = '0 0 10px var(--color-primary)';
                    gratuito.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        gratuito.style.transform = 'scale(1)';
                    }, 300);
                    @endif
                }

                // Registrar métrica de visualización completa
                registrarMetrica({
                    tipo_visual: @if($videoUrl) 'video' @else 'carrusel' @endif,
                    duracion_visual: Math.floor((Date.now() - tiempoInicio) / 1000),
                    detalle: 'visualizacion_completa'
                });
            }

            // Función para registrar métricas
            function registrarMetrica(data) {
                const baseData = {
                    zona_id: {{ $zona->id }},
                    mac_address: '{{ $mikrotikData['mac'] ?? 'unknown' }}',
                    dispositivo: extraerInformacionDispositivo(),
                    navegador: extraerInformacionNavegador()
                };

                const metricaData = {...baseData, ...data};

                logDebug('Registrando métrica:', metricaData);

                fetch('/actualizar-metrica', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(metricaData)
                })
                .then(response => response.json())
                .then(data => {
                    logDebug('Respuesta de métrica:', data);
                })
                .catch(error => {
                    console.error('Error registrando métrica:', error);
                });
            }

            // Función para registrar clics en botones
            function actualizarMetricaClic(tipo, detalle) {
                registrarMetrica({
                    tipo_visual: tipo,
                    clic_boton: true,
                    detalle: detalle
                });
            }

            // Función global para validación y autenticación de login de Mikrotik
            window.doLogin = function() {
                // Registrar clic en botón de login con información del tipo de autenticación
                const tipoAuth = '{{ $zona->tipo_autenticacion_mikrotik }}';
                actualizarMetricaClic('login', 'tipo_auth_' + tipoAuth);

                // Validar campos según el tipo de autenticación
                @if($zona->tipo_autenticacion_mikrotik == 'pin')
                    const pinInput = document.getElementById('pin-username');
                    if (!pinInput || !pinInput.value.trim()) {
                        alert('Por favor ingresa el PIN');
                        return false;
                    }

                    // Para PIN, usar autenticación directa
                    return true;

                @elseif($zona->tipo_autenticacion_mikrotik == 'usuario_password')
                    const username = document.getElementById('username');
                    const password = document.getElementById('password');
                    if (!username || !username.value.trim()) {
                        alert('Por favor ingresa el nombre de usuario');
                        return false;
                    }
                    if (!password || !password.value.trim()) {
                        alert('Por favor ingresa la contraseña');
                        return false;
                    }

                    // Si hay CHAP challenge, usar autenticación CHAP
                    const chapId = '{{ $mikrotikData["chap-id"] ?? "" }}';
                    const chapChallenge = '{{ $mikrotikData["chap-challenge"] ?? "" }}';

                    if (chapId && chapChallenge && typeof hexMD5 === 'function') {
                        // Autenticación CHAP
                        const chapPassword = hexMD5(chapId + password.value + chapChallenge);

                        // Usar formulario oculto para CHAP
                        document.sendin.username.value = username.value;
                        document.sendin.password.value = chapPassword;
                        document.sendin.submit();
                        return false; // Prevenir el envío del formulario visible
                    }

                    // Autenticación normal (sin CHAP)
                    return true;
                @endif

                return true;
            };

            // La función doTrial se ha movido a una implementación unificada más abajo en el código

            // Función para actualizar métricas (duración, clics)
            function actualizarMetrica(datos) {
                fetch('/hotspot-metrics/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        zona_id: {{ $zona->id }},
                        mac_address: '{{ $mikrotikData["mac"] ?? "" }}',
                        ...datos
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Métrica actualizada:', data.message);
                    } else {
                        console.error('Error en métrica:', data.message);
                    }
                })
                .catch(error => console.log('Error actualizando métrica:', error));
            }

            // Función para registrar clics en botones con información detallada
            function actualizarMetricaClic(tipoBoton, botonInfo = '') {
                const tiempoActual = Math.floor((Date.now() - tiempoInicio) / 1000);

                // Mapeamos el tipo de botón a valores permitidos en la base de datos
                let tipoVisual = tipoBoton;
                if (tipoBoton === 'trial' || tipoBoton === 'login') {
                    tipoVisual = 'login';
                } else if (!['formulario', 'carrusel', 'video', 'portal_cautivo', 'portal_entrada', 'login'].includes(tipoBoton)) {
                    tipoVisual = 'formulario';  // Valor por defecto para tipos no reconocidos
                }

                actualizarMetrica({
                    clic_boton: true,
                    tipo_visual: tipoVisual,
                    duracion_visual: Math.floor(tiempoActual),
                    detalle: botonInfo || tipoBoton // Conservamos el tipo original en el detalle para análisis
                });
                console.log(`Registro de clic en botón: ${tipoBoton} (guardado como ${tipoVisual}) ${botonInfo ? '(' + botonInfo + ')' : ''}`);
            }

            // Actualizar duración visual periódicamente
            setInterval(function() {
                const tiempoActual = Math.floor((Date.now() - tiempoInicio) / 1000);
                actualizarMetrica({
                    duracion_visual: Math.floor(tiempoActual)
                });

                // Actualizar contador visible en la página si existe
                const contadorEstadisticas = document.getElementById('tiempo-sesion');
                if (contadorEstadisticas) {
                    contadorEstadisticas.textContent = formatTime(tiempoActual);
                }
            }, 10000); // Cada 10 segundos

            // Función para formatear tiempo en formato mm:ss
            function formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }

            // Ocultar el botón de conexión inicialmente si hay video
            @if($videoUrl)
            const botonGratis = document.getElementById('connection-container');
            if (botonGratis) {
                botonGratis.classList.add('hidden');
                // Añadir indicador para saber que el video requiere visualización completa
                const statusMsg = document.getElementById('status-message');
                if (statusMsg) {
                    statusMsg.innerHTML = '<p>Mira el video completo para conectarte <span id="video-progress"></span></p>';
                }
            }
            @endif

            // Iniciar contador si no hay formulario y no es video
            @if(!$mostrarFormulario && !$videoUrl)
                iniciarContador();
            @endif

            // Registrar métrica de entrada
            // Intentar extraer información más precisa del dispositivo
            const extraerInformacionDispositivo = () => {
                const ua = navigator.userAgent;
                let dispositivo = 'Desconocido';

                // Extraer modelo de dispositivo móvil Android
                const regexModelo = /Android[\s\d\.]+;\s([^;)]+)/i;
                const modeloMatch = ua.match(regexModelo);

                if (modeloMatch && modeloMatch[1]) {
                    dispositivo = modeloMatch[1].trim();

                    // Detectar y formatear dispositivos Xiaomi/POCO
                    if (/M2\d{3}|22\d{6}|21\d{6}/.test(dispositivo)) {
                        // Es un código de modelo Xiaomi/POCO
                        if (ua.toLowerCase().includes('poco')) {
                            dispositivo = `POCO ${dispositivo}`;
                        } else if (ua.toLowerCase().includes('redmi')) {
                            dispositivo = `Redmi ${dispositivo}`;
                        } else {
                            dispositivo = `Xiaomi ${dispositivo}`;
                        }
                    }
                }
                // Si es iPhone/iPad
                else if (ua.includes('iPhone') || ua.includes('iPad')) {
                    dispositivo = ua.includes('iPhone') ? 'iPhone' : 'iPad';
                }
                // Si es un dispositivo Windows
                else if (ua.includes('Windows')) {
                    dispositivo = 'PC Windows';
                }
                // Si es un dispositivo Mac
                else if (ua.includes('Macintosh')) {
                    dispositivo = 'Mac';
                }

                return dispositivo;
            };

            // Extraer información del navegador
            const extraerInformacionNavegador = () => {
                const ua = navigator.userAgent;
                let navegador = 'Desconocido';
                let version = '';

                // Extraer versiones de navegadores usando expresiones regulares
                if (ua.includes('Chrome') && !ua.includes('Edg') && !ua.includes('OPR')) {
                    navegador = 'Chrome';
                    const match = ua.match(/Chrome\/(\d+(\.\d+)?)/);
                    if (match) version = match[1];
                } else if (ua.includes('Firefox')) {
                    navegador = 'Firefox';
                    const match = ua.match(/Firefox\/(\d+(\.\d+)?)/);
                    if (match) version = match[1];
                } else if (ua.includes('Safari') && !ua.includes('Chrome')) {
                    navegador = 'Safari';
                    const match = ua.match(/Version\/(\d+(\.\d+)?)/);
                    if (match) version = match[1];
                } else if (ua.includes('Edg')) {
                    navegador = 'Edge';
                    const match = ua.match(/Edg\/(\d+(\.\d+)?)/);
                    if (match) version = match[1];
                } else if (ua.includes('OPR') || ua.includes('Opera')) {
                    navegador = 'Opera';
                    const match = ua.match(/(OPR|Opera)\/(\d+(\.\d+)?)/);
                    if (match) version = match[2];
                } else if (ua.includes('MIUI')) {
                    navegador = 'Navegador MIUI';
                    const match = ua.match(/MiuiBrowser\/(\d+(\.\d+)?)/);
                    if (match) version = match[1];
                } else if (ua.includes('SamsungBrowser')) {
                    navegador = 'Samsung Internet';
                    const match = ua.match(/SamsungBrowser\/(\d+(\.\d+)?)/);
                    if (match) version = match[1];
                }

                // Si encontramos versión, la añadimos al nombre del navegador
                if (version) {
                    navegador += ' ' + version;
                }

                return navegador;
            };

            // Extraer sistema operativo
            const extraerSistemaOperativo = () => {
                const ua = navigator.userAgent;
                let so = navigator.platform || 'Desconocido';

                if (ua.includes('Android')) {
                    const match = ua.match(/Android\s([0-9\.]+)/);
                    so = 'Android ' + (match ? match[1] : '');
                } else if (ua.includes('iPhone') || ua.includes('iPad') || ua.includes('iPod')) {
                    const match = ua.match(/OS\s([0-9_]+)/);
                    so = 'iOS ' + (match ? match[1].replace(/_/g, '.') : '');
                } else if (ua.includes('Windows')) {
                    const match = ua.match(/Windows NT\s([0-9\.]+)/);
                    if (match) {
                        // Mapeo de versiones de Windows NT a nombres comerciales
                        const windowsVersions = {
                            '10.0': 'Windows 10/11',
                            '6.3': 'Windows 8.1',
                            '6.2': 'Windows 8',
                            '6.1': 'Windows 7',
                            '6.0': 'Windows Vista',
                            '5.2': 'Windows XP x64',
                            '5.1': 'Windows XP',
                            '5.0': 'Windows 2000'
                        };
                        so = windowsVersions[match[1]] || 'Windows ' + match[1];
                    } else {
                        so = 'Windows';
                    }
                } else if (ua.includes('Mac OS X') || ua.includes('Macintosh')) {
                    const match = ua.match(/Mac OS X\s?([0-9_\.]+)?/);
                    so = 'macOS ' + (match && match[1] ? match[1].replace(/_/g, '.') : '');
                } else if (ua.includes('Linux')) {
                    if (ua.includes('Ubuntu')) {
                        so = 'Ubuntu Linux';
                    } else if (ua.includes('Fedora')) {
                        so = 'Fedora Linux';
                    } else if (ua.includes('Debian')) {
                        so = 'Debian Linux';
                    } else {
                        so = 'Linux';
                    }
                }

                return so;
            };

            const metricaData = {
                zona_id: {{ $zona->id }},
                mac_address: '{{ $mikrotikData["mac"] ?? "" }}',
                tipo_visual: '{{ $videoUrl ? "video" : (count($imagenes) > 0 ? "carrusel" : "formulario") }}',
                dispositivo: extraerInformacionDispositivo(),
                navegador: extraerInformacionNavegador(),
                sistema_operativo: extraerSistemaOperativo(),
                // Guardamos el user agent completo para análisis en caso de ser necesario
                user_agent: navigator.userAgent
            };

            fetch('/hotspot-metrics/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(metricaData)
            })
            .then(response => response.json())
            .then(data => {
                // Se ha eliminado el componente de estadísticas en tiempo real
            })
            .catch(error => console.log('Error registrando métrica:', error));

            // Se eliminó el código para crear el contenedor de estadísticas

            // Función global para acceso gratuito
            window.doTrial = function() {
                // Registrar clic en botón de acceso gratuito
                actualizarMetricaClic('trial', 'boton_gratis');

                @if($videoUrl)
                // Si hay video, registrar que se hizo clic después de completarlo
                const video = document.getElementById('campaign-video');
                if (video && !video.ended) {
                    // Si el video no ha terminado, no permitir la conexión
                    actualizarMetrica({
                        tipo_visual: 'video',
                        detalle: 'intento_saltar_video',
                        duracion_visual: Math.floor(video.currentTime)
                    });

                    // Mostrar mensaje de alerta
                    alert('Por favor, mira el video completo antes de conectarte');

                    // No continuar con la conexión
                    return;
                } else {
                    // Video completado correctamente
                    actualizarMetrica({
                        tipo_visual: 'video',
                        detalle: 'conexion_post_video',
                        duracion_visual: Math.floor(video.duration)
                    });
                }
                @endif

                // Usar el formato correcto para Mikrotik trial URL
                const linkLoginOnly = '{{ $mikrotikData["link-login-only"] ?? "" }}';
                const linkOrigEsc = '{{ $mikrotikData["link-orig-esc"] ?? "" }}';
                const macEsc = '{{ $mikrotikData["mac-esc"] ?? "" }}';

                // Verificar que tenemos todos los datos necesarios para la URL de trial
                if (linkLoginOnly && linkOrigEsc && macEsc) {
                    // Crear la URL exacta según el formato de Mikrotik: $(link-login-only)?dst=$(link-orig-esc)&amp;username=T-$(mac-esc)
                    const trialUrl = linkLoginOnly + '?dst=' + linkOrigEsc + '&username=T-' + macEsc;
                    console.log("Conectando con trial:", trialUrl);

                    // Redireccionar a la URL de trial
                    window.location = trialUrl;
                } else {
                    console.error("Error: Faltan parámetros necesarios para la conexión trial", {
                        linkLoginOnly,
                        linkOrigEsc,
                        macEsc
                    });
                    alert("No se pueden obtener los datos necesarios para la conexión. Por favor, intente de nuevo.");
                }
            }
        });        /**
         * Funciones para el modal de enlace de campaña
         */
        function abrirModalEnlace(url, titulo) {
            console.log('=== INICIANDO MODAL ===');
            console.log('URL:', url);
            console.log('Título:', titulo);

            const modal = document.getElementById('modalEnlace');
            const iframe = document.getElementById('modalEnlaceIframe');
            const tituloElement = document.getElementById('modalEnlaceTitulo');

            console.log('Modal encontrado:', !!modal);
            console.log('Iframe encontrado:', !!iframe);
            console.log('Título element encontrado:', !!tituloElement);

            if (!modal || !iframe || !tituloElement) {
                console.error('Elementos del modal no encontrados');
                alert('Error: No se pudo abrir el modal');
                return;
            }

            // Establecer el título
            tituloElement.textContent = titulo || 'Información adicional';
            console.log('Título establecido:', tituloElement.textContent);

            // Limpiar iframe antes de cargar nueva URL
            iframe.src = 'about:blank';
            console.log('Iframe limpiado');

            // Mostrar modal
            modal.style.display = 'block';
            console.log('Modal mostrado');

            // Prevenir scroll del body
            document.body.style.overflow = 'hidden';
            console.log('Scroll del body deshabilitado');

            // Variable para controlar si ya se mostró el error
            let errorMostrado = false;
            let tiempoInicioMedicion = Date.now();

            // Función para verificar si el iframe está realmente cargado
            const verificarCargaIframe = () => {
                if (errorMostrado) return;

                const tiempoTranscurrido = Date.now() - tiempoInicioMedicion;
                console.log(`Verificando carga del iframe (${tiempoTranscurrido}ms transcurridos)`);

                try {
                    const iframeWindow = iframe.contentWindow;
                    const iframeDoc = iframe.contentDocument || iframeWindow.document;

                    // Si podemos acceder al documento y está vacío
                    if (iframeDoc && iframeDoc.readyState === 'complete') {
                        const body = iframeDoc.body;
                        if (!body || body.innerHTML.trim() === '' || body.children.length === 0) {
                            console.log('Iframe vacío detectado - documento accesible pero sin contenido');
                            errorMostrado = true;
                            mostrarErrorIframe(iframe, url);
                            return;
                        }

                        // Verificar si hay contenido visible
                        const bodyText = body.innerText || body.textContent || '';
                        if (bodyText.trim() === '') {
                            console.log('Iframe sin contenido de texto visible');
                            errorMostrado = true;
                            mostrarErrorIframe(iframe, url);
                            return;
                        }
                    }

                    // Si el iframe apunta a about:blank, es un error
                    if (iframe.src === 'about:blank' || iframe.src === '') {
                        console.log('Iframe regresó a about:blank - posible error de carga');
                        errorMostrado = true;
                        mostrarErrorIframe(iframe, url);
                        return;
                    }

                } catch (e) {
                    // Error de cross-origin es normal para sitios externos
                    console.log('Cross-origin detected - asumiendo carga exitosa');

                    // Si han pasado más de 3 segundos y seguimos teniendo cross-origin,
                    // probablemente el sitio se cargó correctamente
                    if (tiempoTranscurrido > 3000) {
                        console.log('Cross-origin después de 3s - asumiendo éxito');
                        return;
                    }
                }
            };

            // Event listeners para detectar carga y errores
            const onLoad = function() {
                console.log('Evento load del iframe disparado');
                console.log('URL actual del iframe:', iframe.src);
                console.log('Tiempo transcurrido:', Date.now() - tiempoInicioMedicion, 'ms');

                // Verificar inmediatamente y después de pequeños delays
                setTimeout(verificarCargaIframe, 100);
                setTimeout(verificarCargaIframe, 500);
                setTimeout(verificarCargaIframe, 1500);
            };

            const onError = function() {
                console.log('Evento error del iframe disparado');
                if (!errorMostrado) {
                    errorMostrado = true;
                    mostrarErrorIframe(iframe, url);
                }
            };

            // Función para detectar si el iframe está colgado (no responde)
            const detectarIframeColgado = () => {
                if (errorMostrado) return;

                const tiempoTranscurrido = Date.now() - tiempoInicioMedicion;
                console.log(`Verificando si iframe está colgado (${tiempoTranscurrido}ms)`);

                // Si han pasado más de 5 segundos y no hemos detectado contenido ni error
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                    // Si podemos acceder y no hay contenido, es un problema
                    if (iframeDoc && (!iframeDoc.body || iframeDoc.body.innerHTML.trim() === '')) {
                        console.log('Iframe colgado detectado - sin contenido');
                        errorMostrado = true;
                        mostrarErrorIframe(iframe, url);
                        return;
                    }

                } catch (e) {
                    // Error de cross-origin es normal para sitios externos
                    console.log('Error al detectar si iframe está colgado (Cross-origin):', e.message);
                }
            };

            // Remover listeners anteriores si existen
            iframe.removeEventListener('load', onLoad);
            iframe.removeEventListener('error', onError);

            // Agregar nuevos listeners
            iframe.addEventListener('load', onLoad);
            iframe.addEventListener('error', onError);

            // Cargar la URL después de un pequeño delay
            setTimeout(() => {
                console.log('Cargando URL en iframe:', url);
                iframe.src = url;

                // Programar múltiples verificaciones
                setTimeout(() => {
                    if (!errorMostrado) {
                        console.log('Primera verificación (2s)');
                        verificarCargaIframe();
                    }
                }, 2000);

                setTimeout(() => {
                    if (!errorMostrado) {
                        console.log('Verificando iframe colgado (4s)');
                        detectarIframeColgado();
                    }
                }, 4000);

                setTimeout(() => {
                    if (!errorMostrado) {
                        console.log('Segunda verificación completa (6s)');
                        verificarCargaIframe();
                        detectarIframeColgado();
                    }
                }, 6000);

                // Verificación final agresiva
                setTimeout(() => {
                    if (!errorMostrado) {
                        console.log('Verificación final agresiva (8s)');

                        // Si el iframe sigue con la URL original pero no hay indicios de carga,
                        // probablemente sea un problema de X-Frame-Options o CSP
                        if (iframe.src === url) {
                            try {
                                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                if (!iframeDoc || (iframeDoc.body && iframeDoc.body.innerHTML.trim() === '')) {
                                    console.log('El contenido no se pudo mostrar - Restricciones de X-Frame-Options');
                                    errorMostrado = true;
                                    mostrarErrorIframe(iframe, url);
                                }
                            } catch (e) {
                                // Para sitios con cross-origin es normal tener un error aquí
                                // Pero después de tanto tiempo, si no vemos el error normal, forzamos mensaje de error
                                console.log('Cross-origin detectado después de 8s');

                                // Para el caso específico de sattlink.com
                                if (url.includes('sattlink.com')) {
                                    console.log('URL de Sattlink detectada, mostrando mensaje de error específico');
                                    errorMostrado = true;
                                    mostrarErrorIframe(iframe, url);
                                }
                            }
                        }
                    }
                }, 8000);
            }, 100);

            // Registrar métrica de interacción
            registrarInteraccionEnlace();
        }        function cerrarModalEnlace() {
            console.log('Cerrando modal de enlace');

            const modal = document.getElementById('modalEnlace');
            const iframe = document.getElementById('modalEnlaceIframe');

            // Ocultar modal
            modal.style.display = 'none';

            // Restaurar scroll del body
            document.body.style.overflow = '';

            // Limpiar iframe para liberar recursos
            iframe.src = 'about:blank';
        }

        function mostrarErrorIframe(iframe, url) {
            console.log('Mostrando error de iframe para URL: ' + url);

            // Remover cualquier mensaje de error previo
            const errorPrevio = document.getElementById('iframe-error-message');
            if (errorPrevio) {
                errorPrevio.remove();
            }

            // Ocultar iframe
            iframe.style.display = 'none';

            // Crear mensaje de error
            const errorDiv = document.createElement('div');
            errorDiv.id = 'iframe-error-message';
            errorDiv.style.cssText = `
                padding: 2rem;
                text-align: center;
                color: #666;
                background: #f8f9fa;
                margin: 20px;
                border-radius: 8px;
                border: 1px solid #dee2e6;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            `;

            errorDiv.innerHTML = `
                <div>
                    <svg style="width: 48px; height: 48px; margin: 0 auto 1rem; display: block; color: #ffc107;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 style="margin-bottom: 0.5rem; color: #495057; font-size: 18px;">Contenido no disponible</h3>
                    <p style="margin-bottom: 1.5rem; color: #6c757d; font-size: 14px; line-height: 1.5;">
                        El sitio web no se puede mostrar aquí.<br>
                        Esto puede deberse a restricciones de seguridad del sitio.
                    </p>
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button onclick="abrirEnNuevaVentana('${url}')" style="padding: 0.75rem 1.5rem; background: #ff5e2c; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            📱 Abrir en nueva ventana
                        </button>
                        <button onclick="intentarRecargar('${url}')" style="padding: 0.75rem 1.5rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                            🔄 Intentar de nuevo
                        </button>
                    </div>
                </div>
            `;

            // Agregar al contenedor del modal
            const modalBody = iframe.parentNode;
            modalBody.appendChild(errorDiv);
        }

        function abrirEnNuevaVentana(url) {
            console.log('Abriendo en nueva ventana: ' + url);
            window.open(url, '_blank', 'noopener,noreferrer');
        }

        function intentarRecargar(url) {
            console.log('Intentando recargar: ' + url);

            // Remover mensaje de error
            const errorMsg = document.getElementById('iframe-error-message');
            if (errorMsg) {
                errorMsg.remove();
            }

            // Obtener iframe y resetear
            const iframe = document.getElementById('modalEnlaceIframe');
            iframe.style.display = 'block';
            iframe.src = 'about:blank';

            // Pequeño delay antes de recargar
            setTimeout(() => {
                console.log('Recargando URL:', url);

                // Variable para controlar errores en el intento de recarga
                let errorMostradoRecarga = false;

                const onLoadRecarga = function() {
                    console.log('Recarga - evento load disparado');
                    setTimeout(() => {
                        if (!errorMostradoRecarga) {
                            try {
                                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                if (iframeDoc && iframeDoc.body && iframeDoc.body.innerHTML.trim() === '') {
                                    console.log('Recarga - iframe vacío detectado');
                                    errorMostradoRecarga = true;
                                    mostrarErrorIframe(iframe, url);
                                }
                            } catch (e) {
                                console.log('Recarga - cross-origin detected (probablemente exitoso)');
                            }
                        }
                    }, 1000);
                };

                const onErrorRecarga = function() {
                    console.log('Recarga - evento error disparado');
                    if (!errorMostradoRecarga) {
                        errorMostradoRecarga = true;
                        mostrarErrorIframe(iframe, url);
                    }
                };

                // Limpiar listeners anteriores
                iframe.removeEventListener('load', onLoadRecarga);
                iframe.removeEventListener('error', onErrorRecarga);

                // Agregar listeners para la recarga
                iframe.addEventListener('load', onLoadRecarga);
                iframe.addEventListener('error', onErrorRecarga);

                // Cargar URL
                iframe.src = url;

                // Timeout para la recarga
                setTimeout(() => {
                    if (!errorMostradoRecarga) {
                        try {
                            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                            if (!iframeDoc || (iframeDoc.body && iframeDoc.body.innerHTML.trim() === '')) {
                                console.log('Recarga - timeout, mostrando error');
                                errorMostradoRecarga = true;
                                mostrarErrorIframe(iframe, url);
                            }
                        } catch (e) {
                            console.log('Recarga - timeout con cross-origin (probablemente exitoso)');
                        }
                    }
                }, 5000);

            }, 200);
        }        function registrarInteraccionEnlace() {
            // Registrar que el usuario interactuó con el enlace de la campaña
            const zonaId = document.getElementById('zona_id')?.value;
            const macAddress = document.getElementById('mac_address')?.value;

            if (zonaId && macAddress) {
                console.log('Registrando interacción con enlace de campaña');

                // Obtener el token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                if (!csrfToken) {
                    console.log('Token CSRF no encontrado, omitiendo registro de métrica');
                    return;
                }

                fetch('/portal/actualizar-metrica', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        zona_id: parseInt(zonaId),
                        mac_address: macAddress,
                        clic_boton: true,
                        tipo_visual: 'enlace_campana',
                        detalle: 'Usuario abrió enlace de campaña'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Métrica de enlace registrada correctamente');
                })
                .catch(error => {
                    console.log('Error al registrar métrica de enlace: ' + error.message);
                });
            }
        }

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('modalEnlace');
                if (modal && modal.style.display === 'block') {
                    cerrarModalEnlace();
                }
            }
        });

        // Manejar errores del iframe
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.getElementById('modalEnlaceIframe');
            if (iframe) {
                iframe.addEventListener('error', function() {
                    console.log('Error al cargar contenido en iframe');
                    mostrarErrorIframe(iframe, iframe.src);
                });

                iframe.addEventListener('load', function() {
                    console.log('Iframe cargado correctamente');
                    // Verificar si realmente se cargó contenido
                    try {
                        // Si podemos acceder al documento del iframe, significa que se cargó
                        if (iframe.contentDocument || iframe.contentWindow) {
                            console.log('Contenido del iframe accesible');
                        }
                    } catch (e) {
                        // Si hay error de cross-origin, es normal para sitios externos
                        console.log('Iframe cargado (cross-origin)');
                    }
                });
            }
        });
    </script>

    <!-- Scripts de configuración adicionales -->
    {!! $zona->script_body ?? '' !!}
</body>
</html>
