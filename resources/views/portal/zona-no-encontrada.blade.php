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

        .zonas-disponibles {
            margin-top: 24px;
            padding: 16px;
            background: #f0f9ff;
            border-radius: 8px;
            border-left: 4px solid #0ea5e9;
        }

        .zonas-titulo {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 12px;
        }

        .zonas-lista {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .zona-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: white;
            color: #0ea5e9;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #e0f2fe;
        }

        .zona-link:hover {
            background: #0ea5e9;
            color: white;
            transform: translateX(4px);
        }

        .admin-info {
            margin-top: 24px;
            padding: 16px;
            background: #fef3c7;
            border-radius: 8px;
            border-left: 4px solid #d97706;
        }

        .admin-title {
            font-size: 14px;
            font-weight: 600;
            color: #d97706;
            margin-bottom: 8px;
        }

        .admin-details {
            font-size: 13px;
            color: #92400e;
        }

        .admin-details code {
            background: #fbbf24;
            color: #451a03;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: bold;
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
            @if(isset($tipo_error) && $tipo_error === 'inactiva')
            <div class="error-icon" style="background: linear-gradient(135deg, #fef3c7, #fcd34d); color: #d97706;">
                🔒
            </div>
            @else
            <div class="error-icon">
                ⚠️
            </div>
            @endif

            @if(isset($tipo_error) && $tipo_error === 'inactiva')
            <h2 class="error-message">Zona temporalmente desactivada</h2>
            @else
            <h2 class="error-message">Zona no disponible</h2>
            @endif

            @if(isset($zona_nombre))
            <p class="error-details">
                La zona <strong>{{ $zona_nombre }}</strong> está temporalmente desactivada.
                <br>{{ $mensaje ?? 'Contacte al administrador para activarla.' }}
            </p>
            @else
            <p class="error-details">
                {{ $mensaje ?? 'La zona solicitada no existe o no está disponible en este momento.' }}
            </p>
            @endif

            @if(isset($zona_id))
            <div class="error-code">
                @if(isset($zona_nombre))
                    Zona: {{ $zona_nombre }} (ID: {{ $zona_id }})
                @else
                    ID de Zona: {{ $zona_id }}
                @endif
            </div>
            @endif

            @if(isset($tipo_error) && $tipo_error === 'inactiva')
            <div class="admin-info">
                <h3 class="admin-title">Para el administrador:</h3>
                <p class="admin-details">
                    Para activar esta zona, ejecute:<br>
                    <code>php artisan zona:activar {{ $zona_id }}</code>
                </p>
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

            @if(isset($zonas_disponibles) && !empty($zonas_disponibles))
            <div class="zonas-disponibles">
                <h3 class="zonas-titulo">Zonas disponibles:</h3>
                <div class="zonas-lista">
                    @foreach($zonas_disponibles as $zona_id => $zona_nombre)
                    <a href="{{ route('zona.login.mikrotik', ['id' => $zona_id]) }}" class="zona-link">
                        📍 {{ $zona_nombre }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif            <div class="contact-info">
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
