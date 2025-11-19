<div class="space-y-6">
    <!-- Información de la zona -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <flux:icon.signal class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $zona->nombre }}</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $zona->descripcion ?: 'Sin descripción' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Botón crear nueva campaña -->
    <div class="flex justify-end">
        <flux:button
            wire:click="$set('showCrearCampana', true)"
            icon="plus"
        >
            Crear Nueva Campaña
        </flux:button>
    </div>

    <!-- Modal crear campaña -->
    @if($showCrearCampana)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:ignore.self>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="resetForm"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-zinc-800 rounded-lg shadow-xl">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-4">
                        Crear Nueva Campaña
                    </h3>

                    <form wire:submit="crearCampana" class="space-y-4">
                        <div>
                            <flux:label for="titulo">Título *</flux:label>
                            <flux:input wire:model="titulo" id="titulo" placeholder="Nombre de la campaña" />
                            @error('titulo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <flux:label for="descripcion">Descripción</flux:label>
                            <flux:textarea wire:model="descripcion" id="descripcion" placeholder="Descripción opcional" rows="3" />
                            @error('descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <flux:label for="tipo">Tipo *</flux:label>
                            <flux:select wire:model.live="tipo" id="tipo">
                                <flux:option value="imagen">Imagen</flux:option>
                                <flux:option value="video">Video</flux:option>
                            </flux:select>
                            @error('tipo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <flux:label for="archivo">
                                Archivo *
                                @if($tipo === 'imagen')
                                    (JPG, PNG, GIF - máx 50MB)
                                @else
                                    (MP4, MOV, AVI - máx 50MB)
                                @endif
                            </flux:label>
                            <input type="file" wire:model="archivo" id="archivo"
                                   class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   accept="{{ $tipo === 'imagen' ? 'image/*' : 'video/*' }}">
                            @error('archivo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                            @if ($archivo)
                                <div class="mt-2 text-sm text-zinc-600">
                                    Archivo seleccionado: {{ $archivo->getClientOriginalName() }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <flux:label for="enlace">Enlace (opcional)</flux:label>
                            <flux:input wire:model="enlace" id="enlace" type="url" placeholder="https://ejemplo.com" />
                            @error('enlace') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <flux:label for="prioridad">Prioridad (1-100)</flux:label>
                            <flux:input wire:model="prioridad" id="prioridad" type="number" min="1" max="100" />
                            <p class="text-xs text-zinc-500 mt-1">1 = mayor prioridad, 100 = menor prioridad</p>
                            @error('prioridad') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:checkbox wire:model="activo" id="activo" />
                            <flux:label for="activo">Campaña activa</flux:label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <flux:button type="button" wire:click="resetForm" variant="outline">
                                Cancelar
                            </flux:button>
                            <flux:button type="submit">
                                <div wire:loading wire:target="crearCampana">
                                    <flux:icon.arrow-path class="animate-spin w-4 h-4" />
                                </div>
                                <span wire:loading.remove wire:target="crearCampana">
                                    Crear Campaña
                                </span>
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Campañas asociadas a la zona -->
    <div class="space-y-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
            Campañas Asociadas ({{ $campanasAsociadas->count() }})
        </h3>

        @if($campanasAsociadas->count() > 0)
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($campanasAsociadas as $campana)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <!-- Imagen/Video preview -->
                        <div class="h-32 bg-zinc-100 dark:bg-zinc-700 relative">
                            @if($campana->archivo_path)
                                @if($campana->tipo === 'video')
                                    <video src="{{ Storage::url($campana->archivo_path) }}" class="w-full h-full object-cover">
                                    </video>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="bg-black bg-opacity-50 rounded-full p-3">
                                            <flux:icon.play class="w-6 h-6 text-white" />
                                        </div>
                                    </div>
                                @else
                                    <img src="{{ Storage::url($campana->archivo_path) }}" alt="{{ $campana->titulo }}" class="w-full h-full object-cover">
                                @endif
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <flux:icon.photo class="w-8 h-8 text-zinc-400" />
                                </div>
                            @endif
                        </div>

                        <!-- Contenido -->
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white">{{ $campana->titulo }}</h4>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $campana->descripcion ?: 'Sin descripción' }}</p>
                                </div>
                                <flux:badge :color="$campana->tipo === 'video' ? 'blue' : 'green'" size="sm">
                                    {{ ucfirst($campana->tipo) }}
                                </flux:badge>
                            </div>

                            @if($campana->enlace)
                                <p class="text-xs text-blue-600 dark:text-blue-400 truncate mb-3">
                                    {{ $campana->enlace }}
                                </p>
                            @endif

                            <div class="flex items-center justify-between text-sm text-zinc-500 mb-3">
                                <span>Prioridad: {{ $campana->prioridad }}</span>
                                <span>{{ $campana->created_at->format('d/m/Y') }}</span>
                            </div>

                            <!-- Acciones -->
                            <div class="flex gap-2">
                                <flux:button
                                    wire:click="toggleActivoCampana({{ $campana->id }})"
                                    :variant="$campana->activo ? 'filled' : 'outline'"
                                    :color="$campana->activo ? 'green' : 'zinc'"
                                    size="sm"
                                    class="flex-1"
                                >
                                    {{ $campana->activo ? 'Activa' : 'Inactiva' }}
                                </flux:button>

                                <flux:button
                                    wire:click="desasociarCampana({{ $campana->id }})"
                                    variant="outline"
                                    color="red"
                                    size="sm"
                                    wire:confirm="¿Seguro que quieres desasociar esta campaña de la zona?"
                                >
                                    Quitar
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <flux:icon.presentation-chart-bar class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">No hay campañas asociadas</h3>
                <p class="mt-1 text-sm text-zinc-500">Asocia campañas existentes o crea una nueva.</p>
            </div>
        @endif
    </div>

    <!-- Campañas disponibles para asociar -->
    @if($campanasDisponibles->count() > 0)
        <div class="space-y-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                Campañas Disponibles ({{ $campanasDisponibles->count() }})
            </h3>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($campanasDisponibles as $campana)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden opacity-75 hover:opacity-100 transition-opacity">
                        <!-- Imagen/Video preview -->
                        <div class="h-24 bg-zinc-100 dark:bg-zinc-700 relative">
                            @if($campana->archivo_path)
                                @if($campana->tipo === 'video')
                                    <video src="{{ Storage::url($campana->archivo_path) }}" class="w-full h-full object-cover">
                                    </video>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="bg-black bg-opacity-50 rounded-full p-2">
                                            <flux:icon.play class="w-4 h-4 text-white" />
                                        </div>
                                    </div>
                                @else
                                    <img src="{{ Storage::url($campana->archivo_path) }}" alt="{{ $campana->titulo }}" class="w-full h-full object-cover">
                                @endif
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <flux:icon.photo class="w-6 h-6 text-zinc-400" />
                                </div>
                            @endif
                        </div>

                        <!-- Contenido -->
                        <div class="p-3">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="font-medium text-sm text-zinc-900 dark:text-white">{{ $campana->titulo }}</h4>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ $campana->descripcion ?: 'Sin descripción' }}</p>
                                </div>
                                <flux:badge :color="$campana->tipo === 'video' ? 'blue' : 'green'" size="sm">
                                    {{ ucfirst($campana->tipo) }}
                                </flux:badge>
                            </div>

                            <!-- Acción -->
                            <flux:button
                                wire:click="asociarCampana({{ $campana->id }})"
                                variant="outline"
                                size="sm"
                                class="w-full"
                            >
                                + Asociar a esta zona
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
