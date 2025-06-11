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
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Formulario
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
                                {{ $metrica->dispositivo }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $metrica->sistema_operativo ?? 'Desconocido' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($metrica->tipo_visual === 'formulario') bg-blue-100 text-blue-800
                                    @elseif($metrica->tipo_visual === 'carrusel') bg-green-100 text-green-800
                                    @elseif($metrica->tipo_visual === 'video') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($metrica->tipo_visual) }}
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
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
</div>

<script>
    // Usar window para evitar redeclaraciones en Livewire
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
