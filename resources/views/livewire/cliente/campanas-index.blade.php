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
                        <option value="imagen">Imágenes</option>
                        <option value="video">Videos</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <label class="inline-flex items-center">
                        <input type="checkbox" wire:model="filtroActivo" class="form-checkbox" value="1">
                        <span class="ml-2">Solo activas</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Tabla de Campañas -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vista previa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Campaña</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Zonas asignadas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($campanas as $campana)
                        <tr>
                            <!-- Vista previa -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if ($campana->archivo_path)
                                        @if ($campana->tipo === 'imagen')
                                            <img class="h-12 w-12 rounded-lg object-cover" src="{{ Storage::url($campana->archivo_path) }}" alt="{{ $campana->titulo }}">
                                        @else
                                            <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    @else
                                        <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Información de la campaña -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $campana->titulo }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($campana->descripcion, 50) }}</div>
                                @if ($campana->enlace)
                                    <div class="text-xs text-blue-600 dark:text-blue-400 truncate max-w-xs">
                                        <a href="{{ $campana->enlace }}" target="_blank" class="hover:underline">{{ $campana->enlace }}</a>
                                    </div>
                                @endif
                                <div class="text-xs text-gray-400">Prioridad: {{ $campana->prioridad }}</div>
                            </td>

                            <!-- Tipo -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $campana->tipo === 'imagen' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($campana->tipo) }}
                                </span>
                            </td>

                            <!-- Estado -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleActivo({{ $campana->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $campana->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $campana->activo ? 'Activa' : 'Inactiva' }}
                                </button>
                            </td>

                            <!-- Zonas asignadas -->
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

                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $campana->created_at->format('d/m/Y H:i') }}
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
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
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron campañas.
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
    <!-- Barra de búsqueda y filtros -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1 max-w-md">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
                    </svg>
                </div>
                <input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar campañas..."
                    class="w-full pl-10 pr-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="perPage" class="w-24 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">por página</span>
        </div>
    </div>

    <!-- Tabla de campañas -->
    <div class="overflow-hidden border border-zinc-200 rounded-lg dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th wire:click="sortBy('titulo')"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Título</span>
                                @if($sortBy === 'titulo')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('tipo')"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Tipo</span>
                                @if($sortBy === 'tipo')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Descripción</th>
                        <th wire:click="sortBy('activo')"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Estado</span>
                                @if($sortBy === 'activo')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Zonas</th>
                        <th wire:click="sortBy('created_at')"
                            class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700">
                            <div class="flex items-center gap-2">
                                <span>Creada</span>
                                @if($sortBy === 'created_at')
                                    <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($campanas as $campana)
                        <tr wire:key="campana-{{ $campana->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if($campana->archivo_path)
                                        @if($campana->tipo === 'video')
                                            <div class="w-10 h-10 rounded bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M9 10V9a2 2 0 012-2h2a2 2 0 012 2v1M9 10v5a2 2 0 002 2h2a2 2 0 002-2v-5" />
                                                </svg>
                                            </div>
                                        @else
                                            <img src="{{ \Storage::url($campana->archivo_path) }}"
                                                 alt="{{ $campana->titulo }}"
                                                 class="w-10 h-10 rounded object-cover">
                                        @endif
                                    @else
                                        <div class="w-10 h-10 rounded bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white">
                                            {{ $campana->titulo }}
                                        </div>
                                        @if($campana->enlace)
                                            <div class="text-xs text-blue-600 dark:text-blue-400 truncate max-w-xs">
                                                {{ $campana->enlace }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $campana->tipo === 'video' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                    {{ ucfirst($campana->tipo) }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="max-w-xs text-sm text-zinc-600 dark:text-zinc-400 truncate">
                                    {{ $campana->descripcion ?: 'Sin descripción' }}
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    wire:click="toggleActivo({{ $campana->id }})"
                                    class="px-3 py-1 text-sm font-medium rounded transition-colors
                                        {{ $campana->activo ? 'bg-green-600 hover:bg-green-700 text-white' : 'border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white' }}"
                                >
                                    {{ $campana->activo ? 'Activa' : 'Inactiva' }}
                                </button>
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    @if($campana->zonas->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($campana->zonas->take(3) as $zona)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                                                    {{ $zona->nombre }}
                                                </span>
                                            @endforeach
                                            @if($campana->zonas->count() > 3)
                                                <span class="text-xs text-zinc-500">
                                                    +{{ $campana->zonas->count() - 3 }} más
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-zinc-400">Sin zonas</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $campana->created_at->format('d/m/Y') }}
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($campana->archivo_path)
                                        <button
                                            onclick="window.open('{{ \Storage::url($campana->archivo_path) }}', '_blank')"
                                            class="px-2 py-1 text-xs bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 rounded transition-colors"
                                        >
                                            Ver archivo
                                        </button>
                                    @endif

                                    @if($campana->enlace)
                                        <button
                                            onclick="window.open('{{ $campana->enlace }}', '_blank')"
                                            class="px-2 py-1 text-xs bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 rounded transition-colors"
                                        >
                                            Abrir enlace
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">
                                    No tienes campañas
                                </h3>
                                <p class="mt-1 text-sm text-zinc-500">
                                    @if($search)
                                        No se encontraron campañas que coincidan con "{{ $search }}"
                                    @else
                                        Las campañas se asocian automáticamente a tus zonas WiFi.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($campanas->hasPages())
        <div class="flex justify-center">
            {{ $campanas->links() }}
        </div>
    @endif

    <!-- Información adicional -->
    <div class="text-sm text-zinc-600 dark:text-zinc-400 text-center">
        Mostrando {{ $campanas->firstItem() }} - {{ $campanas->lastItem() }} de {{ $campanas->total() }} campañas
    </div>
</div>
