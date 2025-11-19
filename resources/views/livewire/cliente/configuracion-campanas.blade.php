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
        <button
            wire:click="$set('showCrearCampana', true)"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Crear Nueva Campaña
        </button>
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
                            <label for="titulo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Título *</label>
                            <input wire:model="titulo" id="titulo" placeholder="Nombre de la campaña"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            @error('titulo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="descripcion" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Descripción</label>
                            <textarea wire:model="descripcion" id="descripcion" placeholder="Descripción opcional" rows="3"
                                      class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            @error('descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="tipo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Tipo *</label>
                            <select wire:model.live="tipo" id="tipo"
                                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="imagen">Imagen</option>
                                <option value="video">Video</option>
                            </select>
                            @error('tipo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="archivo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Archivo *
                                @if($tipo === 'imagen')
                                    (JPG, PNG, GIF - máx 50MB)
                                @else
                                    (MP4, MOV, AVI - máx 50MB)
                                @endif
                            </label>
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
                            <label for="enlace" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Enlace (opcional)</label>
                            <input wire:model="enlace" id="enlace" type="url" placeholder="https://ejemplo.com"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            @error('enlace') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="prioridad" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Prioridad (1-100)</label>
                            <input wire:model="prioridad" id="prioridad" type="number" min="1" max="100"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            <p class="text-xs text-zinc-500 mt-1">1 = mayor prioridad, 100 = menor prioridad</p>
                            @error('prioridad') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="activo" id="activo"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-zinc-300 rounded" />
                            <label for="activo" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Campaña activa</label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" wire:click="resetForm"
                                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                <div wire:loading wire:target="crearCampana">
                                    <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                        <path fill="currentColor" d="m100 50c0 27.614-22.386 50-50 50s-50-22.386-50-50 22.386-50 50-50 50 22.386 50 50zm-90.856 0c0 22.091 17.909 40 40 40s40-17.909 40-40-17.909-40-40-40-40 17.909-40 40z" class="opacity-75"></path>
                                    </svg>
                                </div>
                                <span wire:loading.remove wire:target="crearCampana">
                                    Crear Campaña
                                </span>
                            </button>
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $campana->tipo === 'video' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                    {{ ucfirst($campana->tipo) }}
                                </span>
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
                                <button
                                    wire:click="toggleActivoCampana({{ $campana->id }})"
                                    class="flex-1 px-3 py-1 text-sm font-medium rounded transition-colors
                                        {{ $campana->activo ? 'bg-green-600 hover:bg-green-700 text-white' : 'border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white' }}"
                                >
                                    {{ $campana->activo ? 'Activa' : 'Inactiva' }}
                                </button>

                                <button
                                    wire:click="desasociarCampana({{ $campana->id }})"
                                    wire:confirm="¿Seguro que quieres desasociar esta campaña de la zona?"
                                    class="px-3 py-1 text-sm font-medium border border-red-300 bg-white hover:bg-red-50 text-red-700 rounded transition-colors"
                                >
                                    Quitar
                                </button>
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $campana->tipo === 'video' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                    {{ ucfirst($campana->tipo) }}
                                </span>
                            </div>

                            <!-- Acción -->
                            <button
                                wire:click="asociarCampana({{ $campana->id }})"
                                class="w-full px-3 py-1 text-sm font-medium border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-white rounded transition-colors"
                            >
                                + Asociar a esta zona
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
