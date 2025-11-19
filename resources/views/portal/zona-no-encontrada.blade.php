<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zona no encontrada - i-Free Portal</title>
    <style>
        :root {
            --color-primary: #ff5e2c;
            --color-secondary: #ff8159;
            --color-text: #1f2937;
            --color-text-light: #6b7280;
            --color-background: #f9fafb;
            --color-border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, var(--color-background) 0%, #ffffff 100%);
            color: var(--color-text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            border: 1px solid var(--color-border);
        }

        .error-header {
            background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
            color: white;
            text-align: center;
            padding: 20px;
        }

        .error-logo {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }

        .error-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .error-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .error-content {
            padding: 40px 32px;
            text-align: center;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-radius: 50%;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc2626;
            font-size: 32px;
        }

        .error-message {
            font-size: 18px;
            font-weight: 500;
            color: var(--color-text);
            margin-bottom: 16px;
        }

        .error-details {
            font-size: 14px;
            color: var(--color-text-light);
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .error-code {
            background: #f3f4f6;
            color: #374151;
            padding: 8px 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 24px;
        }

        .error-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            text-align: center;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px -8px var(--color-primary);
        }

        .btn-secondary {
            background: #f9fafb;
            color: var(--color-text);
            border: 1px solid var(--color-border);
        }

        .btn-secondary:hover {
            background: #f3f4f6;
        }

        .contact-info {
            margin-top: 24px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid var(--color-primary);
        }

        .contact-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 8px;
        }

        .contact-details {
            font-size: 13px;
            color: var(--color-text-light);
        }

        @media (max-width: 480px) {
            .error-container {
                margin: 10px;
                border-radius: 12px;
            }

            .error-content {
                padding: 32px 24px;
            }

            .error-header {
                padding: 16px;
            }

            .error-title {
                font-size: 18px;
            }

            .error-message {
                font-size: 16px;
            }
        }

        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-container {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Efectos hover adicionales */
        .error-icon {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-logo">
                iF
            </div>
            <h1 class="error-title">i-Free Portal WiFi</h1>
            <p class="error-subtitle">Sistema de Portal Cautivo</p>
        </div>

        <div class="error-content">
            <div class="error-icon">
                ⚠️
            </div>

            <h2 class="error-message">Zona no disponible</h2>

            <p class="error-details">
                {{ $mensaje ?? 'La zona solicitada no existe o no está disponible en este momento.' }}
            </p>

            @if(isset($zona_id))
            <div class="error-code">
                ID de Zona: {{ $zona_id }}
            </div>
            @endif

            <div class="error-actions">
                <a href="javascript:history.back()" class="btn btn-primary">
                    ← Volver atrás
                </a>

                <a href="javascript:location.reload()" class="btn btn-secondary">
                    🔄 Intentar nuevamente
                </a>
            </div>

            <div class="contact-info">
                <h3 class="contact-title">¿Necesitas ayuda?</h3>
                <p class="contact-details">
                    Si continúas teniendo problemas para acceder al WiFi, contacta al administrador del establecimiento o al soporte técnico de i-Free.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Log del error para análisis
        console.log('Error de zona:', {
            zona_id: '{{ $zona_id ?? "N/A" }}',
            mensaje: '{{ $mensaje ?? "Sin mensaje" }}',
            timestamp: new Date().toISOString(),
            url: window.location.href
        });

        // Auto-refresh cada 30 segundos por si se soluciona el problema
        setTimeout(function() {
            if (confirm('¿Deseas intentar acceder nuevamente al portal WiFi?')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
