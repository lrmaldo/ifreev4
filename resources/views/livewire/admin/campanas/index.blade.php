<div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg dark:bg-zinc-800 dark:border dark:border-zinc-700">

        <!-- Mensajes Flash -->
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 dark:bg-green-900 dark:border-green-700 dark:text-green-300 px-4 py-3 rounded relative m-4" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        @endif

        @if (session()->has('warning'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-300 px-4 py-3 rounded relative m-4" role="alert">
                <span class="block sm:inline">{{ session('warning') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 dark:bg-red-900 dark:border-red-700 dark:text-red-300 px-4 py-3 rounded relative m-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Filtros -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 dark:bg-zinc-900 dark:border-zinc-700">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1">
                    <input type="text" wire:model.debounce.300ms="search" placeholder="Buscar por título o descripción..." class="block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <select wire:model="filtroTipo" class="block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos los tipos</option>
                        <option value="imagen">Solo imágenes</option>
                        <option value="video">Solo videos</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <label class="inline-flex items-center">
                        <input type="checkbox" wire:model="mostrarSoloActivas" class="form-checkbox h-5 w-5 text-indigo-600">
                        <span class="ml-2 text-sm text-gray-700">Solo activas</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Tabla de Campañas -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Campaña</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periodo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Prioridad</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($campanas as $campana)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if ($campana->tipo === 'imagen')
                                            <img class="h-10 w-10 rounded object-cover" src="{{ Storage::url($campana->archivo_path) }}" alt="{{ $campana->titulo }}">
                                        @else
                                            <div class="h-10 w-10 rounded bg-gray-100 dark:bg-zinc-700 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-gray-500 dark:text-gray-300" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                    <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $campana->titulo }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-300">{{ Str::limit($campana->descripcion, 50) }}</div>
                                        @if ($campana->enlace)
                                            <a href="{{ $campana->enlace }}" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">{{ Str::limit($campana->enlace, 30) }}</a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($campana->siempre_visible)
                                    <div class="text-sm text-gray-900 dark:text-gray-200">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Siempre visible
                                        </span>
                                    </div>
                                @else
                                    <div class="text-sm text-gray-900 dark:text-gray-200">
                                        {{ $campana->fecha_inicio->format('d/m/Y') }} - {{ $campana->fecha_fin->format('d/m/Y') }}
                                        @if($campana->dias_visibles)
                                            <div class="text-xs text-gray-500 mt-1">
                                                Solo en días específicos
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campana->tipo === 'imagen' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                    {{ $campana->tipo }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $campana->cliente ? $campana->cliente->razon_social : 'Global' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $campana->prioridad ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleVisibility({{ $campana->id }})" class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 {{ $campana->visible ? 'bg-green-500' : 'bg-gray-200' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <span class="sr-only">Toggle visibility</span>
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $campana->visible ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $campana->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                    <svg class="h-5 w-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $campana->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" onclick="return confirm('¿Está seguro de eliminar esta campaña?')">
                                    <svg class="h-5 w-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No hay campañas disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-4 py-3 bg-white dark:bg-zinc-800 border-t border-gray-200 dark:border-zinc-700 sm:px-6">
            {{ $campanas->links() }}
        </div>
    </div>

    <!-- Modal de creación/edición -->
    @if($showModal)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    {{ $editando ? 'Editar Campaña' : 'Nueva Campaña' }}
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <!-- Título -->
                                    <div>
                                        <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
                                        <input type="text" wire:model="titulo" id="titulo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        @error('titulo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <!-- Descripción -->
                                    <div>
                                        <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                                        <textarea wire:model="descripcion" id="descripcion" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                        @error('descripcion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <!-- Enlace -->
                                    <div>
                                        <label for="enlace" class="block text-sm font-medium text-gray-700">Enlace (opcional)</label>
                                        <input type="url" wire:model="enlace" id="enlace" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        @error('enlace') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <!-- Fechas -->
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-data x-show="!$wire.siempre_visible">
                                        <div>
                                            <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha de inicio</label>
                                            <input type="date" wire:model="fecha_inicio" id="fecha_inicio" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            @error('fecha_inicio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha de fin</label>
                                            <input type="date" wire:model="fecha_fin" id="fecha_fin" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            @error('fecha_fin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <!-- Tipo -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipo de campaña</label>
                                        <div class="mt-2 space-x-4">
                                            <label class="inline-flex items-center">
                                                <input type="radio" wire:model.live="tipo" value="imagen" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                                <span class="ml-2 text-sm text-gray-700">Imagen</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" wire:model.live="tipo" value="video" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                                <span class="ml-2 text-sm text-gray-700">Video</span>
                                            </label>
                                        </div>
                                        @error('tipo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Prioridad -->
                                    <div>
                                        <label for="prioridad" class="block text-sm font-medium text-gray-700">Prioridad</label>
                                        <div class="flex items-center">
                                            <input type="number" wire:model="prioridad" id="prioridad" min="1" max="100"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                                style="max-width: 100px;">
                                            <span class="ml-2 text-xs text-gray-500">Menor número = Mayor prioridad</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Cuando la selección es por prioridad, se muestra la campaña con el menor valor.</p>
                                        @error('prioridad') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Archivo -->
                                    <div>
                                        <label for="archivo-{{ $tipo }}" class="block text-sm font-medium text-gray-700">
                                            {{ $tipo === 'imagen' ? 'Imagen' : 'Video' }}
                                            @if($editando && $archivo_actual)
                                                <span class="text-xs text-gray-500">(Dejar en blanco para mantener el actual)</span>
                                            @endif
                                        </label>
                                        <!-- Usamos un ID único dinámico basado en el tipo -->
                                        <input type="file"
                                            wire:model="archivo"
                                            id="archivo-{{ $tipo }}"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            accept="{{ $tipo === 'imagen' ? 'image/*' : '.mp4,.mov,.ogg,.qt,.webm,.mpeg,.avi,video/*' }}">

                                        <div class="mt-2">
                                            @if ($archivo)
                                                @if($tipo === 'imagen')
                                                    <img src="{{ $archivo->temporaryUrl() }}" class="h-20 w-auto">
                                                @else
                                                    <span class="text-sm text-gray-500">Video seleccionado: {{ $archivo->getClientOriginalName() }} ({{ round($archivo->getSize() / 1048576, 2) }} MB)</span>
                                                @endif
                                            @elseif($editando && $archivo_actual)
                                                @if($tipo === 'imagen')
                                                    <img src="{{ Storage::url($archivo_actual) }}" class="h-20 w-auto">
                                                @else
                                                    <span class="text-sm text-gray-500">Video actual: {{ basename($archivo_actual) }}</span>
                                                @endif
                                            @endif
                                        </div>

                                        @if ($tipo === 'video')
                                            <div class="mt-2 text-xs text-gray-500">
                                                Formatos permitidos: MP4, MOV, OGG, WebM, MPEG, AVI (máx. 100MB)
                                            </div>
                                            @if ($errors->has('archivo'))
                                                <div class="mt-2 p-2 bg-red-100 text-red-700 text-xs rounded">
                                                    <strong>Problema con el archivo:</strong> {{ $errors->first('archivo') }}
                                                    <br>
                                                    Si el archivo es muy grande (más de 100MB), considera usar el
                                                    <a href="{{ url('/') }}/video-compressor.php" target="_blank" class="underline">compresor de videos</a>.
                                                </div>
                                            @endif
                                        @else
                                            @error('archivo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        @endif
                                    </div>
                                    <!-- Visibilidad -->
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input type="checkbox" wire:model="visible" id="visible" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            <label for="visible" class="ml-2 block text-sm text-gray-700">Visible</label>
                                        </div>

                                        <div class="flex items-center">
                                            <input type="checkbox" wire:model="siempre_visible" id="siempre_visible" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                            <label for="siempre_visible" class="ml-2 block text-sm text-gray-700">Siempre visible (ignorar fechas y días)</label>
                                        </div>

                                        <!-- Días visibles -->
                                        <div class="mt-3" x-data="{showDays: {{ !$siempre_visible ? 'true' : 'false' }}}" x-show="!$wire.siempre_visible">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Días visibles (opcional)</label>
                                            <div class="grid grid-cols-7 gap-2">
                                                <div class="flex flex-col items-center">
                                                    <label for="dia-0" class="block text-xs font-medium text-gray-700">Dom</label>
                                                    <input type="checkbox" wire:model="dias_visibles" value="0" id="dia-0" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                </div>
                                                <div class="flex flex-col items-center">
                                                    <label for="dia-1" class="block text-xs font-medium text-gray-700">Lun</label>
                                                    <input type="checkbox" wire:model="dias_visibles" value="1" id="dia-1" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                </div>
                                                <div class="flex flex-col items-center">
                                                    <label for="dia-2" class="block text-xs font-medium text-gray-700">Mar</label>
                                                    <input type="checkbox" wire:model="dias_visibles" value="2" id="dia-2" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                </div>
                                                <div class="flex flex-col items-center">
                                                    <label for="dia-3" class="block text-xs font-medium text-gray-700">Mié</label>
                                                    <input type="checkbox" wire:model="dias_visibles" value="3" id="dia-3" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                </div>
                                                <div class="flex flex-col items-center">
                                                    <label for="dia-4" class="block text-xs font-medium text-gray-700">Jue</label>
                                                    <input type="checkbox" wire:model="dias_visibles" value="4" id="dia-4" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                </div>
                                                <div class="flex flex-col items-center">
                                                    <label for="dia-5" class="block text-xs font-medium text-gray-700">Vie</label>
                                                    <input type="checkbox" wire:model="dias_visibles" value="5" id="dia-5" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                </div>
                                                <div class="flex flex-col items-center">
                                                    <label for="dia-6" class="block text-xs font-medium text-gray-700">Sáb</label>
                                                    <input type="checkbox" wire:model="dias_visibles" value="6" id="dia-6" class="mt-1 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Si no selecciona ningún día, se mostrará todos los días dentro del rango de fechas.</p>
                                        </div>
                                    </div>

                                    <!-- Cliente -->
                                    <div>
                                        <label for="cliente_id" class="block text-sm font-medium text-gray-700">Cliente (opcional)</label>
                                        <select wire:model="cliente_id" id="cliente_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Global (todos los clientes)</option>
                                            @foreach($clientes as $cliente)
                                                <option value="{{ $cliente->id }}">{{ $cliente->razon_social }}</option>
                                            @endforeach
                                        </select>
                                        @error('cliente_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Zonas -->
                                    <div>
                                        <label for="zonas_select" class="block text-sm font-medium text-gray-700">Zonas donde mostrar esta campaña</label>
                                        <div wire:ignore>
                                            <select id="zonas_select"
                                                    class="select2 mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                                                    multiple
                                                    wire:model.live.debounce.500ms="zonas_ids"
                                                    data-livewire-values="{{ json_encode($zonas_ids ?? []) }}">
                                                @foreach($zonas as $zona)
                                                    <option value="{{ $zona->id }}"
                                                        @if(in_array($zona->id, $zonas_ids ?? [])) selected @endif>
                                                        {{ $zona->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="mt-1 text-xs text-gray-500">
                                                <strong>Estado:</strong>
                                                @if(!empty($zonas_ids))
                                                    {{ count($zonas_ids) }} zonas seleccionadas
                                                    <span class="text-green-600">({{ collect($zonas)->whereIn('id', $zonas_ids)->pluck('nombre')->join(', ') }})</span>
                                                @else
                                                    <span class="text-gray-600">Ninguna zona seleccionada</span>
                                                @endif
                                            </small>
                                        </div>
                                        @error('zonas_ids') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="save" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Guardar
                        </button>
                        <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<!-- jQuery y Select2 desde CDN (temporal hasta resolver Vite) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Asegurarse que jQuery está disponible antes de cargar Select2 -->
<script>
    if (typeof jQuery === 'undefined') {
        console.error('jQuery no está disponible! Cargando jQuery de respaldo...');
        document.write('<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"><\/script>');
    }

    // Script para manejar el cambio de tipo de archivo
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('tipo-changed', function(data) {
            // Limpiamos el input file para que refleje el nuevo tipo
            const tipoActual = data.tipo;
            setTimeout(() => {
                const inputFile = document.getElementById('archivo-'+tipoActual);
                if (inputFile) {
                    inputFile.value = '';
                }
            }, 100);
        });
    });
</script>

<!-- Select2 CSS y JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Verificar que Select2 esté disponible -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined') {
            console.log('✅ jQuery está disponible: ' + jQuery.fn.jquery);

            if (typeof jQuery.fn.select2 !== 'undefined') {
                console.log('✅ Select2 está disponible');
            } else {
                console.error('❌ Select2 no está disponible! Cargando Select2 de respaldo...');
                var select2Script = document.createElement('script');
                select2Script.src = 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js';
                document.head.appendChild(select2Script);
            }
        }
    });

    // Implementación simplificada usando hooks de Livewire 3
    document.addEventListener("livewire:init", function() {
        console.log('🚀 Inicializando integración Select2 con Livewire 3');

        function initializeZonasSelect2() {
            const element = $("#zonas_select");

            if (!element.length) {
                console.log('❌ Elemento #zonas_select no encontrado');
                return;
            }

            // Destruir Select2 existente si ya está inicializado
            if (element.hasClass('select2-hidden-accessible')) {
                console.log('🔄 Destruyendo Select2 existente');
                element.select2('destroy');
            }

            console.log('✅ Inicializando Select2');
            element.select2({
                placeholder: "Seleccione zonas...",
                allowClear: true,
                width: '100%'
            }).on("change", function() {
                const values = $(this).val() || [];
                console.log('📤 Select2 cambió, enviando a Livewire:', values);

                // Usar la instancia global de Livewire para actualizar el componente
                if (window.Livewire) {
                    // Encontrar el componente Livewire más cercano
                    const livewireEl = this.closest('[wire\\:id]');
                    if (livewireEl) {
                        const wireId = livewireEl.getAttribute('wire:id');
                        const component = window.Livewire.find(wireId);
                        if (component) {
                            component.set('zonas_ids', values);
                        }
                    }
                } else {
                    // Fallback usando $wire si está disponible
                    if (typeof $wire !== 'undefined') {
                        $wire.set('zonas_ids', values);
                    }
                }
            });

            // Aplicar valores iniciales desde el atributo data-livewire-values
            const initialValues = element.attr('data-livewire-values');
            if (initialValues) {
                try {
                    const values = JSON.parse(initialValues);
                    if (Array.isArray(values) && values.length > 0) {
                        console.log('📝 Aplicando valores iniciales:', values);
                        element.val(values).trigger('change.select2');
                    }
                } catch (e) {
                    console.error('❌ Error al parsear valores iniciales:', e);
                }
            }
        }

        // Inicializar cuando se carga la página
        initializeZonasSelect2();

        // Re-inicializar después de cada actualización de Livewire
        Livewire.hook("morph", () => {
            console.log('🔄 Hook morph - Reinicializando Select2');
            setTimeout(() => {
                initializeZonasSelect2();
            }, 100);
        });

        // También escuchar el evento específico de edición de campaña
        Livewire.on('campanEditLoaded', (data) => {
            console.log('🎯 Evento campanEditLoaded - Configurando para edición:', data);

            setTimeout(() => {
                initializeZonasSelect2();

                // Aplicar valores específicos de la campaña
                if (data && data.zonasIds && data.zonasIds.length > 0) {
                    const element = $("#zonas_select");
                    if (element.length) {
                        console.log('📝 Aplicando zonas de la campaña:', data.zonasIds);
                        element.val(data.zonasIds).trigger('change.select2');
                    }
                }
            }, 200);
        });
    });
</script>
@endpush


