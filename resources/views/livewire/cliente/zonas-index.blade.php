<div class="space-y-6">
    <!-- Encabezado -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <flux:icon.signal class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Mis Zonas</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Gestiona tus zonas de portal cautivo y descarga los archivos para Mikrotik
                </p>
            </div>
        </div>
    </div>

    <!-- Mensajes flash -->
    @if (session()->has('message'))
        <div class="p-4 bg-green-100 dark:bg-green-900 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-200 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-red-100 dark:bg-red-900 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Controles de búsqueda -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4 justify-between">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                        </svg>
                    </div>
                    <input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre..."
                        class="w-full pl-10 pr-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="perPage" class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="5">5 por página</option>
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Lista de zonas -->
    @if($zonas->count() > 0)
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($zonas as $zona)
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <!-- Header de la card -->
                    <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-1">
                                    {{ $zona->nombre }}
                                </h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $zona->descripcion ?: 'Sin descripción' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $zona->tipo_registro === 'sin_registro' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                {{ ucfirst(str_replace('_', ' ', $zona->tipo_registro)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Contenido de la card -->
                    <div class="p-6 space-y-4">
                        <!-- Información básica -->
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Cuenta regresiva</span>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $zona->segundos }}s</p>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Campañas</span>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $zona->campanas->count() }}</p>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Auth Mikrotik</span>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $zona->getTipoAutenticacionMikrotikLabelAttribute() }}</p>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Tipo campañas</span>
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    @if ($zona->seleccion_campanas === 'aleatorio')
                                        Alternancia auto
                                    @elseif ($zona->seleccion_campanas === 'prioridad')
                                        Por prioridad
                                    @elseif ($zona->seleccion_campanas === 'video')
                                        Solo videos
                                    @elseif ($zona->seleccion_campanas === 'imagen')
                                        Solo imágenes
                                    @else
                                        {{ ucfirst($zona->seleccion_campanas) }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- URL de acceso -->
                        @php
                            $loginId = $zona->id_personalizado ?: $zona->id;
                            $loginUrl = "https://i-free.com.mx/login_formulario/{$loginId}";
                        @endphp
                        <div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">URL de acceso:</span>
                            <div class="flex items-center gap-2 mt-1 p-2 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                                <input
                                    value="{{ $loginUrl }}"
                                    readonly
                                    class="flex-1 text-xs px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white"
                                />
                                <button
                                    onclick="navigator.clipboard.writeText('{{ $loginUrl }}')"
                                    title="Copiar URL"
                                    class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors"
                                >
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="p-6 pt-0 space-y-3">
                        <!-- Botón principal: Configurar campañas -->
                        <a href="{{ route('cliente.zonas.configuracion-campanas', ['zonaId' => $zona->id]) }}"
                           class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Configurar Campañas
                        </a>

                        <!-- Dropdown con más opciones -->
                        <div class="relative" x-data="{ open: false }">
                            <button
                                @click="open = !open"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white font-medium rounded-lg transition-colors"
                            >
                                Más acciones
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 @click.away="open = false"
                                 class="absolute z-10 mt-2 w-56 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg">

                                <!-- Vista previa -->
                                <div class="p-2">
                                    <div class="px-3 py-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Vista previa</div>
                                    <a href="{{ route('cliente.zona.preview', ['id' => $zona->id]) }}" target="_blank"
                                       class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Vista normal
                                    </a>

                                    <a href="{{ route('cliente.zona.preview.carrusel', ['id' => $zona->id]) }}" target="_blank"
                                       class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Con carrusel
                                    </a>

                                    <a href="{{ route('cliente.zona.preview.video', ['id' => $zona->id]) }}" target="_blank"
                                       class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Con video
                                    </a>
                                </div>

                                @if($zona->tipo_registro != 'sin_registro')
                                <!-- Gestión de formularios -->
                                <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">
                                    <div class="px-3 py-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Formularios</div>
                                    <a href="{{ route('admin.zone.form-fields', ['zonaId' => $zona->id]) }}"
                                       class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Configurar campos
                                    </a>

                                    <a href="{{ route('cliente.zona.formulario', ['zonaId' => $zona->id]) }}" target="_blank"
                                       class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Ver formulario
                                    </a>

                                    <a href="{{ route('admin.zona.form-responses', ['zonaId' => $zona->id]) }}"
                                       class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Ver respuestas
                                    </a>
                                </div>
                                @endif

                                <!-- Archivos Mikrotik -->
                                <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">
                                    <div class="px-3 py-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Archivos Mikrotik</div>
                                    <button wire:click="downloadFile({{ $zona->id }}, 'login')"
                                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded text-left">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Descargar login.html
                                    </button>

                                    <button wire:click="downloadFile({{ $zona->id }}, 'alogin')"
                                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded text-left">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Descargar alogin.html
                                    </button>

                                    <button wire:click="openInstructionsModal({{ $zona->id }})"
                                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded text-left">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Ver instrucciones
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginación -->
        <div class="mt-6">
            {{ $zonas->links() }}
        </div>
    @else
        <!-- Estado vacío -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <flux:icon.signal class="mx-auto h-12 w-12 text-zinc-400 mb-4" />
            <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-2">No tienes zonas asignadas</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                @if($search)
                    No se encontraron zonas que coincidan con "{{ $search }}"
                @else
                    Contacta con el administrador para que te asigne zonas
                @endif
            </p>
            @if($search)
                <button wire:click="$set('search', '')"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg transition-colors">
                    Limpiar búsqueda
                </button>
            @endif
        </div>
    @endif

    <!-- Modal de instrucciones -->
    @if($showInstructionsModal && $selectedZona)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="closeInstructionsModal"></div>

                <div class="relative w-full max-w-2xl bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                <flux:icon.information-circle class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    Instrucciones de Instalación
                                </h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Zona: {{ $selectedZona->nombre }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Paso 1 -->
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-2">1. Descargar archivos</h4>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                                    Descarga los archivos login.html y alogin.html desde el menú "Más acciones".
                                </p>
                            </div>

                            <!-- Paso 2 -->
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-2">2. Subir a Mikrotik</h4>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Sube los archivos a tu router Mikrotik en la carpeta Hotspot, reemplazando los archivos existentes.
                                </p>
                            </div>

                            <!-- Paso 3 -->
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg">
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-2">3. Configurar autenticación</h4>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                                    Tipo configurado: <strong>{{ $selectedZona->getTipoAutenticacionMikrotikLabelAttribute() }}</strong>
                                </p>

                                @if($selectedZona->tipo_autenticacion_mikrotik == 'pin')
                                    <ul class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1">
                                        <li>• Los usuarios solo ingresan un código PIN</li>
                                        <li>• Crea usuarios en Mikrotik donde el nombre sea el PIN</li>
                                        <li>• La contraseña puede ser igual al PIN</li>
                                    </ul>
                                @elseif($selectedZona->tipo_autenticacion_mikrotik == 'usuario_password')
                                    <ul class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1">
                                        <li>• Los usuarios ingresan nombre y contraseña</li>
                                        <li>• Crea usuarios en Mikrotik con credenciales completas</li>
                                    </ul>
                                @elseif($selectedZona->tipo_autenticacion_mikrotik == 'sin_autenticacion')
                                    <ul class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1">
                                        <li>• Conexión automática sin credenciales</li>
                                        <li>• Solo se muestra contador regresivo</li>
                                    </ul>
                                @endif
                            </div>

                            <!-- URL de configuración -->
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <h4 class="font-semibold text-zinc-900 dark:text-white mb-2">4. URL de redirección</h4>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                                    Configura esta URL en tu Hotspot Profile:
                                </p>
                                <div class="p-2 bg-white dark:bg-zinc-800 rounded border text-xs font-mono">
                                    https://i-free.com.mx/login_formulario/{{ $selectedZona->id_personalizado ?: $selectedZona->id }}
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-6">
                            <button wire:click="closeInstructionsModal"
                                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg transition-colors">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
