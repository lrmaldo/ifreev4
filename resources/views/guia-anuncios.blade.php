<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Guía de Especificaciones de Publicidad - {{ config('app.name', 'Laravel') }}</title>

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50 dark:bg-zinc-900">
    <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-12 bg-gray-50 dark:bg-zinc-900">
        <!-- Logo Header -->
        <div class="flex flex-col items-center w-full">
            <svg class="w-16 h-16 text-blue-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 text-center">Guía de Especificaciones</h1>
            <p class="text-gray-500 dark:text-gray-400 text-center px-4 max-w-2xl">
                Especificaciones técnicas para los recursos visuales e imágenes utilizados en el Portal Cautivo.
            </p>
        </div>

        <div class="w-full sm:max-w-4xl mt-8 px-6 py-8 bg-white dark:bg-zinc-800 shadow-md sm:rounded-xl border border-gray-100 dark:border-zinc-700">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- Sección Imágenes -->
                <div class="flex flex-col h-full bg-blue-50/50 dark:bg-zinc-700/30 p-6 rounded-lg border border-blue-100 dark:border-zinc-600">
                    <div class="flex items-center mb-6 border-b border-blue-200 dark:border-zinc-600 pb-3">
                        <svg class="w-8 h-8 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Imágenes (Carrusel)</h2>
                    </div>
                    
                    <ul class="space-y-4 text-gray-700 dark:text-gray-300 flex-grow">
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Formatos:</span>
                            <span>JPG, PNG y WEBP.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Peso máximo:</span>
                            <span>2 MB a 5 MB (se sugiere <strong>1MB - 2MB</strong> para cargar rápido).</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Dimensiones 📱 (Vertical):</span>
                            <span><strong>1080 x 1920 px</strong><br><span class="text-sm text-gray-500">(Relación 9:16) - <em>Optimizado para móviles.</em></span></span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Dimensiones 💻 (Horizontal):</span>
                            <span><strong>1920 x 1080 px</strong><br><span class="text-sm text-gray-500">(Relación 16:9).</span></span>
                        </li>
                    </ul>

                    <div class="mt-6 bg-white dark:bg-zinc-800 p-3 rounded border border-gray-200 dark:border-zinc-600 flex justify-center">
                        <div class="text-center">
                            <div class="w-16 h-28 border-2 border-dashed border-blue-400 dark:border-blue-500 rounded mx-auto flex items-center justify-center text-xs text-blue-600 dark:text-blue-400 font-bold mb-2">9:16</div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">1080 x 1920p</span>
                        </div>
                    </div>
                </div>

                <!-- Sección Videos -->
                <div class="flex flex-col h-full bg-red-50/50 dark:bg-zinc-700/30 p-6 rounded-lg border border-red-100 dark:border-zinc-600">
                    <div class="flex items-center mb-6 border-b border-red-200 dark:border-zinc-600 pb-3">
                        <svg class="w-8 h-8 mr-3 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Videos</h2>
                    </div>

                    <ul class="space-y-4 text-gray-700 dark:text-gray-300 flex-grow">
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Formatos:</span>
                            <span><strong>MP4</strong> o <strong>WEBM</strong> (AVI o MOV fallan en algunos smartphones).</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Duración:</span>
                            <span>Máximo <strong>15 segundos</strong> (Recomendado 5-10s para mayor retención).</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Peso máximo:</span>
                            <span><strong>15 MB</strong> a <strong>20 MB</strong>.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Dimensiones📱:</span>
                            <span><strong>1080 x 1920 px</strong> o 720x1280 px (Formato Vertical 9:16).</span>
                        </li>
                        <li class="flex items-start">
                            <span class="font-bold min-w-[120px]">Audio 🔈:</span>
                            <span>Silenciado por obligación (Políticas Autoplay Android/iOS).</span>
                        </li>
                    </ul>
                    
                    <div class="mt-6 bg-white dark:bg-zinc-800 p-3 rounded border border-gray-200 dark:border-zinc-600 flex justify-center">
                        <div class="text-center">
                            <div class="relative w-16 h-28 border-2 border-dashed border-red-400 dark:border-red-500 bg-red-50 dark:bg-zinc-700 rounded mx-auto flex items-center justify-center text-xs text-red-600 dark:text-red-400 font-bold mb-2">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                <span class="absolute bottom-1 right-1 text-[10px] bg-red-100 dark:bg-red-900 px-1 rounded text-red-700 dark:text-red-300">15s</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">1080 x 1920p</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Botones de Acción / Descarga PDF -->
            <div class="mt-8 flex justify-center border-t border-gray-200 dark:border-zinc-700 pt-6">
                <!-- Solo imprime la guia -->
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-zinc-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir o Guardar Como PDF
                </button>
            </div>

        </div>
        
        <div class="mt-8 text-center text-sm text-gray-500 pb-10">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>