<div class="space-y-6">
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
