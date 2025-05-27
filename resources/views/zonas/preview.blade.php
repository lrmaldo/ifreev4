<!DOCTYPE html>
<html>
<head>
    <title>Vista previa de portal cautivo - {{ $zona->nombre }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
        }
        .preview-container {
            max-width: 500px;
            margin: 20px auto;
            border: 2px dashed #6b7280;
            border-radius: 8px;
        }
        .preview-notice {
            background-color: #374151;
            color: white;
            text-align: center;
            padding: 6px;
            font-size: 14px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        .preview-content {
            padding: 20px;
            background-color: white;
            border-bottom-left-radius: 6px;
            border-bottom-right-radius: 6px;
        }
        /* Script personalizado para head */
        {!! $zona->script_head !!}
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Vista previa: {{ $zona->nombre }}</h1>
            <a href="{{ route('cliente.zonas.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Volver a zonas
            </a>
        </div>

        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
            <p class="font-bold">Modo vista previa</p>
            <p>Esta es una simulación de cómo se verá su portal cautivo. Los datos de Mikrotik son ficticios y no representa exactamente cómo se verá en un dispositivo real.</p>
        </div>

        <div class="preview-container">
            <div class="preview-notice">Vista previa del portal cautivo</div>
            <div class="preview-content">
                <div class="mb-4 text-center">
                    <h2 class="text-xl font-bold">{{ $zona->nombre }}</h2>
                    <p class="text-gray-600">Conectarse a WiFi</p>
                </div>

                @if($zona->tipo_registro == 'sin_registro')
                    <div class="text-center p-4 bg-green-50 rounded border border-green-200 mb-4">
                        <p>Esta zona está configurada para acceso sin registro.</p>
                        <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Conectar ahora
                        </button>
                    </div>
                @else
                    <form action="#" method="post" class="space-y-4">
                        <!-- Campos dinámicos del formulario -->
                        @foreach($camposHtml as $campoHtml)
                            {!! $campoHtml !!}
                        @endforeach

                        @if($zona->login_sin_registro)
                            <div class="mt-6 flex items-center justify-between">
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Registrarse y conectar
                                </button>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="#" class="text-sm text-gray-600 hover:text-gray-800">No quiero registrarme, solo conectar</a>
                            </div>
                        @else
                            <div class="mt-6">
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Registrarse y conectar
                                </button>
                            </div>
                        @endif
                    </form>
                @endif

                <div class="mt-6 text-xs text-gray-500 text-center">
                    <p>Al conectarte, aceptas nuestras políticas de uso y privacidad</p>
                </div>
            </div>
        </div>

        <div class="mt-8 bg-white p-6 rounded shadow">
            <h3 class="text-lg font-semibold mb-4">Datos simulados de Mikrotik</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($mikrotikData as $key => $value)
                    <div class="border-b pb-2">
                        <span class="font-mono text-sm text-gray-600">{{ $key }}:</span>
                        <span class="ml-2 font-mono text-sm">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Script personalizado para body -->
    {!! $zona->script_body !!}
</body>
</html>
