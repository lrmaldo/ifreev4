@php
    $editando = (bool) $campana;
    $input = 'block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100';
    $tarjetaOpcion = 'relative flex cursor-pointer flex-col gap-1 rounded-lg border border-zinc-300 bg-white p-4 text-sm transition hover:border-indigo-400 has-[:checked]:border-indigo-600 has-[:checked]:ring-2 has-[:checked]:ring-indigo-600/20 dark:border-zinc-600 dark:bg-zinc-900';
    $seccion = 'grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-xs lg:grid-cols-3 dark:border-zinc-700 dark:bg-zinc-800';
    $dias = ['1' => 'Lun', '2' => 'Mar', '3' => 'Mié', '4' => 'Jue', '5' => 'Vie', '6' => 'Sáb', '0' => 'Dom'];
    $pesoMb = $archivo ? round($archivo->getSize() / 1048576, 1) : null;
@endphp

<div class="mx-auto w-full max-w-5xl pb-28">
    {{-- Encabezado --}}
    <div class="mb-8">
        <a href="{{ route('admin.campanas.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-indigo-600 dark:text-zinc-400">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/></svg>
            Campañas
        </a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $editando ? ($campana->titulo ?: 'Campaña sin título') : 'Nueva campaña' }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">La imagen o el video que se muestra en el portal antes de conectar a internet.</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- 1. Contenido --}}
        <section class="{{ $seccion }}">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Contenido</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Imagen de hasta 2 MB o video. Para eventos con mucha gente, usa videos ligeros: cada persona lo descarga antes de conectarse.</p>
            </div>
            <div class="space-y-5 lg:col-span-2">
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Tipo</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="{{ $tarjetaOpcion }}">
                            <input type="radio" wire:model.live="tipo" value="imagen" class="sr-only">
                            <span class="font-medium text-zinc-900 dark:text-white">Imagen</span>
                            <span class="text-zinc-500 dark:text-zinc-400">Se muestra en carrusel con las demás imágenes de la zona.</span>
                        </label>
                        <label class="{{ $tarjetaOpcion }}">
                            <input type="radio" wire:model.live="tipo" value="video" class="sr-only">
                            <span class="font-medium text-zinc-900 dark:text-white">Video</span>
                            <span class="text-zinc-500 dark:text-zinc-400">Se reproduce completo antes de dar acceso.</span>
                        </label>
                    </div>
                    @if ($editando && $campana->tipo !== $tipo)
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-400">Cambiaste el tipo: sube un archivo nuevo de {{ $tipo }}.</p>
                    @endif
                </fieldset>

                {{-- Archivo --}}
                <div>
                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $tipo === 'imagen' ? 'Imagen' : 'Video' }}</span>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div class="flex aspect-video w-full shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 sm:w-64 dark:border-zinc-700 dark:bg-zinc-900">
                            @if ($archivo && $archivo->isPreviewable())
                                @if ($tipo === 'imagen')
                                    <img src="{{ $archivo->temporaryUrl() }}" alt="Vista previa" class="h-full w-full object-contain">
                                @else
                                    <video src="{{ $archivo->temporaryUrl() }}" class="h-full w-full object-contain" controls muted></video>
                                @endif
                            @elseif ($archivo)
                                <span class="px-4 text-center text-sm text-zinc-500">{{ $archivo->getClientOriginalName() }}<br>(sin vista previa)</span>
                            @elseif ($editando && $campana->archivo_path && $campana->tipo === $tipo)
                                @if ($tipo === 'imagen')
                                    <img src="{{ Storage::url($campana->archivo_path) }}" alt="Imagen actual" class="h-full w-full object-contain">
                                @else
                                    <video src="{{ Storage::url($campana->archivo_path) }}" class="h-full w-full object-contain" controls muted preload="metadata"></video>
                                @endif
                            @else
                                <svg class="h-10 w-10 text-zinc-300 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 space-y-2">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
                                {{ ($archivo || $editando) ? 'Reemplazar archivo' : 'Elegir archivo' }}
                                <input type="file" wire:model="archivo" class="sr-only" accept="{{ $tipo === 'imagen' ? 'image/jpeg,image/png,image/webp,image/gif' : 'video/mp4,video/webm,video/quicktime,video/ogg' }}">
                            </label>
                            <div wire:loading wire:target="archivo" class="text-sm text-indigo-600">Subiendo archivo…</div>
                            @if ($archivo)
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $archivo->getClientOriginalName() }} · {{ $pesoMb }} MB
                                    <button type="button" wire:click="quitarArchivoNuevo" class="ml-2 border-0 bg-transparent p-0 text-sm font-medium text-red-600 shadow-none hover:text-red-500">Quitar</button>
                                </p>
                                @if ($tipo === 'video' && $pesoMb > 5)
                                    <p class="text-sm text-amber-700 dark:text-amber-400">Este video pesa {{ $pesoMb }} MB. En un evento con 1,000 personas serían unos {{ number_format($pesoMb * 1000 / 1024, 1) }} GB por el enlace; conviene comprimirlo a 2–3 MB.</p>
                                @endif
                            @elseif ($editando && $campana->archivo_path)
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Archivo actual: <span class="break-all font-mono">{{ basename($campana->archivo_path) }}</span></p>
                            @endif
                            @error('archivo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="titulo" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Título <span class="font-normal text-zinc-400">(opcional, se muestra en el portal)</span></label>
                    <input id="titulo" type="text" wire:model="titulo" maxlength="255" class="{{ $input }}" placeholder="Ej. Konecta · Internet para tu negocio">
                    @error('titulo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="descripcion" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Descripción <span class="font-normal text-zinc-400">(interna)</span></label>
                        <textarea id="descripcion" rows="2" wire:model="descripcion" class="{{ $input }}" placeholder="Notas para el equipo"></textarea>
                    </div>
                    <div>
                        <label for="enlace" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Enlace <span class="font-normal text-zinc-400">(opcional)</span></label>
                        <input id="enlace" type="url" wire:model="enlace" class="{{ $input }}" placeholder="https://">
                        @error('enlace') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Programación --}}
        <section class="{{ $seccion }}">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Programación</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Cuándo se muestra. Con varias campañas en una zona, la prioridad decide cuál va primero (1 = la más alta) cuando la zona usa selección por prioridad.</p>
            </div>
            <div class="space-y-5 lg:col-span-2">
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" wire:model.live="visible" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span class="font-medium text-zinc-900 dark:text-white">Publicada</span><br><span class="text-zinc-500 dark:text-zinc-400">Desmárcala para pausarla sin borrarla.</span></span>
                </label>
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" wire:model.live="siempre_visible" class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span class="font-medium text-zinc-900 dark:text-white">Siempre visible</span><br><span class="text-zinc-500 dark:text-zinc-400">Ignora fechas y días: se muestra mientras esté publicada.</span></span>
                </label>

                <div @class(['grid gap-5 sm:grid-cols-2', 'opacity-50' => $siempre_visible])>
                    <div>
                        <label for="fecha_inicio" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Desde</label>
                        <input id="fecha_inicio" type="date" wire:model="fecha_inicio" class="{{ $input }}" @disabled($siempre_visible)>
                        @error('fecha_inicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="fecha_fin" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Hasta</label>
                        <input id="fecha_fin" type="date" wire:model="fecha_fin" class="{{ $input }}" @disabled($siempre_visible)>
                        @error('fecha_fin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @unless ($siempre_visible)
                    <fieldset>
                        <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Días de la semana <span class="font-normal text-zinc-400">(sin marcar = todos)</span></legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($dias as $valor => $nombre)
                                <label class="cursor-pointer select-none rounded-full border border-zinc-300 px-3 py-1.5 text-sm text-zinc-700 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-600 has-[:checked]:text-white dark:border-zinc-600 dark:text-zinc-200">
                                    <input type="checkbox" wire:model="dias_visibles" value="{{ $valor }}" class="sr-only">{{ $nombre }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endunless

                <div class="sm:max-w-xs">
                    <label for="prioridad" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Prioridad</label>
                    <input id="prioridad" type="number" min="1" max="100" wire:model="prioridad" class="{{ $input }}">
                    @error('prioridad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- 3. Dónde se muestra --}}
        <section class="{{ $seccion }}">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Dónde se muestra</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Las zonas en cuyo portal aparece. Una zona sin campañas asignadas usa como respaldo las del mismo cliente y las globales.</p>
            </div>
            <div class="space-y-5 lg:col-span-2">
                <div class="sm:max-w-md">
                    <label for="cliente_id" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">Cliente</label>
                    <select id="cliente_id" wire:model="cliente_id" class="{{ $input }}">
                        <option value="">Global (Sattlink / i-Free)</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Zonas <span class="font-normal text-zinc-500">({{ count($zonas_ids) }} de {{ $totalZonas }} seleccionadas)</span></span>
                        <div class="flex gap-3 text-sm">
                            <button type="button" wire:click="seleccionarZonas(true)" class="border-0 bg-transparent p-0 font-medium text-indigo-600 shadow-none hover:text-indigo-500">Todas</button>
                            <button type="button" wire:click="seleccionarZonas(false)" class="border-0 bg-transparent p-0 font-medium text-zinc-500 shadow-none hover:text-zinc-700">Ninguna</button>
                        </div>
                    </div>
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="border-b border-zinc-200 p-2 dark:border-zinc-700">
                            <input type="search" wire:model.live.debounce.250ms="buscarZona" placeholder="Buscar zona…" aria-label="Buscar zona" class="{{ $input }}">
                        </div>
                        <div class="max-h-72 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-700">
                            @forelse ($zonas as $zona)
                                <label wire:key="zona-{{ $zona->id }}" class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                                    <input type="checkbox" wire:model.live="zonas_ids" value="{{ $zona->id }}" class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="flex-1 text-zinc-800 dark:text-zinc-100">{{ $zona->nombre }}</span>
                                    <span class="font-mono text-xs text-zinc-400">{{ $zona->id_personalizado ?: $zona->id }}</span>
                                </label>
                            @empty
                                <p class="px-3 py-6 text-center text-sm text-zinc-500">Ninguna zona coincide con "{{ $buscarZona }}".</p>
                            @endforelse
                        </div>
                    </div>
                    @error('zonas_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Barra de acciones fija --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <p class="hidden text-sm text-zinc-500 sm:block dark:text-zinc-400">
                    @if ($errors->any())
                        <span class="text-red-600">Revisa los campos marcados en rojo.</span>
                    @else
                        {{ count($zonas_ids) ? 'Se mostrará en ' . count($zonas_ids) . ' ' . (count($zonas_ids) === 1 ? 'zona' : 'zonas') . '.' : 'Sin zonas asignadas.' }}
                    @endif
                </p>
                <div class="flex w-full justify-end gap-3 sm:w-auto">
                    <a href="{{ route('admin.campanas.index') }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cancelar</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border-0 bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-60" wire:loading.attr="disabled" wire:target="save,archivo">
                        <span wire:loading.remove wire:target="save">{{ $editando ? 'Guardar cambios' : 'Crear campaña' }}</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
