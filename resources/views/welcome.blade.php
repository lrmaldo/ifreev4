<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Ifree Hotspot - Monetiza el acceso a Internet con publicidad</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles -->
        <!-- TailwindCSS: asegúrate que resources/css/app.css tenga las directivas @tailwind base; @tailwind components; @tailwind utilities; -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- Fin TailwindCSS -->
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] min-h-screen">
        <!-- Navbar -->
        <header class="fixed w-full top-0 z-50 bg-[#FDFDFC]/95 dark:bg-[#0a0a0a]/95 backdrop-blur-sm shadow-sm">
            <div class="container mx-auto px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-10 w-10 text-[#ff3f00]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 16h-2v-6h2v6zm4 0h-2v-6h2v6zm1-9.5C16 9.33 15.33 10 14.5 10h-5C8.67 10 8 9.33 8 8.5V8c0-.83.67-1.5 1.5-1.5h5c.83 0 1.5.67 1.5 1.5v.5z"/>
                        </svg>
                        <span class="ml-3 text-2xl font-bold text-[#ff3f00]">Ifree Hotspot</span>
                    </div>
                    @if (Route::has('login'))
                        <nav class="flex items-center gap-4">
                            @auth
                                <a
                                    href="{{ url('/dashboard') }}"
                                    class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                                >
                                    Dashboard
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                                >
                                    Log in
                                </a>

                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="inline-block px-5 py-1.5 bg-[#ff3f00] hover:bg-[#ff5c26] text-white rounded-sm text-sm leading-normal"
                                    >
                                        Register
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </div>
            </div>
        </header>

        <main class="pt-16">
            <!-- Hero principal con imagen a un lado y texto al otro -->
            <section class="py-16 lg:py-24 container mx-auto px-4">
                <div class="grid grid-cols-1 lg:grid-cols-2 items-center gap-12">
                    <!-- Contenido de texto -->
                    <div class="order-2 lg:order-1 animate-fade-in">
                        <span class="bg-[#ff3f00]/10 text-[#ff3f00] px-4 py-1 rounded-full font-semibold mb-6 inline-block">Monetiza tus espacios</span>
                        <h1 class="text-4xl lg:text-6xl font-extrabold mb-6 text-[#1b1b18] dark:text-white animate-slide-down">
                            Internet gratuito <span class="text-[#ff3f00]">que genera ingresos</span>
                        </h1>
                        <p class="text-lg mb-8 text-gray-700 dark:text-gray-300 animate-fade-in-delay">
                            Ifree Hotspot permite ofrecer acceso a internet en espacios públicos mediante publicidad.
                            Tus usuarios obtienen conexión gratis y tú obtienes ingresos publicitarios.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 animate-fade-in-delay2">
                            <a href="{{ route('register') }}" class="bg-[#ff3f00] hover:bg-[#ff5c26] text-white font-semibold px-8 py-3 rounded-lg shadow-lg transition-all duration-300">Comenzar prueba</a>
                            <a href="#como-funciona" class="border-2 border-[#1b1b18] dark:border-white text-[#1b1b18] dark:text-white hover:bg-[#1b1b18]/5 dark:hover:bg-white/5 font-semibold px-8 py-3 rounded-lg transition-all duration-300 flex items-center justify-center">
                                <span>Cómo funciona</span>
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Métricas -->
                        <div class="grid grid-cols-3 gap-4 mt-12 border-t pt-6 border-gray-200 dark:border-gray-800 animate-fade-in-delay3">
                            <div>
                                <p class="text-3xl font-bold text-[#ff3f00]">500+</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Hotspots activos</p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-[#ff3f00]">10k+</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Usuarios diarios</p>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-[#ff3f00]">98%</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Satisfacción</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mockup de dispositivos -->
                    <div class="order-1 lg:order-2 relative animate-float">
                        <!-- Laptop mockup -->
                        <div class="relative mx-auto w-full max-w-md">
                            <div class="relative rounded-t-xl bg-[#1b1b18] dark:bg-gray-900 w-full aspect-[16/10] shadow-xl overflow-hidden">
                                <div class="absolute inset-0 p-2">
                                    <!-- Panel de administración -->
                                    <div class="w-full h-full rounded-lg bg-white dark:bg-gray-800 overflow-hidden">
                                        <div class="h-8 bg-gray-100 dark:bg-gray-900 flex items-center px-3">
                                            <div class="flex space-x-1.5">
                                                <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                                                <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                                                <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                                            </div>
                                        </div>
                                        <div class="p-2 flex h-[calc(100%-2rem)]">
                                            <!-- Sidebar -->
                                            <div class="w-1/4 bg-gray-50 dark:bg-gray-900 rounded-md p-2">
                                                <div class="flex items-center gap-2 p-1 mb-4">
                                                    <div class="w-6 h-6 bg-[#ff3f00] rounded-md"></div>
                                                    <div class="text-xs font-bold">Dashboard</div>
                                                </div>
                                                <div class="space-y-2">
                                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-4/5"></div>
                                                </div>
                                            </div>
                                            <!-- Main content -->
                                            <div class="w-3/4 pl-2">
                                                <div class="h-32 bg-[#ff3f00]/10 rounded-md mb-2 flex items-center justify-center">
                                                    <div class="w-24 h-16 bg-[#ff3f00] rounded-md"></div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 mb-2">
                                                    <div class="h-16 bg-gray-100 dark:bg-gray-700 rounded-md"></div>
                                                    <div class="h-16 bg-gray-100 dark:bg-gray-700 rounded-md"></div>
                                                </div>
                                                <div class="h-20 bg-gray-100 dark:bg-gray-700 rounded-md"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="h-3 bg-[#1b1b18] dark:bg-gray-900 rounded-b-xl"></div>
                            <div class="h-1 bg-[#1b1b18]/80 dark:bg-gray-800 rounded-b-xl w-4/5 mx-auto"></div>
                        </div>

                        <!-- Phone mockup - hotspot login -->
                        <div class="absolute -bottom-10 -right-4 md:right-0 w-48 h-96 bg-[#1b1b18] dark:bg-gray-900 rounded-xl shadow-lg border-4 border-[#1b1b18] dark:border-gray-900 overflow-hidden animate-float-delayed">
                            <div class="absolute top-0 w-24 h-6 bg-[#1b1b18] dark:bg-gray-900 left-1/2 transform -translate-x-1/2 rounded-b-xl"></div>
                            <div class="w-full h-full bg-white dark:bg-gray-800 p-1">
                                <!-- Pantalla de conexión WiFi -->
                                <div class="h-full rounded bg-gray-100 dark:bg-gray-700 flex flex-col">
                                    <!-- Banner de publicidad -->
                                    <div class="bg-[#ff3f00] h-24 flex items-center justify-center text-white p-1">
                                        <div class="text-center text-xs font-bold">PUBLICIDAD</div>
                                    </div>
                                    <!-- Contenido -->
                                    <div class="flex-1 flex flex-col items-center justify-center p-2 space-y-3">
                                        <div class="w-12 h-12 rounded-full bg-[#ff3f00]/20 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-[#ff3f00]" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
                                            </svg>
                                        </div>
                                        <div class="space-y-2 w-full">
                                            <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                                            <div class="h-6 bg-[#ff3f00] rounded-lg w-3/4 mx-auto"></div>
                                        </div>
                                        <div class="text-[7px] text-center text-gray-500">Ver anuncio para continuar</div>
                                        <div class="h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full w-3/4 mx-auto overflow-hidden">
                                            <div class="h-full bg-[#ff3f00] w-2/3"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cómo funciona - Pasos ilustrados -->
            <section id="como-funciona" class="py-16 bg-gray-50 dark:bg-gray-900">
                <div class="container mx-auto px-4">
                    <div class="text-center mb-12 animate-fade-in-delay4">
                        <span class="text-[#ff3f00] font-semibold">Proceso simple</span>
                        <h2 class="text-3xl md:text-4xl font-bold">¿Cómo funciona Ifree Hotspot?</h2>
                        <p class="mt-4 text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                            Un sistema fácil para ofrecer internet y monetizar tu espacio en 3 simples pasos
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-fade-in-delay5">
                        <!-- Paso 1 -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
                            <div class="w-12 h-12 bg-[#ff3f00]/20 rounded-full flex items-center justify-center text-[#ff3f00] mb-4">1</div>
                            <h3 class="font-bold text-xl mb-2">Configura tu Hotspot</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">Conecta un router Mikrotik y configúralo en minutos con nuestra interfaz intuitiva.</p>
                            <div class="h-40 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden">
                                <!-- Ilustración simplificada de router -->
                                <svg class="w-24 h-24 text-[#ff3f00]/70" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 6h-8l-2-2H4C2.9 4 2 4.9 2 6v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V6h5.17l2 2H20v10zm-6-3c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm0-5c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Paso 2 -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
                            <div class="w-12 h-12 bg-[#ff3f00]/20 rounded-full flex items-center justify-center text-[#ff3f00] mb-4">2</div>
                            <h3 class="font-bold text-xl mb-2">Sube tu publicidad</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">Agrega imágenes, videos o formularios que tus usuarios verán antes de acceder a internet.</p>
                            <div class="h-40 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden">
                                <!-- Ilustración de subida de contenido -->
                                <svg class="w-24 h-24 text-[#ff3f00]/70" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Paso 3 -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition-all">
                            <div class="w-12 h-12 bg-[#ff3f00]/20 rounded-full flex items-center justify-center text-[#ff3f00] mb-4">3</div>
                            <h3 class="font-bold text-xl mb-2">Genera ingresos</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">Comienza a recibir ingresos por la visualización de publicidad mientras ofreces internet gratuito.</p>
                            <div class="h-40 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden">
                                <!-- Ilustración de ganancias -->
                                <svg class="w-24 h-24 text-[#ff3f00]/70" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ejemplo de pantallas -->
            <section class="py-16 container mx-auto px-4">
                <div class="text-center mb-12">
                    <span class="text-[#ff3f00] font-semibold">Visualiza el potencial</span>
                    <h2 class="text-3xl md:text-4xl font-bold">Pantallas de ejemplo</h2>
                    <p class="mt-4 text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                        Así es como tus usuarios interactuarán con el servicio
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Pantalla de conexión -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="h-6 bg-gray-100 dark:bg-gray-700 flex items-center px-4">
                            <div class="w-2 h-2 rounded-full bg-red-500 mr-1"></div>
                            <div class="w-2 h-2 rounded-full bg-yellow-500 mr-1"></div>
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        </div>
                        <div class="p-4 border-b">
                            <div class="flex justify-center mb-4">
                                <svg class="w-16 h-16 text-[#ff3f00]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-center mb-2">¡Bienvenido a WiFi Gratis!</h3>
                            <p class="text-center text-gray-600 dark:text-gray-400 text-sm">
                                Conecta a internet viendo un breve anuncio
                            </p>
                        </div>
                        <div class="p-4">
                            <div class="bg-[#ff3f00]/10 rounded-lg p-4 mb-4">
                                <div class="h-24 bg-[#ff3f00] rounded flex items-center justify-center text-white font-bold">
                                    ESPACIO PUBLICITARIO
                                </div>
                            </div>
                            <button class="w-full bg-[#ff3f00] hover:bg-[#ff5c26] text-white py-2 rounded-lg font-bold">
                                Ver anuncio para conectar
                            </button>
                        </div>
                    </div>

                    <!-- Pantalla de cuenta regresiva -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="h-6 bg-gray-100 dark:bg-gray-700 flex items-center px-4">
                            <div class="w-2 h-2 rounded-full bg-red-500 mr-1"></div>
                            <div class="w-2 h-2 rounded-full bg-yellow-500 mr-1"></div>
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-center mb-4">
                                <div class="w-24 h-24 rounded-full border-4 border-[#ff3f00] flex items-center justify-center">
                                    <span class="text-3xl font-bold text-[#ff3f00]">0:30</span>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-center mb-2">¡Anuncio completado!</h3>
                            <p class="text-center text-gray-600 dark:text-gray-400 text-sm mb-4">
                                Serás conectado automáticamente en 30 segundos
                            </p>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-[#ff3f00] w-3/4"></div>
                            </div>
                            <p class="text-center text-xs mt-2 text-gray-500">Tiempo restante: 30 segundos</p>
                        </div>
                        <div class="p-4 bg-gray-100 dark:bg-gray-700">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-300">Tiempo de acceso:</span>
                                <span class="font-bold">10 minutos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de administrador -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg">
                        <div class="h-6 bg-gray-100 dark:bg-gray-700 flex items-center px-4">
                            <div class="w-2 h-2 rounded-full bg-red-500 mr-1"></div>
                            <div class="w-2 h-2 rounded-full bg-yellow-500 mr-1"></div>
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        </div>
                        <div class="p-4 border-b">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-[#ff3f00] rounded-full"></div>
                                <div>
                                    <h3 class="font-bold text-sm">Panel de administración</h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Administrador</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Usuarios activos</p>
                                    <p class="text-xl font-bold">247</p>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Impresiones</p>
                                    <p class="text-xl font-bold">1,893</p>
                                </div>
                            </div>
                            <div class="h-28 bg-gray-100 dark:bg-gray-700 rounded-lg mb-4 p-2">
                                <div class="flex h-full items-end justify-between px-2">
                                    <div class="w-[10%] h-[30%] bg-[#ff3f00]"></div>
                                    <div class="w-[10%] h-[45%] bg-[#ff3f00]"></div>
                                    <div class="w-[10%] h-[60%] bg-[#ff3f00]"></div>
                                    <div class="w-[10%] h-[40%] bg-[#ff3f00]"></div>
                                    <div class="w-[10%] h-[75%] bg-[#ff3f00]"></div>
                                    <div class="w-[10%] h-[65%] bg-[#ff3f00]"></div>
                                    <div class="w-[10%] h-[85%] bg-[#ff3f00]"></div>
                                </div>
                            </div>
                            <div class="bg-[#ff3f00]/10 p-2 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-bold">Ingresos del mes</p>
                                    <p class="text-[#ff3f00] font-bold">$1,245</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="py-16 bg-[#ff3f00]">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">¿Listo para monetizar tu hotspot?</h2>
                    <p class="text-white/90 max-w-2xl mx-auto mb-8">
                        Únete a cientos de negocios que ya están generando ingresos extra ofreciendo internet gratuito a sus clientes
                    </p>
                    <a href="{{ route('register') }}" class="inline-block px-8 py-3 bg-white text-[#ff3f00] font-bold rounded-lg shadow-lg hover:bg-gray-100 transition-colors duration-300">
                        Comenzar ahora
                    </a>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-100 dark:bg-gray-900 py-8">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center mb-4 md:mb-0">
                        <svg class="h-8 w-8 text-[#ff3f00]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 16h-2v-6h2v6zm4 0h-2v-6h2v6zm1-9.5C16 9.33 15.33 10 14.5 10h-5C8.67 10 8 9.33 8 8.5V8c0-.83.67-1.5 1.5-1.5h5c.83 0 1.5.67 1.5 1.5v.5z"/>
                        </svg>
                        <span class="ml-2 font-bold text-gray-800 dark:text-white">Ifree Hotspot</span>
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        &copy; {{ date('Y') }} Ifree Hotspot. Todos los derechos reservados.
                    </div>
                </div>
            </div>
        </footer>

        <!-- Botón flotante de WhatsApp mejorado -->
        <div class="fixed bottom-6 right-6 z-50 group">
            <!-- Pulso animado -->
            <div class="absolute -inset-4 bg-[#25D366] rounded-full opacity-30 animate-pulse-slow group-hover:opacity-40"></div>

            <!-- Contenedor principal con texto -->
            <a href="https://wa.me/5491123456789" target="_blank"
               class="flex items-center gap-2 bg-[#25D366] hover:bg-[#20c65e] text-white rounded-full shadow-lg pl-2 pr-5 py-2.5 transition-all duration-300 group-hover:shadow-xl">
                <!-- Logotipo oficial de WhatsApp -->
                <div class="relative flex items-center justify-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-white rounded-full shadow-inner">
                        <svg class="w-5 h-5" viewBox="0 0 90 90" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd">
                            <path d="M44.8 89.5c23.1 0 41.8-18.7 41.8-41.8S67.9 5.9 44.8 5.9 3.1 24.6 3.1 47.7s18.7 41.8 41.8 41.8zm.1-84.1c23.3 0 42.3 19 42.3 42.3S68.2 90 44.9 90 2.6 71 2.6 47.7 21.6 5.4 44.9 5.4z" fill="#25D366"/>
                            <path d="M61.4 53.5c-.7-.3-4.1-2-4.8-2.2-.7-.2-1.1-.3-1.6.3-.5.6-1.8 2.2-2.2 2.7-.4.5-.8.5-1.5.2-.7-.3-2.9-1.1-5.5-3.4-2-1.8-3.4-4-3.8-4.7-.4-.7 0-1 .3-1.3.3-.3.7-.8 1-1.2.3-.4.4-.7.6-1.1.2-.4.1-.8 0-1.2-.1-.3-1.6-3.8-2.1-5.2-.6-1.4-1.1-1.2-1.6-1.2-.4 0-.8 0-1.3 0s-1.2.2-1.8.9c-.6.7-2.3 2.2-2.3 5.5 0 3.3 2.4 6.5 2.7 6.9.3.4 4.9 7.5 11.9 10.5 1.7.7 3 1.1 4 1.5 1.7.5 3.2.4 4.4.3 1.3-.2 4.1-1.7 4.7-3.3.6-1.6.6-3 .4-3.3-.2-.3-.6-.5-1.3-.8zm-12.4 17c-3.8 0-7.4-1-10.6-3l-.8-.5-7.8 2 2.1-7.5-.5-.8c-2.1-3.3-3.2-7.2-3.2-11.2 0-11.6 9.4-21 21-21s21 9.4 21 21c0 11.6-9.5 21-21 21zm0-38.2c-9.5 0-17.2 7.7-17.2 17.2 0 3.7 1.2 7.3 3.4 10.2l.5.8-2.2 8 8.2-2.1.8.5c2.9 1.7 6.2 2.7 9.5 2.7 9.5 0 17.2-7.7 17.2-17.2.1-9.5-7.6-17.1-17.1-17.1z" fill="#25D366"/>
                        </svg>
                    </div>
                </div>

                <!-- Texto -->
                <span class="font-medium text-sm whitespace-nowrap">¿Necesitas ayuda?</span>
            </a>

            <!-- Tooltip que aparece al pasar el mouse -->
            <div class="absolute bottom-full right-0 mb-3 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 transform opacity-0 scale-95 transition-all duration-300 pointer-events-none group-hover:opacity-100 group-hover:scale-100">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">¡Contáctanos por WhatsApp!</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Respuesta inmediata</p>
                <!-- Flecha del tooltip -->
                <div class="absolute bottom-0 right-6 transform translate-y-1/2 rotate-45 w-3 h-3 bg-white dark:bg-gray-800"></div>
            </div>
        </div>

        <!-- Animaciones adicionales para el nuevo diseño -->
        <style>
        @keyframes float-delayed { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .animate-float-delayed { animation: float-delayed 3s ease-in-out infinite; animation-delay: 0.5s; }
        @keyframes pulse-slow { 0%, 100% { transform: scale(1); opacity: 0.3; } 50% { transform: scale(1.1); opacity: 0.6; } }
        .animate-pulse-slow { animation: pulse-slow 2s ease-in-out infinite; }
        </style>
    </body>
</html>
