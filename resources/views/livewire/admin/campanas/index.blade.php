@php
    $colorEstado = [
        'activa' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-300',
        'programada' => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
        'vencida' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
        'oculta' => 'bg-zinc-100 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-700 dark:text-zinc-300',
    ];
    $badge = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset';
    $control = 'w-auto rounded-lg border border-zinc-300 bg-white py-2 pl-3 pr-8 text-sm text-zinc-700 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200';
@endphp

<div class="w-full">
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Campañas</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Imágenes y videos del portal cautivo · {{ $campanas->total() }} {{ $campanas->total() === 1 ? 'campaña' : 'campañas' }}{{ $hayFiltros ? ' con estos filtros' : '' }}</p>
        </div>
        <a href="{{ route('admin.campanas.crear') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
            Nueva campaña
        </a>
    </div>

    {{-- Mensajes --}}
    @if (session()->has('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="status">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">{{ session('error') }}</div>
    @endif

    {{-- Filtros --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <label class="flex w-full max-w-sm items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 shadow-xs focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800">
            <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
            <span class="sr-only">Buscar campañas</span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por título o descripción…" class="h-9 w-full rounded-none border-0 bg-transparent px-0 py-2 text-sm text-zinc-900 placeholder-zinc-400 shadow-none focus:outline-none focus:ring-0 dark:text-zinc-100">
        </label>
        <select wire:model.live="filtroEstado" aria-label="Estado" class="{{ $control }}">
            <option value="">Todos los estados</option>
            <option value="activas">Activas</option>
            <option value="programadas">Programadas</option>
            <option value="vencidas">Vencidas</option>
            <option value="ocultas">Ocultas</option>
        </select>
        <select wire:model.live="filtroTipo" aria-label="Tipo" class="{{ $control }}">
            <option value="">Imágenes y videos</option>
            <option value="imagen">Solo imágenes</option>
            <option value="video">Solo videos</option>
        </select>
        <select wire:model.live="filtroCliente" aria-label="Cliente" class="{{ $control }}">
            <option value="">Todos los clientes</option>
            <option value="global">Global (Sattlink / i-Free)</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
            @endforeach
        </select>
        @if ($hayFiltros)
            <button type="button" wire:click="limpiarFiltros" class="border-0 bg-transparent px-1 py-0 text-sm font-medium text-indigo-600 shadow-none hover:text-indigo-500">Quitar filtros</button>
        @endif
        <button type="button" wire:click="ejecutarDiagnostico" class="ml-auto border-0 bg-transparent px-1 py-0 text-sm text-zinc-500 shadow-none hover:text-zinc-700 dark:text-zinc-400" title="Revisa carpetas y permisos para subir archivos">
            <span wire:loading.remove wire:target="ejecutarDiagnostico">Diagnóstico de archivos</span>
            <span wire:loading wire:target="ejecutarDiagnostico">Revisando…</span>
        </button>
    </div>

    {{-- Cuadrícula de campañas --}}
    @if ($campanas->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 px-4 py-16 text-center dark:border-zinc-600">
            <p class="text-sm text-zinc-500">{{ $hayFiltros ? 'Ninguna campaña coincide con estos filtros.' : 'Aún no hay campañas.' }}</p>
            @unless ($hayFiltros)
                <a href="{{ route('admin.campanas.crear') }}" wire:navigate class="mt-3 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-500">Crear la primera campaña →</a>
            @endunless
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
            @foreach ($campanas as $campana)
                @php $estado = $campana->estado; @endphp
                <article wire:key="campana-{{ $campana->id }}" @class(['flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-800', 'opacity-70' => $estado['clave'] === 'oculta'])>
                    {{-- Miniatura --}}
                    <a href="{{ route('admin.campanas.editar', ['campanaId' => $campana->id]) }}" wire:navigate class="relative block aspect-video overflow-hidden bg-zinc-100 dark:bg-zinc-900">
                        @if ($campana->tipo === 'imagen')
                            <img src="{{ Storage::url($campana->archivo_path) }}" alt="{{ $campana->titulo ?: 'Campaña sin título' }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                        @else
                            <video src="{{ Storage::url($campana->archivo_path) }}#t=1" preload="metadata" muted playsinline class="absolute inset-0 h-full w-full object-cover"></video>
                            <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-black/55 text-white"><svg class="ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.344-5.891a1.5 1.5 0 000-2.538L6.3 2.841z"/></svg></span>
                            </span>
                        @endif
                        <span class="absolute left-2 top-2 rounded-md bg-black/60 px-2 py-0.5 text-xs font-medium text-white">{{ $campana->tipo === 'imagen' ? 'Imagen' : 'Video' }}</span>
                    </a>

                    {{-- Datos --}}
                    <div class="flex flex-1 flex-col gap-2 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('admin.campanas.editar', ['campanaId' => $campana->id]) }}" wire:navigate @class(['line-clamp-2 font-medium hover:text-indigo-600', 'text-zinc-900 dark:text-white' => $campana->titulo, 'italic text-zinc-400' => !$campana->titulo])>{{ $campana->titulo ?: 'Sin título' }}</a>
                            <span class="{{ $badge }} {{ $colorEstado[$estado['clave']] }} shrink-0">{{ $estado['etiqueta'] }}</span>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $estado['detalle'] }}</p>
                        <dl class="mt-auto grid grid-cols-3 gap-2 border-t border-zinc-100 pt-3 text-xs dark:border-zinc-700">
                            <div>
                                <dt class="text-zinc-400">Cliente</dt>
                                <dd class="truncate text-zinc-700 dark:text-zinc-200" title="{{ $campana->cliente?->nombre ?? 'Global' }}">{{ $campana->cliente?->nombre ?? 'Global' }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-400">Zonas</dt>
                                <dd @class(['text-zinc-700 dark:text-zinc-200' => $campana->zonas_count, 'font-medium text-amber-700 dark:text-amber-400' => !$campana->zonas_count])>{{ $campana->zonas_count ?: 'Ninguna' }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-400">Prioridad</dt>
                                <dd class="text-zinc-700 dark:text-zinc-200">{{ $campana->prioridad ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center justify-between gap-2 border-t border-zinc-100 px-4 py-3 dark:border-zinc-700">
                        <button type="button" wire:click="toggleVisibility({{ $campana->id }})" role="switch" aria-checked="{{ $campana->visible ? 'true' : 'false' }}" class="flex items-center gap-2 border-0 bg-transparent p-0 text-sm text-zinc-600 shadow-none dark:text-zinc-300">
                            <span @class(['relative inline-flex h-5 w-9 shrink-0 rounded-full transition', 'bg-green-500' => $campana->visible, 'bg-zinc-300 dark:bg-zinc-600' => !$campana->visible])>
                                <span @class(['absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition', 'left-[1.125rem]' => $campana->visible, 'left-0.5' => !$campana->visible])></span>
                            </span>
                            {{ $campana->visible ? 'Publicada' : 'Pausada' }}
                        </button>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.campanas.editar', ['campanaId' => $campana->id]) }}" wire:navigate class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Editar</a>
                            <flux:dropdown position="bottom" align="end">
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-300 bg-white p-0 text-zinc-600 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300" aria-label="Más acciones">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM8.5 10a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM15.5 8.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3z"/></svg>
                                </button>
                                <flux:menu>
                                    <flux:menu.item icon="arrow-top-right-on-square" :href="Storage::url($campana->archivo_path)" target="_blank">Ver archivo</flux:menu.item>
                                    @if ($campana->enlace)
                                        <flux:menu.item icon="link" :href="$campana->enlace" target="_blank">Abrir enlace</flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="confirmarEliminar({{ $campana->id }})">Eliminar</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div class="mt-6">{{ $campanas->links() }}</div>

    {{-- Confirmar eliminación --}}
    @if ($confirmandoEliminar)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-eliminar">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h3 id="titulo-eliminar" class="text-lg font-semibold text-zinc-900 dark:text-white">¿Eliminar esta campaña?</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Se borra también su archivo y deja de mostrarse en todas sus zonas. Si solo quieres detenerla un tiempo, mejor páusala.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('confirmandoEliminar', null)" class="rounded-lg border border-zinc-300 bg-white px-4 py-1.5 text-sm font-medium text-zinc-700 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cancelar</button>
                    <button type="button" wire:click="eliminar" class="rounded-lg border-0 bg-red-600 px-4 py-1.5 text-sm font-semibold text-white shadow-none hover:bg-red-500">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
