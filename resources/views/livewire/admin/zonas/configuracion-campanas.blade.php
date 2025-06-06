<div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Configuración de Campañas para: {{ $zona->nombre }}
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Configure cómo se mostrarán las campañas publicitarias en esta zona.
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.zonas.index') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:border-blue-300 focus:shadow-outline-blue active:bg-gray-100 transition ease-in-out duration-150">
                    <svg class="mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver a zonas
                </a>
            </div>
        </div>

        <!-- Mensajes flash -->
        @if (session()->has('message'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 mx-6 mt-4">
                <p>{{ session('message') }}</p>
            </div>
        @endif

        <div class="px-4 py-5 sm:px-6">
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <!-- Método de selección de campañas -->
                    <div class="sm:col-span-3">
                        <label for="seleccion_campanas" class="block text-sm font-medium text-gray-700">
                            Método de selección de campañas
                        </label>
                        <div class="mt-1">
                            <select
                                wire:model="seleccion_campanas"
                                id="seleccion_campanas"
                                class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                            >
                                <option value="aleatorio">Aleatorio</option>
                                <option value="prioridad">Por prioridad</option>
                            </select>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            @if ($seleccion_campanas === 'aleatorio')
                                Las campañas se seleccionarán aleatoriamente de entre todas las activas.
                            @else
                                Se mostrará la campaña activa con el número de prioridad más bajo (mayor prioridad).
                            @endif
                        </p>
                    </div>

                    <!-- Tiempo de visualización -->
                    <div class="sm:col-span-3">
                        <label for="tiempo_visualizacion" class="block text-sm font-medium text-gray-700">
                            Tiempo de visualización (segundos)
                        </label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input
                                type="number"
                                wire:model="tiempo_visualizacion"
                                id="tiempo_visualizacion"
                                min="5"
                                max="120"
                                class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border-gray-300"
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Tiempo en segundos que se mostrará el carrusel antes de permitir acceso.
                        </p>
                        @error('tiempo_visualizacion')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Información sobre el comportamiento -->
                    <div class="sm:col-span-6 bg-gray-50 p-4 rounded-md">
                        <h4 class="font-medium text-gray-900 mb-2">Información sobre la configuración</h4>

                        <div class="space-y-2 text-sm text-gray-700">
                            <p class="flex items-center">
                                <svg class="mr-2 h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <strong>Método Aleatorio:</strong> Cada vez que un usuario acceda a la zona, se mostrará una campaña aleatoria de entre las activas.
                            </p>
                            <p class="flex items-center">
                                <svg class="mr-2 h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <strong>Método por Prioridad:</strong> Se mostrará la campaña con el número de prioridad más bajo (mayor prioridad).
                            </p>
                            <p class="flex items-center">
                                <svg class="mr-2 h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <strong>Tiempo de visualización:</strong> Define cuántos segundos se mostrará el carrusel antes de permitir el acceso. Para videos, se usa el tiempo real del video.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 mt-6">
                    <button
                        type="submit"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        style="background-color: #ff5e2c; border-color: #ff5e2c;"
                    >
                        <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
