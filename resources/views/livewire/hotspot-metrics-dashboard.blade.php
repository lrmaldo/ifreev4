<div class="space-y-6">
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Zona -->
            <div>
                <x-label for="zona_id" value="Zona" />
                <select wire:model.live="zona_id" id="zona_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">Todas las zonas</option>
                    @foreach($zonas as $zona)
                        <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Fecha Inicio -->
            <div>
                <x-label for="fecha_inicio" value="Fecha Inicio" />
                <x-input type="date" wire:model.live="fecha_inicio" id="fecha_inicio"
                         class="focus:border-orange-500 focus:ring-orange-500" />
            </div>

            <!-- Fecha Fin -->
            <div>
                <x-label for="fecha_fin" value="Fecha Fin" />
                <x-input type="date" wire:model.live="fecha_fin" id="fecha_fin"
                         class="focus:border-orange-500 focus:ring-orange-500" />
            </div>

            <!-- MAC Address -->
            <div>
                <x-label for="mac_address" value="MAC Address" />
                <x-input type="text" wire:model.live.debounce.300ms="mac_address" id="mac_address"
                         placeholder="Buscar por MAC..."
                         class="focus:border-orange-500 focus:ring-orange-500" />
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex flex-wrap gap-2 mt-4">
            <button wire:click="clearFilters"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Limpiar Filtros
            </button>
            @can('gestionar metricas hotspot')
            <x-button wire:click="exportData"
                      class="bg-orange-600 hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-800 focus:ring-orange-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exportar CSV
            </x-button>

            <button onclick="generateWrapped()"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-900 focus:outline-none focus:border-purple-900 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Generar Wrapped
            </button>
            @endcan
        </div>
    </div>    <!-- Estadísticas Generales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Visitas -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg" style="background-color: rgba(255, 63, 0, 0.1);">
                    <svg class="w-6 h-6" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Total Visitas</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($estadisticas['total_visitas'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Dispositivos Únicos -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg" style="background-color: rgba(255, 63, 0, 0.1);">
                    <svg class="w-6 h-6" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Dispositivos Únicos</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($estadisticas['dispositivos_unicos'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Formularios Completados -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg" style="background-color: rgba(255, 63, 0, 0.1);">
                    <svg class="w-6 h-6" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Formularios</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($estadisticas['formularios_completados'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Tasa de Conversión -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg" style="background-color: rgba(255, 63, 0, 0.1);">
                    <svg class="w-6 h-6" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Tasa Conversión</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $estadisticas['tasa_conversion'] ?? 0 }}%</p>
                </div>
            </div>
        </div>
    </div>    <!-- Estadísticas Adicionales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Duración Promedio -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg" style="background-color: rgba(255, 63, 0, 0.1);">
                    <svg class="w-6 h-6" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Duración Promedio</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $estadisticas['duracion_promedio'] ?? 0 }}s</p>
                </div>
            </div>
        </div>

        <!-- Clicks CTA -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg" style="background-color: rgba(255, 63, 0, 0.1);">
                    <svg class="w-6 h-6" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Clicks CTA</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($estadisticas['clics_boton'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Usuarios Recurrentes -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center">
                <div class="p-2 rounded-lg" style="background-color: rgba(255, 63, 0, 0.1);">
                    <svg class="w-6 h-6" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-500">Usuarios Recurrentes</p>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($estadisticas['usuarios_recurrentes'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>    <!-- Gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Visitas por Día -->
        <div class="bg-white rounded-lg shadow-md p-4 md:p-6" wire:ignore>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Visitas Diarias (Últimos 30 días)
                </div>
            </h3>
            <div wire:ignore>
                <canvas id="visitasChart" height="200"></canvas>
            </div>
        </div>

        <!-- Dispositivos Populares -->
        <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Dispositivos Más Utilizados
                </div>
            </h3>
            <div class="space-y-3">
                @foreach($dispositivosPopulares as $dispositivo)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">{{ $dispositivo['dispositivo'] ?: 'Desconocido' }}</span>
                    <span class="text-sm font-semibold text-gray-900">{{ number_format($dispositivo['total']) }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full" style="width: {{ ($dispositivo['total'] / ($dispositivosPopulares[0]['total'] ?? 1)) * 100 }}%; background-color: #ff3f00;"></div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sistemas Operativos Populares -->
    <div class="bg-white rounded-lg shadow-md p-4 md:p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Sistemas Operativos Más Utilizados
            </div>
        </h3>
        <div class="space-y-3">
            @foreach($sistemasOperativosPopulares as $so)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ $so['sistema_operativo'] ?: 'Desconocido' }}</span>
                <span class="text-sm font-semibold text-gray-900">{{ number_format($so['total']) }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full" style="width: {{ ($so['total'] / ($sistemasOperativosPopulares[0]['total'] ?? 1)) * 100 }}%; background-color: #ff3f00;"></div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Tabla de Métricas Detalladas -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" style="color: #ff3f00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v2z"></path>
                    </svg>
                    Métricas Detalladas
                </div>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('created_at')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center">
                                Fecha
                                @if($order_by === 'created_at')
                                    <span class="ml-1">{{ $order_direction === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zona
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            MAC Address
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dispositivo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sistema Operativo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo Visual
                        </th>
                        <th wire:click="sortBy('veces_entradas')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center">
                                Entradas
                                @if($order_by === 'veces_entradas')
                                    <span class="ml-1">{{ $order_direction === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Formulario
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($metricas as $metrica)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $metrica->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $metrica->zona->nombre ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                {{ $metrica->mac_address }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @php
                                    $dispositivo = $metrica->dispositivo;
                                    // Limitar la longitud a 25 caracteres para mejor visualización en la tabla
                                    if (strlen($dispositivo) > 25) {
                                        $dispositivo = substr($dispositivo, 0, 22) . '...';
                                    }
                                @endphp
                                <span title="{{ $metrica->dispositivo }}">{{ $dispositivo }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @php
                                    $sistemaOperativo = $metrica->sistema_operativo ?? 'Desconocido';
                                    // Limitar la longitud a 20 caracteres
                                    if (strlen($sistemaOperativo) > 20) {
                                        $sistemaOperativo = substr($sistemaOperativo, 0, 17) . '...';
                                    }
                                @endphp
                                <span title="{{ $metrica->sistema_operativo ?? 'Desconocido' }}">{{ $sistemaOperativo }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($metrica->tipo_visual === 'formulario') bg-blue-100 text-blue-800
                                    @elseif($metrica->tipo_visual === 'carrusel') bg-green-100 text-green-800
                                    @elseif($metrica->tipo_visual === 'video') bg-purple-100 text-purple-800
                                    @elseif($metrica->tipo_visual === 'login') bg-orange-100 text-orange-800
                                    @elseif($metrica->tipo_visual === 'portal_cautivo') bg-yellow-100 text-yellow-800
                                    @elseif($metrica->tipo_visual === 'portal_entrada') bg-indigo-100 text-indigo-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $metrica->tipo_visual)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-semibold">{{ $metrica->veces_entradas }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($metrica->formulario_id)
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Completado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                        Sin completar
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('hotspot-metrics.detalles', $metrica->id) }}"
                                   class="text-orange-600 hover:text-orange-900 inline-flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Detalles
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                No se encontraron métricas para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $metricas->links() }}
        </div>
    </div>

    <!-- Contenedor para el Wrapped (oculto y aislado de Flux) -->
    <div style="position: fixed; left: -9999px; top: -9999px; z-index: -100;" id="wrapped-outer-container">
        <!-- Aumentamos la altura a 1100px para que no se corte la gráfica -->
        <div id="wrapped-card" style="width: 600px; height: 1100px; background: linear-gradient(to bottom right, #ff3f00, #591100); color: white; padding: 40px; display: flex; flex-direction: column; justify-content: space-between; font-family: sans-serif; position: relative; overflow: hidden; box-sizing: border-box; border: 0px solid transparent;">
            
            <!-- Partículas decorativas -->
            <div style="position: absolute; top: -80px; right: -80px; width: 320px; height: 320px; background: rgba(255,255,255,0.1); border-radius: 9999px; filter: blur(60px); border: 0px solid transparent;"></div>
            <div style="position: absolute; bottom: -80px; left: -80px; width: 256px; height: 256px; background: rgba(255, 63, 0, 0.2); border-radius: 9999px; filter: blur(40px); border: 0px solid transparent;"></div>

            <!-- Header -->
            <div style="z-index: 10; text-align: center; position: relative; border: 0px solid transparent;">
                <div style="display: flex; justify-content: center; margin-bottom: 20px; border: 0px solid transparent;">
                    <svg width="60" height="60" viewBox="0 0 100 100" fill="white" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3)); border: 0px solid transparent;">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="white" stroke-width="2" />
                        <path d="M30 70 L50 30 L70 70" stroke="white" stroke-width="6" fill="none" stroke-linecap="round" />
                    </svg>
                </div>
                <h1 style="font-size: 32px; font-weight: 900; letter-spacing: -0.02em; text-transform: uppercase; margin: 0 0 5px 0; line-height: 1; color: white; border: 0px solid transparent;">METRIC WRAPPED</h1>
                <p style="color: #ffd5c2; font-size: 13px; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; margin: 0; border: 0px solid transparent;">Resumen de tu Zona</p>
                <div style="height: 4px; width: 80px; background: #ffffff; margin: 15px auto 0; border-radius: 9999px; opacity: 0.8; border: 0px solid transparent;"></div>
            </div>

            <!-- Zona e Info -->
            <div style="z-index: 10; margin-top: 20px; background: rgba(0,0,0,0.3); border-radius: 20px; padding: 25px; border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);">
                <p style="font-size: 10px; color: #ffd5c2; text-transform: uppercase; font-weight: 900; letter-spacing: 0.1em; margin: 0 0 5px 0; border: 0px solid transparent;">Zona Seleccionada</p>
                <h2 style="font-size: 26px; font-weight: 700; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white; border: 0px solid transparent;">
                    {{ $zona_id ? ($zonas->find($zona_id)->nombre ?? 'Todas las Zonas') : 'Todas las Zonas' }}
                </h2>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 13px; color: #f3f4f6; border: 0px solid transparent;">
                    <span style="border: 0px solid transparent;">📅 {{ Carbon\Carbon::parse($fecha_inicio)->format('d/m/Y') }} — {{ Carbon\Carbon::parse($fecha_fin)->format('d/m/Y') }}</span>
                </div>
            </div>

            <!-- Estadísticas Principales -->
            <div style="display: flex; flex-wrap: wrap; gap: 10px; z-index: 10; margin: 20px 0; border: 0px solid transparent;">
                @foreach([
                    ['Visitas', number_format($estadisticas['total_visitas'] ?? 0)],
                    ['Disp. Únicos', number_format($estadisticas['dispositivos_unicos'] ?? 0)],
                    ['Recurrentes', number_format($estadisticas['usuarios_recurrentes'] ?? 0)],
                    ['Conversión', ($estadisticas['tasa_conversion'] ?? 0) . '%'],
                    ['Duración', ($estadisticas['duracion_promedio'] ?? 0) . 's']
                ] as $index => $stat)
                <div style="flex: 1 1 {{ $index < 2 ? 'calc(50% - 10px)' : 'calc(33.33% - 10px)' }}; background: rgba(255,255,255,0.1); border-radius: 15px; padding: 15px; border: 1px solid rgba(255,255,255,0.1); box-sizing: border-box; text-align: center;">
                    <p style="font-size: 9px; color: #ffd5c2; text-transform: uppercase; font-weight: 900; letter-spacing: 0.1em; margin: 0; border: 0px solid transparent;">{{ $stat[0] }}</p>
                    <p style="font-size: {{ $index < 2 ? '26px' : '18px' }}; font-weight: 900; margin: 4px 0 0 0; letter-spacing: -0.05em; color: white; border: 0px solid transparent;">{{ $stat[1] }}</p>
                </div>
                @endforeach
            </div>

            <!-- Dispositivos Populares -->
            <div style="z-index: 10; background: rgba(0,0,0,0.2); border-radius: 20px; padding: 20px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px;">
                <p style="font-size: 10px; color: #ffd5c2; text-transform: uppercase; font-weight: 900; letter-spacing: 0.1em; margin: 0 0 12px 0; text-align: center;">Dispositivos más utilizados</p>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach(array_slice($dispositivosPopulares, 0, 3) as $index => $dispositivo)
                    <div style="display: flex; align-items: center; justify-content: space-between; border: 0px solid transparent;">
                        <div style="display: flex; align-items: center; gap: 10px; border: 0px solid transparent;">
                            <span style="width: 20px; height: 20px; background: {{ ['#ffd5c2', '#ffaa85', '#ff7f4d'][$index] ?? '#ffffff33' }}; color: #591100; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 900;">{{ $index + 1 }}</span>
                            <span style="font-size: 13px; font-weight: 600; color: white;">{{ Str::limit($dispositivo['dispositivo'] ?: 'Desconocido', 25) }}</span>
                        </div>
                        <span style="font-size: 13px; font-weight: 900; color: #ffd5c2;">{{ number_format($dispositivo['total']) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Gráfico (Miniatura) -->
            <div style="z-index: 10; background: #ffffff; border-radius: 20px; padding: 25px; margin-bottom: 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); border: 0px solid transparent;">
                <p style="font-size: 11px; text-align: center; color: #ff3f00; margin: 0 0 15px 0; font-weight: 900; text-transform: uppercase; letter-spacing: 0.2em; border: 0px solid transparent;">Tendencia de Visitas</p>
                <div style="display: flex; align-items: center; justify-content: center; background: #ffffff; border: 0px solid transparent; min-height: 200px;">
                    <!-- Ajustamos el estilo de la imagen para asegurar que se vea -->
                    <img id="wrapped-chart-img" style="width: 100%; max-width: 500px; height: auto; display: block; border: 0px solid transparent;" src="" alt="Cargando gráfica...">
                </div>
            </div>

            <!-- Footer -->
            <div style="z-index: 10; text-align: center; border: 0px solid transparent;">
                <p style="color: white; font-weight: 900; letter-spacing: 0.3em; font-size: 18px; margin: 0; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5)); border: 0px solid transparent;">{{ config('app.name') }}</p>
                <p style="color: rgba(255,213,194,0.8); font-size: 10px; margin: 8px 0 0 0; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; font-style: italic; border: 0px solid transparent;">#YourHotspotWrapped</p>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof html2canvas === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
        document.head.appendChild(script);
    }

    function generateWrapped() {
        const btn = event.currentTarget || document.activeElement;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span style="display:flex;align-items:center;"><svg style="animation:spin 1s linear infinite;margin-right:8px;width:16px;height:16px;" viewBox="0 0 24 24"><circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> GENERANDO...</span>';

        // Asegurar que la gráfica existe y convertirla
        const chartCanvas = document.getElementById('visitasChart');
        if (chartCanvas) {
            try {
                // Forzar la imagen del gráfico al elemento img del Wrapped
                const chartImg = chartCanvas.toDataURL('image/png');
                const targetImg = document.getElementById('wrapped-chart-img');
                targetImg.src = chartImg;
            } catch (e) {
                console.error("No se pudo capturar el canvas:", e);
            }
        }

        // Dar más tiempo (800ms) para que la imagen se renderice en el DOM oculto
        setTimeout(() => {
            const element = document.getElementById('wrapped-card');
            
            html2canvas(element, {
                scale: 2,
                backgroundColor: null,
                logging: false,
                useCORS: true,
                onclone: (clonedDoc) => {
                    const clonedCard = clonedDoc.getElementById('wrapped-card');
                    const allElements = clonedCard.getElementsByTagName('*');
                    for (let el of allElements) {
                        const attrs = el.attributes;
                        for (let i = attrs.length - 1; i >= 0; i--) {
                            const attrName = attrs[i].name;
                            if (attrName.startsWith('data-flux') || attrName.startsWith('wire:') || attrName === 'x-data') {
                                el.removeAttribute(attrName);
                            }
                        }
                        if (el.tagName === 'IMG' && el.id === 'wrapped-chart-img') {
                           // Aseguramos visibilidad en el clon
                           el.style.display = 'block';
                           el.style.visibility = 'visible';
                        }
                    }
                }
            }).then(canvas => {
                const link = document.createElement('a');
                const zonaName = '{{ $zona_id ? "Zona-" . $zona_id : "General" }}';
                link.download = `Wrapped-${zonaName}-${new Date().getTime()}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                btn.disabled = false;
                btn.innerHTML = originalText;
            }).catch(err => {
                console.error("Error al generar el Wrapped:", err);
                btn.disabled = false;
                btn.innerHTML = originalText;
                alert("Error al generar imagen. Prueba recargar la página.");
            });
        }, 800);
    }
    
    // El resto de los scripts de Chart.js se mantienen igual...
    if (!window.visitasChart) {
        window.visitasChart = null;
    }
    if (!window.chartInitialized) {
        window.chartInitialized = false;
    }

    function initVisitasChart() {
        // Verificar que Chart.js esté disponible
        if (typeof Chart === 'undefined') {
            console.log('Chart.js no está disponible, intentando cargar...');
            loadChartJS();
            return;
        }

        const canvas = document.getElementById('visitasChart');
        if (!canvas) {
            console.log('Canvas visitasChart no encontrado');
            return;
        }

        // Si ya está inicializado, solo actualizar datos
        if (window.chartInitialized && window.visitasChart) {
            console.log('Gráfico ya inicializado, actualizando datos...');
            updateChartData();
            return;
        }

        // Destruir el gráfico anterior si existe y tiene el método destroy
        if (window.visitasChart && typeof window.visitasChart.destroy === 'function') {
            console.log('Destruyendo gráfico anterior...');
            window.visitasChart.destroy();
            window.visitasChart = null;
        }

        const ctx = canvas.getContext('2d');

        // Usar datos iniciales del componente
        const visitasData = @json($visitasPorDia);

        // Preparar datos para los últimos 30 días
        const labels = [];
        const data = [];

        for (let i = 29; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            labels.push(date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }));
            data.push(visitasData[dateStr] || 0);
        }

        try {
            window.visitasChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Visitas',
                        data: data,
                        borderColor: '#ff3f00',
                        backgroundColor: 'rgba(255, 63, 0, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ff3f00',
                        pointBorderColor: '#ff3f00',
                        pointHoverBackgroundColor: '#ff3f00',
                        pointHoverBorderColor: '#ff3f00'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            window.chartInitialized = true;
            console.log('Gráfico de visitas inicializado correctamente');
        } catch (error) {
            console.error('Error al inicializar el gráfico:', error);
            window.chartInitialized = false;
        }
    }

    function loadChartJS() {
        if (document.querySelector('script[src*="chart"]')) {
            // Chart.js ya está siendo cargado, esperar
            setTimeout(initVisitasChart, 500);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = function() {
            console.log('Chart.js cargado exitosamente');
            setTimeout(initVisitasChart, 100);
        };
        script.onerror = function() {
            console.error('Error al cargar Chart.js');
        };
        document.head.appendChild(script);
    }

    function updateChartData(newData = null) {
        if (!window.visitasChart || typeof Chart === 'undefined') return;

        // Usar datos nuevos si se proporcionan, sino usar los datos del componente
        const visitasData = newData || @json($visitasPorDia);
        const labels = [];
        const data = [];

        for (let i = 29; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            labels.push(date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }));
            data.push(visitasData[dateStr] || 0);
        }

        try {
            // Reemplazar completamente los datos en lugar de agregar
            window.visitasChart.data.labels = labels;
            window.visitasChart.data.datasets[0].data = data;
            window.visitasChart.update('none');
            console.log('Gráfico actualizado con nuevos datos', data);
        } catch (error) {
            console.error('Error al actualizar datos del gráfico:', error);
        }
    }

    // Variable para controlar las actualizaciones
    let updateTimeout = null;
    let isUpdating = false;
    let lastUpdate = 0;

    // Inicializar cuando la página cargue
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(initVisitasChart, 100);
    });

    // Inicializar cuando Livewire navegue
    document.addEventListener('livewire:navigated', function () {
        window.chartInitialized = false;
        window.visitasChart = null;
        setTimeout(initVisitasChart, 100);
    });

    // Escuchar evento de actualización de datos del gráfico
    window.addEventListener('chartDataUpdated', function (event) {
        console.log('Evento chartDataUpdated recibido:', event.detail);
        const newData = event.detail;
        if (window.chartInitialized && window.visitasChart) {
            updateChartData(newData);
        }
    });

    // Actualizar datos cuando el componente cambie - con control de frecuencia
    document.addEventListener('livewire:updated', function (event) {
        // Solo procesar actualizaciones del componente correcto
        if (!event.detail || !event.detail.component ||
            !event.detail.component.fingerprint ||
            event.detail.component.fingerprint.name !== 'hotspot-metrics-dashboard') {
            return;
        }

        const now = Date.now();

        // Evitar actualizaciones muy frecuentes (máximo una cada 500ms)
        if (now - lastUpdate < 500) {
            return;
        }

        // Cancelar timeout anterior si existe
        if (updateTimeout) {
            clearTimeout(updateTimeout);
        }

        // Evitar actualizaciones múltiples simultáneas
        if (isUpdating) {
            return;
        }

        // Debounce la actualización
        updateTimeout = setTimeout(() => {
            isUpdating = true;
            lastUpdate = Date.now();

            if (window.chartInitialized && window.visitasChart) {
                updateChartData();
            } else {
                initVisitasChart();
            }

            setTimeout(() => {
                isUpdating = false;
            }, 100);
        }, 300);
    });
</script>
