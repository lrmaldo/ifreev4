<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Detalles de Métrica') }}
            </h2>
            <a href="{{ route('hotspot-metrics.index') }}" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información general de la métrica -->
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Información General
                        </h3>
                        <dl class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->id }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Zona</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->zona->nombre ?? 'N/A' }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">MAC Address</dt>
                                <dd class="text-sm font-bold font-mono text-gray-900 dark:text-gray-100">{{ $metrica->mac_address }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->created_at->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dispositivo</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->dispositivo }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Navegador</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->navegador }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sistema Operativo</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->sistema_operativo ?? 'Desconocido' }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo Visual</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->tipo_visual }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duración (seg)</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->duracion_visual }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Clic en Botón</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded {{ $metrica->clic_boton ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $metrica->clic_boton ? 'Sí' : 'No' }}
                                    </span>
                                </dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Veces Entradas</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $metrica->veces_entradas }}</dd>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Formulario Completado</dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded {{ $metrica->formulario_id ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $metrica->formulario_id ? 'Sí' : 'No' }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Estadísticas rápidas de la interacción -->
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Estadísticas de Interacción
                        </h3>

                        <div class="grid grid-cols-2 sm:grid-cols-2 gap-4">
                            <!-- Gráfico de tipo de evento -->
                            <div class="bg-white dark:bg-gray-800 p-3 rounded-md">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipos de Eventos</h4>
                                <div class="h-40" id="chart-eventos"></div>
                            </div>

                            <!-- Gráfico de tiempos -->
                            <div class="bg-white dark:bg-gray-800 p-3 rounded-md">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timeline de Interacción</h4>
                                <div class="h-40" id="chart-timeline"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles de eventos -->
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Registro de Eventos
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha/Hora</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contenido</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detalle</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                                @forelse($detalles as $detalle)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $detalle->fecha_hora->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded
                                        @if($detalle->tipo_evento == 'clic') bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100
                                        @elseif($detalle->tipo_evento == 'vista') bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100
                                        @elseif($detalle->tipo_evento == 'formulario') bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100
                                        @endif">
                                            {{ $detalle->tipo_evento }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $detalle->contenido }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $detalle->detalle }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No se encontraron eventos detallados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Datos para gráficos
            const eventosData = @json($detalles->groupBy('tipo_evento')->map->count());
            const timelineData = @json($detalles->map(function($item) {
                return [
                    $item->fecha_hora->timestamp * 1000, // Convertir a milisegundos para JS
                    1
                ];
            }));

            // Gráfico de tipos de eventos
            if (Object.keys(eventosData).length > 0) {
                const optionsEventos = {
                    series: Object.values(eventosData),
                    chart: {
                        type: 'donut',
                        height: 160
                    },
                    labels: Object.keys(eventosData),
                    colors: ['#FBBF24', '#3B82F6', '#10B981', '#6B7280'],
                    legend: {
                        position: 'bottom',
                        fontSize: '12px'
                    }
                };

                const chartEventos = new ApexCharts(document.querySelector("#chart-eventos"), optionsEventos);
                chartEventos.render();
            } else {
                document.querySelector("#chart-eventos").innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">No hay datos disponibles</div>';
            }

            // Gráfico de timeline de interacción
            if (timelineData.length > 0) {
                const optionsTimeline = {
                    series: [{
                        name: 'Eventos',
                        data: timelineData
                    }],
                    chart: {
                        height: 160,
                        type: 'area',
                        toolbar: {
                            show: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    xaxis: {
                        type: 'datetime'
                    },
                    tooltip: {
                        x: {
                            format: 'dd MMM yyyy HH:mm'
                        }
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.3
                        }
                    }
                };

                const chartTimeline = new ApexCharts(document.querySelector("#chart-timeline"), optionsTimeline);
                chartTimeline.render();
            } else {
                document.querySelector("#chart-timeline").innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">No hay datos disponibles</div>';
            }
        });
    </script>
    @endpush
</x-app-layout>
