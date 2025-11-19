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
                        <input type="checkbox" wire:model="filtroActivo" class="form-checkbox h-5 w-5 text-indigo-600">
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
                        <th wire:click="sortBy('titulo')" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Campaña</span>
                                @if($sortBy === 'titulo')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('tipo')" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Tipo</span>
                                @if($sortBy === 'tipo')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Zonas asignadas</th>
                        <th wire:click="sortBy('prioridad')" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Prioridad</span>
                                @if($sortBy === 'prioridad')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('activo')" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Estado</span>
                                @if($sortBy === 'activo')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($campanas as $campana)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        @if ($campana->tipo === 'imagen' && $campana->archivo_path)
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campana->tipo === 'imagen' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' }}">
                                    {{ $campana->tipo }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($campana->zonas->count() > 0)
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ $campana->zonas->count() }} zona(s)
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $campana->zonas->pluck('nombre')->take(2)->join(', ') }}
                                        @if ($campana->zonas->count() > 2)
                                            y {{ $campana->zonas->count() - 2 }} más
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">Sin zonas</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $campana->prioridad ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleActivo({{ $campana->id }})" class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 {{ $campana->activo ? 'bg-green-500' : 'bg-gray-200' }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <span class="sr-only">Toggle estado</span>
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $campana->activo ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    @if ($campana->archivo_path)
                                        <a href="{{ Storage::url($campana->archivo_path) }}" target="_blank" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                            Ver archivo
                                        </a>
                                    @endif
                                    @if ($campana->enlace)
                                        <a href="{{ $campana->enlace }}" target="_blank" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                            Ir a enlace
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                @if($search)
                                    No se encontraron campañas que coincidan con "{{ $search }}"
                                @else
                                    No tienes campañas asociadas a tus zonas.
                                @endif
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
</div>
