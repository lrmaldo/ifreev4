<div class="space-y-6">
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
                <button wire:click="openModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg flex items-center">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nueva Zona
                </button>
            </div>
        </div>
    </div>

    <!-- Lista de zonas en formato tabla -->
    @if($zonas->count() > 0)
        <div class="w-full overflow-x-auto bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="text-sm text-gray-500 px-4 py-2 bg-gray-100 md:hidden">
                Desliza horizontalmente para ver más información
            </div>
            <style>
                /* Estilos optimizados para la tabla de zonas del cliente */
                .tabla-container {
                    min-width: 100%;
                }

                .tabla-zonas {
                    width: 100%;
                    table-layout: fixed;
                    min-width: 1200px;
                }

                .tabla-zonas th,
                .tabla-zonas td {
                    text-align: left;
                    vertical-align: top;
                    padding: 12px 16px;
                    border-bottom: 1px solid #e5e7eb;
                }

                .tabla-zonas th {
                    background-color: #f9fafb;
                    font-weight: 600;
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: #6b7280;
                }

                .tabla-zonas tbody tr:hover {
                    background-color: #f3f4f6;
                }

                /* Anchos de columna específicos */
                .col-zona { width: 25%; }
                .col-config { width: 20%; }
                .col-campanas { width: 15%; }
                .col-auth { width: 15%; }
                .col-url { width: 15%; }
                .col-acciones { width: 10%; }

                /* Responsive para móviles */
                @media (max-width: 640px) {
                    .tabla-zonas {
                        min-width: 800px;
                    }

                    .tabla-zonas th,
                    .tabla-zonas td {
                        padding: 8px 12px;
                        font-size: 14px;
                    }

                    .col-zona { width: 30%; }
                    .col-config { width: 25%; }
                    .col-campanas { width: 15%; }
                    .col-auth { width: 20%; }
                    .col-url { width: 15%; }
                    .col-acciones { width: 10%; }
                }
            </style>
            <div class="tabla-container">
                <table class="tabla-zonas divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="col-zona">Zona</th>
                            <th class="col-config">Configuración</th>
                            <th class="col-campanas">Campañas</th>
                            <th class="col-auth">Autenticación</th>
                            <th class="col-url">URL de Acceso</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($zonas as $zona)
                            <tr class="hover:bg-gray-50">
                                <!-- Información de la zona -->
                                <td class="col-zona">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $zona->nombre }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $zona->descripcion ?: 'Sin descripción' }}
                                        </div>
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $zona->tipo_registro === 'sin_registro' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst(str_replace('_', ' ', $zona->tipo_registro)) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Configuración -->
                                <td class="col-config">
                                    <div class="text-sm text-gray-900">
                                        <div class="mb-1">
                                            <span class="font-medium">Cuenta regresiva:</span> {{ $zona->segundos }}s
                                        </div>
                                        <div>
                                            <span class="font-medium">Tipo campañas:</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                @if ($zona->seleccion_campanas === 'aleatorio')
                                                    bg-green-100 text-green-800
                                                @elseif ($zona->seleccion_campanas === 'prioridad')
                                                    bg-orange-100 text-orange-800
                                                @elseif ($zona->seleccion_campanas === 'video')
                                                    bg-blue-100 text-blue-800
                                                @elseif ($zona->seleccion_campanas === 'imagen')
                                                    bg-purple-100 text-purple-800
                                                @else
                                                    bg-gray-100 text-gray-800
                                                @endif
                                            ">
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
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Campañas -->
                                <td class="col-campanas">
                                    <div class="text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <span class="text-2xl font-bold text-blue-600">{{ $zona->campanas->count() }}</span>
                                            <span class="ml-1 text-gray-500">campañas</span>
                                        </div>
                                        @if($zona->campanas->count() > 0)
                                            <div class="text-xs text-gray-500 mt-1">
                                                Activas: {{ $zona->campanas->where('activo', true)->count() }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Autenticación -->
                                <td class="col-auth">
                                    <div class="text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $zona->getTipoAutenticacionMikrotikLabelAttribute() }}
                                        </span>
                                    </div>
                                </td>

                                <!-- URL de acceso -->
                                <td class="col-url">
                                    @php
                                        $loginId = $zona->id_personalizado ?: $zona->id;
                                        $loginUrl = "https://i-free.com.mx/login_formulario/{$loginId}";
                                    @endphp
                                    <div class="text-sm">
                                        <div class="flex items-center gap-2">
                                            <input
                                                value="{{ $loginUrl }}"
                                                readonly
                                                class="flex-1 text-xs px-2 py-1 border border-gray-300 rounded bg-gray-50 text-gray-700 max-w-32"
                                                title="{{ $loginUrl }}"
                                            />
                                            <button
                                                onclick="navigator.clipboard.writeText('{{ $loginUrl }}')"
                                                title="Copiar URL"
                                                class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors"
                                            >
                                                <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <!-- Acciones -->
                                <td class="col-acciones">
                                    <div class="flex items-center gap-2">
                                        <!-- Configurar campañas -->
                                        <a href="{{ route('cliente.zonas.configuracion-campanas', ['zonaId' => $zona->id]) }}"
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                           title="Configurar campañas">
                                            <svg class="h-5 w-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                                <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                            </svg>
                                        </a>

                                        <!-- Editar -->
                                        <button wire:click="edit({{ $zona->id }})"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                title="Editar zona">
                                            <svg class="h-5 w-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                                <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>

                                        <!-- Eliminar -->
                                        <button wire:click="confirmDelete({{ $zona->id }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                title="Eliminar zona">
                                            <svg class="h-5 w-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>

                                        <!-- Dropdown para más opciones -->
                                        <div class="relative" x-data="{ open: false, position: 'right' }"
                                             x-init="$watch('open', value => {
                                                if (value) {
                                                    const rect = $el.getBoundingClientRect();
                                                    const windowWidth = window.innerWidth;
                                                    position = rect.left > windowWidth / 2 ? 'left' : 'right';
                                                }
                                             })">
                                            <button
                                                @click="open = !open"
                                                onclick="detectPosition(event, 'dropdown-{{ $zona->id }}')"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                title="Más opciones">
                                                <svg class="h-5 w-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                                </svg>
                                            </button>

                                        <div id="dropdown-{{ $zona->id }}"
                                             x-show="open"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95"
                                             x-touchout="open = false"
                                             class="absolute mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
                                             :class="position === 'left' ? 'right-0' : 'left-0'">

                                            <!-- Configurar campañas (principal) -->
                                            <div class="p-3 border-b border-gray-200">
                                                <a href="{{ route('cliente.zonas.configuracion-campanas', ['zonaId' => $zona->id]) }}"
                                                   class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                                    </svg>
                                                    Configurar Campañas
                                                </a>
                                            </div>

                                            <!-- Vista previa -->
                                            <div class="p-2">
                                                <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Vista previa</div>
                                                <a href="{{ route('cliente.zona.preview', ['id' => $zona->id]) }}" target="_blank"
                                                   class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Vista normal
                                                </a>

                                                <a href="{{ route('cliente.zona.preview.carrusel', ['id' => $zona->id]) }}" target="_blank"
                                                   class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    Con carrusel
                                                </a>

                                                <a href="{{ route('cliente.zona.preview.video', ['id' => $zona->id]) }}" target="_blank"
                                                   class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    Con video
                                                </a>
                                            </div>

                                            @if($zona->tipo_registro != 'sin_registro')
                                            <!-- Gestión de formularios -->
                                            <div class="p-2 border-t border-gray-200">
                                                <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Formularios</div>
                                                <a href="{{ route('admin.zone.form-fields', ['zonaId' => $zona->id]) }}"
                                                   class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Configurar campos
                                                </a>

                                                <a href="{{ route('cliente.zona.formulario', ['zonaId' => $zona->id]) }}" target="_blank"
                                                   class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Ver formulario
                                                </a>

                                                <a href="{{ route('admin.zona.form-responses', ['zonaId' => $zona->id]) }}"
                                                   class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                    </svg>
                                                    Ver respuestas
                                                </a>
                                            </div>
                                            @endif

                                            <!-- Archivos Mikrotik -->
                                            <div class="p-2 border-t border-gray-200">
                                                <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Archivos Mikrotik</div>
                                                <button wire:click="downloadFile({{ $zona->id }}, 'login')"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded text-left">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Descargar login.html
                                                </button>

                                                <button wire:click="downloadFile({{ $zona->id }}, 'alogin')"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded text-left">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Descargar alogin.html
                                                </button>

                                                <button wire:click="openInstructionsModal({{ $zona->id }})"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded text-left">
                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Ver instrucciones
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <div class="mt-6">
            {{ $zonas->links() }}
        </div>
    @else
        <!-- Estado vacío -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>
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

    <!-- Modal para crear/editar zona -->
    @if ($showModal)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                {{ $editMode ? 'Editar Zona' : 'Nueva Zona' }}
                            </h3>
                            <div class="mt-4 space-y-4">
                                <!-- Nombre -->
                                <div>
                                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                                    <input wire:model="nombre" type="text" id="nombre" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Descripción -->
                                <div>
                                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción (opcional)</label>
                                    <textarea wire:model="descripcion" id="descripcion" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                    @error('descripcion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- ID Personalizado -->
                                <div>
                                    <label for="id_personalizado" class="block text-sm font-medium text-gray-700">ID Personalizado (opcional)</label>
                                    <input wire:model="id_personalizado" type="text" id="id_personalizado" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <p class="mt-1 text-sm text-gray-500">Si se especifica, se usará en la URL en lugar del ID numérico</p>
                                    @error('id_personalizado') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Tipo de Registro -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tipo de Registro</label>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <input wire:model="tipo_registro" id="sin_registro" name="tipo_registro" type="radio" value="sin_registro" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="sin_registro" class="ml-3 block text-sm font-medium text-gray-700">Sin registro</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="tipo_registro" id="registro_simple" name="tipo_registro" type="radio" value="registro_simple" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="registro_simple" class="ml-3 block text-sm font-medium text-gray-700">Registro simple</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="tipo_registro" id="registro_completo" name="tipo_registro" type="radio" value="registro_completo" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="registro_completo" class="ml-3 block text-sm font-medium text-gray-700">Registro completo</label>
                                        </div>
                                    </div>
                                    @error('tipo_registro') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Segundos -->
                                <div>
                                    <label for="segundos" class="block text-sm font-medium text-gray-700">Segundos de cuenta regresiva</label>
                                    <input wire:model="segundos" type="number" id="segundos" min="1" max="300" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    @error('segundos') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Selección de Campañas -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Selección de Campañas</label>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <input wire:model="seleccion_campanas" id="aleatorio" name="seleccion_campanas" type="radio" value="aleatorio" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="aleatorio" class="ml-3 block text-sm font-medium text-gray-700">Alternancia automática</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="seleccion_campanas" id="prioridad" name="seleccion_campanas" type="radio" value="prioridad" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="prioridad" class="ml-3 block text-sm font-medium text-gray-700">Por prioridad</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="seleccion_campanas" id="video" name="seleccion_campanas" type="radio" value="video" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="video" class="ml-3 block text-sm font-medium text-gray-700">Solo videos</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="seleccion_campanas" id="imagen" name="seleccion_campanas" type="radio" value="imagen" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="imagen" class="ml-3 block text-sm font-medium text-gray-700">Solo imágenes</label>
                                        </div>
                                    </div>
                                    @error('seleccion_campanas') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Autenticación Mikrotik -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tipo de Autenticación Mikrotik</label>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <input wire:model="tipo_autenticacion_mikrotik" id="sin_autenticacion" name="tipo_autenticacion_mikrotik" type="radio" value="sin_autenticacion" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="sin_autenticacion" class="ml-3 block text-sm font-medium text-gray-700">Sin autenticación</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="tipo_autenticacion_mikrotik" id="pin" name="tipo_autenticacion_mikrotik" type="radio" value="pin" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="pin" class="ml-3 block text-sm font-medium text-gray-700">Solo PIN</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input wire:model="tipo_autenticacion_mikrotik" id="usuario_password" name="tipo_autenticacion_mikrotik" type="radio" value="usuario_password" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            <label for="usuario_password" class="ml-3 block text-sm font-medium text-gray-700">Usuario y Contraseña</label>
                                        </div>
                                    </div>
                                    @error('tipo_autenticacion_mikrotik') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button wire:click="save" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            {{ $editMode ? 'Actualizar' : 'Crear' }}
                        </button>
                        <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de confirmación para eliminar zona -->
    @if ($confirmingZonaDeletion)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Eliminar Zona
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ¿Estás seguro de que quieres eliminar esta zona? Esta acción no se puede deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button wire:click="deleteZona" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Eliminar
                        </button>
                        <button wire:click="$set('confirmingZonaDeletion', false)" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
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
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
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

    <script>
        // Función para detectar y ajustar la posición de los dropdowns
        window.detectPosition = function(event, dropdownId) {
            const button = event.currentTarget;
            const dropdown = document.getElementById(dropdownId);
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            const isMobile = windowWidth < 640; // Punto de corte para dispositivos móviles (sm en Tailwind)
            const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);

            // Obtener la posición de scroll
            const scrollX = window.scrollX || window.pageXOffset;
            const scrollY = window.scrollY || window.pageYOffset;

            if (!dropdown || !button) return;

            // Obtener las coordenadas del botón relativas a la ventana
            const buttonRect = button.getBoundingClientRect();

            // Calcular espacio disponible en cada dirección, considerando el scroll
            const spaceRight = windowWidth - buttonRect.right;
            const spaceLeft = buttonRect.left;
            const spaceBelow = windowHeight - buttonRect.bottom;
            const spaceAbove = buttonRect.top;

            // Guardar temporalmente el display original
            const originalDisplay = dropdown.style.display;
            dropdown.style.display = 'block';
            dropdown.style.visibility = 'hidden';

            // Obtener dimensiones del dropdown
            const dropdownWidth = dropdown.offsetWidth;
            const dropdownHeight = dropdown.offsetHeight;

            // Restaurar el display original
            dropdown.style.display = originalDisplay;
            dropdown.style.visibility = '';

            // Determinar la mejor posición según el espacio disponible
            let position = 'right';

            if (isMobile || isTouchDevice) {
                // En dispositivos móviles, elegimos la mejor posición considerando el espacio disponible
                // y la posición del botón en la pantalla

                // Verificar si el botón está en la mitad izquierda o derecha de la pantalla
                const isButtonOnRightSide = buttonRect.left > (windowWidth / 2);

                // Por defecto, mostramos abajo
                position = 'center-bottom';

                // Si no hay suficiente espacio abajo pero hay arriba, mostrarlo arriba
                if (spaceBelow < Math.min(250, dropdownHeight) && spaceAbove > dropdownHeight) {
                    position = 'center-top';
                }

                // Configuración para mejorar la experiencia en dispositivos móviles
                dropdown.style.maxHeight = Math.min(300, windowHeight * 0.7) + 'px';
                dropdown.style.overflowY = 'auto';
                dropdown.style.overflowX = 'hidden';
                dropdown.style.webkitOverflowScrolling = 'touch'; // Para mejorar el scroll en iOS
            } else {
                // Lógica para pantallas más grandes no táctiles
                if (spaceRight < dropdownWidth && spaceLeft > dropdownWidth) {
                    position = 'left';
                } else if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                    position = 'top-right';
                }
            }

            // Aplicar posicionamiento al dropdown
            dropdown.style.position = 'fixed'; // Usar posicionamiento fijo para evitar problemas con scroll
            dropdown.style.zIndex = '999'; // Aumentar el z-index para asegurar que esté por encima de otros elementos

            // Mejorar la visibilidad en todos los dispositivos
            dropdown.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2)';
            dropdown.style.transition = 'opacity 0.25s ease-out, transform 0.25s ease-out';
            dropdown.style.opacity = '0';
            dropdown.style.transform = 'scale(0.95)';
            dropdown.style.borderRadius = '0.5rem'; // Bordes más redondeados (equivalente a rounded-lg)
            dropdown.style.border = '1px solid rgba(209, 213, 219, 0.7)'; // Borde más visible
            dropdown.style.backgroundColor = 'rgba(255, 255, 255, 0.98)'; // Fondo más opaco

            // Posicionar el dropdown basado en la posición determinada
            if (position === 'center-bottom') {
                // Para móviles: calcular mejor posición horizontal
                const buttonCenter = buttonRect.left + (buttonRect.width / 2);
                const idealLeft = buttonCenter - (dropdownWidth / 2);

                // Asegurarnos que no se salga por los bordes
                const safeLeft = Math.max(10, Math.min(windowWidth - dropdownWidth - 10, idealLeft));

                dropdown.style.left = safeLeft + 'px';
                dropdown.style.top = (buttonRect.bottom + 8) + 'px';
                dropdown.style.maxWidth = (windowWidth - 20) + 'px';
                dropdown.style.minWidth = Math.min(250, windowWidth - 20) + 'px';
                dropdown.style.right = 'auto';
            } else if (position === 'center-top') {
                // Para mostrar arriba del botón
                const buttonCenter = buttonRect.left + (buttonRect.width / 2);
                const idealLeft = buttonCenter - (dropdownWidth / 2);

                // Asegurarnos que no se salga por los bordes
                const safeLeft = Math.max(10, Math.min(windowWidth - dropdownWidth - 10, idealLeft));

                dropdown.style.left = safeLeft + 'px';
                dropdown.style.bottom = (windowHeight - buttonRect.top + 8) + 'px';
                dropdown.style.top = 'auto'; // Usar bottom en lugar de top para mejor alineación
                dropdown.style.maxWidth = (windowWidth - 20) + 'px';
                dropdown.style.minWidth = Math.min(250, windowWidth - 20) + 'px';
                dropdown.style.right = 'auto';
            } else if (position === 'right') {
                // Posicionamiento estándar a la derecha
                let leftPos = buttonRect.left;

                // Asegurarse que no se salga de la pantalla por la derecha
                if (leftPos + dropdownWidth > windowWidth - 10) {
                    leftPos = windowWidth - dropdownWidth - 10;
                }

                dropdown.style.left = leftPos + 'px';
                dropdown.style.top = buttonRect.bottom + 5 + 'px';
            } else if (position === 'left') {
                // Posicionamiento a la izquierda
                let leftPos = buttonRect.right - dropdownWidth;

                // Asegurarse que no se salga de la pantalla por la izquierda
                if (leftPos < 10) {
                    leftPos = 10;
                }

                dropdown.style.left = leftPos + 'px';
                dropdown.style.top = buttonRect.bottom + 5 + 'px';
            }

            // Forzar repintado del DOM para que la animación funcione correctamente
            requestAnimationFrame(() => {
                dropdown.style.opacity = '1';
                dropdown.style.transform = 'scale(1)';
            });
        };

        // Función para detectar si es un dispositivo móvil
        function esMobile() {
            return window.innerWidth < 640 || ('ontouchstart' in window) ||
                   (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);
        }

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir soporte para eventos táctiles en dispositivos móviles
            document.addEventListener('touchstart', function() {
                // Esto activa el procesamiento de eventos táctiles
            }, { passive: true });

            // Repositionar dropdowns al hacer scroll (vertical u horizontal)
            window.addEventListener('scroll', handleScroll);

            // También capturar el scroll horizontal en la tabla
            document.querySelectorAll('.overflow-x-auto').forEach(el => {
                el.addEventListener('scroll', handleScroll, { passive: true });
            });

            // Función para manejar el scroll
            function handleScroll() {
                // Buscar todos los dropdowns visibles y cerrarlos
                document.querySelectorAll('[id^="dropdown-"]').forEach(function(dropdown) {
                    if (dropdown.style.display !== 'none' && dropdown.offsetParent !== null) {
                        // Cerrar el dropdown
                        const alpineComponent = dropdown.closest('[x-data]');
                        if (alpineComponent && alpineComponent.__x) {
                            alpineComponent.__x.$data.open = false;
                        }
                    }
                });
            }
        });
    </script>
</div>
