<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Respuestas del Formulario</h2>
                <p class="text-gray-600 mt-1">Zona: <span class="font-semibold text-primary-600">{{ $zona->nombre }}</span></p>
            </div>
            <div class="flex items-center space-x-2">
                <flux:badge variant="outline" color="gray">
                    Total: {{ $respuestas->total() }}
                </flux:badge>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Búsqueda por MAC -->
            <div>
                <flux:field>
                    <flux:label>Buscar por MAC</flux:label>
                    <flux:input
                        wire:model.live.debounce.500ms="searchMac"
                        placeholder="xx:xx:xx:xx:xx:xx"
                        class="w-full"
                    />
                </flux:field>
            </div>

            <!-- Fecha inicio -->
            <div>
                <flux:field>
                    <flux:label>Fecha desde</flux:label>
                    <flux:input
                        type="date"
                        wire:model.live="fechaInicio"
                        class="w-full"
                    />
                </flux:field>
            </div>

            <!-- Fecha fin -->
            <div>
                <flux:field>
                    <flux:label>Fecha hasta</flux:label>
                    <flux:input
                        type="date"
                        wire:model.live="fechaFin"
                        class="w-full"
                    />
                </flux:field>
            </div>

            <!-- Botón limpiar -->
            <div class="flex items-end">
                <flux:button
                    wire:click="limpiarFiltros"
                    variant="outline"
                    size="sm"
                    class="w-full"
                >
                    Limpiar filtros
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Tabla de respuestas -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($respuestas->count() > 0)
            <!-- Desktop view -->
            <div class="block lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                MAC Address
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Dispositivo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tiempo Activo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Respuestas
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($respuestas as $respuesta)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $respuesta->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-md font-mono">
                                        {{ $respuesta->mac_address }}
                                    </code>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate" title="{{ $respuesta->dispositivo }}">
                                    {{ $this->getDispositivoCorto($respuesta->dispositivo) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $this->formatearTiempo($respuesta->tiempo_activo) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($respuesta->formulario_completado)
                                        <flux:badge color="green" variant="soft">Completado</flux:badge>
                                    @else
                                        <flux:badge color="orange" variant="soft">Incompleto</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        class="text-primary-600 hover:text-primary-700 text-sm font-medium transition-colors duration-150 hover:underline"
                                        onclick="toggleRespuestas({{ $respuesta->id }})"
                                    >
                                        Ver respuestas
                                    </button>
                                </td>
                            </tr>
                            <!-- Fila expandible con respuestas -->
                            <tr id="respuestas-{{ $respuesta->id }}" class="hidden bg-gradient-to-r from-gray-50 to-blue-50">
                                <td colspan="6" class="px-6 py-6">
                                    <div class="space-y-4">
                                        <h4 class="font-semibold text-gray-900 text-base">Respuestas del formulario:</h4>
                                        @if($respuesta->respuestas_formateadas)
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                @foreach($respuesta->respuestas_formateadas as $campo)
                                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                                        <dt class="text-sm font-medium text-gray-600">{{ $campo['etiqueta'] }}</dt>
                                                        <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $campo['valor'] ?: 'Sin respuesta' }}</dd>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-gray-500 text-sm italic">No hay respuestas registradas</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile view -->
            <div class="hidden">
                @foreach($respuestas as $respuesta)
                    <div class="border-b border-gray-200 p-4 hover:bg-gray-50 transition-colors duration-150">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-3">
                                    <span class="text-sm font-semibold text-gray-900">
                                        {{ $respuesta->created_at->format('d/m/Y H:i') }}
                                    </span>
                                    @if($respuesta->formulario_completado)
                                        <flux:badge color="green" variant="soft" size="sm">Completado</flux:badge>
                                    @else
                                        <flux:badge color="orange" variant="soft" size="sm">Incompleto</flux:badge>
                                    @endif
                                </div>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-700 mr-2">MAC:</span>
                                        <code class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-md font-mono">{{ $respuesta->mac_address }}</code>
                                    </div>
                                    <div><span class="font-medium text-gray-700">Tiempo:</span> {{ $this->formatearTiempo($respuesta->tiempo_activo) }}</div>
                                    <div class="truncate" title="{{ $respuesta->dispositivo }}">
                                        <span class="font-medium text-gray-700">Dispositivo:</span> {{ $this->getDispositivoCorto($respuesta->dispositivo) }}
                                    </div>
                                </div>
                                <button
                                    class="mt-3 text-primary-600 hover:text-primary-700 text-sm font-medium transition-colors duration-150 hover:underline"
                                    onclick="toggleRespuestas({{ $respuesta->id }})"
                                >
                                    Ver respuestas
                                </button>
                            </div>
                        </div>

                        <!-- Respuestas expandibles -->
                        <div id="respuestas-{{ $respuesta->id }}" class="hidden mt-4 pt-4 border-t border-gray-200">
                            @if($respuesta->respuestas_formateadas)
                                <div class="space-y-3">
                                    @foreach($respuesta->respuestas_formateadas as $campo)
                                        <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                                            <dt class="text-sm font-medium text-gray-600">{{ $campo['etiqueta'] }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $campo['valor'] ?: 'Sin respuesta' }}</dd>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm italic">No hay respuestas registradas</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $respuestas->links() }}
            </div>
        @else
            <!-- Estado vacío -->
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay respuestas</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($searchMac || $fechaInicio || $fechaFin)
                        No se encontraron respuestas con los filtros aplicados.
                    @else
                        Aún no se han registrado respuestas para esta zona.
                    @endif
                </p>
                @if($searchMac || $fechaInicio || $fechaFin)
                    <div class="mt-6">
                        <flux:button wire:click="limpiarFiltros" variant="outline" size="sm">
                            Limpiar filtros
                        </flux:button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
function toggleRespuestas(id) {
    const elemento = document.getElementById('respuestas-' + id);
    const button = document.querySelector(`[onclick="toggleRespuestas(${id})"]`);

    if (elemento.classList.contains('hidden')) {
        elemento.classList.remove('hidden');
        elemento.style.animation = 'fadeIn 0.3s ease-in-out';
        if (button) button.textContent = 'Ocultar respuestas';
    } else {
        elemento.style.animation = 'fadeOut 0.3s ease-in-out';
        setTimeout(() => {
            elemento.classList.add('hidden');
        }, 250);
        if (button) button.textContent = 'Ver respuestas';
    }
}

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-10px); }
    }
`;
document.head.appendChild(style);
</script>
