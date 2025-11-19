<div class="space-y-6">
    <!-- Barra de búsqueda y filtros -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1 max-w-md">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar campañas..."
                icon="magnifying-glass"
            />
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="perPage" class="w-24">
                <flux:option value="5">5</flux:option>
                <flux:option value="10">10</flux:option>
                <flux:option value="25">25</flux:option>
                <flux:option value="50">50</flux:option>
            </flux:select>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">por página</span>
        </div>
    </div>

    <!-- Tabla de campañas -->
    <div class="overflow-hidden border border-zinc-200 rounded-lg dark:border-zinc-700">
        <flux:table class="w-full">
            <flux:columns>
                <flux:column
                    wire:click="sortBy('titulo')"
                    class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800"
                >
                    <div class="flex items-center gap-2">
                        <span>Título</span>
                        @if($sortBy === 'titulo')
                            <flux:icon.chevron-up class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" />
                        @endif
                    </div>
                </flux:column>

                <flux:column
                    wire:click="sortBy('tipo')"
                    class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800"
                >
                    <div class="flex items-center gap-2">
                        <span>Tipo</span>
                        @if($sortBy === 'tipo')
                            <flux:icon.chevron-up class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" />
                        @endif
                    </div>
                </flux:column>

                <flux:column>Descripción</flux:column>

                <flux:column
                    wire:click="sortBy('activo')"
                    class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800"
                >
                    <div class="flex items-center gap-2">
                        <span>Estado</span>
                        @if($sortBy === 'activo')
                            <flux:icon.chevron-up class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" />
                        @endif
                    </div>
                </flux:column>

                <flux:column>Zonas</flux:column>

                <flux:column
                    wire:click="sortBy('created_at')"
                    class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800"
                >
                    <div class="flex items-center gap-2">
                        <span>Creada</span>
                        @if($sortBy === 'created_at')
                            <flux:icon.chevron-up class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" />
                        @endif
                    </div>
                </flux:column>

                <flux:column>Acciones</flux:column>
            </flux:columns>

            <flux:rows>
                @forelse($campanas as $campana)
                    <flux:row wire:key="campana-{{ $campana->id }}">
                        <flux:cell>
                            <div class="flex items-center gap-3">
                                @if($campana->archivo_path)
                                    @if($campana->tipo === 'video')
                                        <div class="w-10 h-10 rounded bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                            <flux:icon.play class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                    @else
                                        <img src="{{ \Storage::url($campana->archivo_path) }}"
                                             alt="{{ $campana->titulo }}"
                                             class="w-10 h-10 rounded object-cover">
                                    @endif
                                @else
                                    <div class="w-10 h-10 rounded bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                        <flux:icon.photo class="w-5 h-5 text-zinc-400" />
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
                        </flux:cell>

                        <flux:cell>
                            <flux:badge
                                :color="$campana->tipo === 'video' ? 'blue' : 'green'"
                                size="sm"
                            >
                                {{ ucfirst($campana->tipo) }}
                            </flux:badge>
                        </flux:cell>

                        <flux:cell>
                            <div class="max-w-xs text-sm text-zinc-600 dark:text-zinc-400 truncate">
                                {{ $campana->descripcion ?: 'Sin descripción' }}
                            </div>
                        </flux:cell>

                        <flux:cell>
                            <flux:button
                                wire:click="toggleActivo({{ $campana->id }})"
                                :variant="$campana->activo ? 'filled' : 'outline'"
                                :color="$campana->activo ? 'green' : 'zinc'"
                                size="sm"
                            >
                                {{ $campana->activo ? 'Activa' : 'Inactiva' }}
                            </flux:button>
                        </flux:cell>

                        <flux:cell>
                            <div class="text-sm">
                                @if($campana->zonas->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($campana->zonas->take(3) as $zona)
                                            <flux:badge variant="outline" size="sm">
                                                {{ $zona->nombre }}
                                            </flux:badge>
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
                        </flux:cell>

                        <flux:cell>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $campana->created_at->format('d/m/Y') }}
                            </div>
                        </flux:cell>

                        <flux:cell>
                            <div class="flex items-center gap-2">
                                @if($campana->archivo_path)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        onclick="window.open('{{ \Storage::url($campana->archivo_path) }}', '_blank')"
                                    >
                                        Ver archivo
                                    </flux:button>
                                @endif

                                @if($campana->enlace)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        onclick="window.open('{{ $campana->enlace }}', '_blank')"
                                    >
                                        Abrir enlace
                                    </flux:button>
                                @endif
                            </div>
                        </flux:cell>
                    </flux:row>
                @empty
                    <flux:row>
                        <flux:cell colspan="7">
                            <div class="text-center py-12">
                                <flux:icon.presentation-chart-bar class="mx-auto h-12 w-12 text-zinc-400" />
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
                            </div>
                        </flux:cell>
                    </flux:row>
                @endforelse
            </flux:rows>
        </flux:table>
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
