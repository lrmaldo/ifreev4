@php
    $colorRol = [
        'admin' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300',
        'cliente' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
        'tecnico' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
    ];
    $colorRolDefecto = 'bg-zinc-100 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-700 dark:text-zinc-300';
    $badge = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset';
    $chip = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-medium shadow-none transition';
    $chipActivo = 'border-indigo-600 bg-indigo-600 text-white';
    $chipInactivo = 'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200';
@endphp

<div class="w-full">
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Usuarios</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Quién entra al panel y qué puede hacer · {{ $total }} {{ $total === 1 ? 'usuario' : 'usuarios' }}</p>
        </div>
        <a href="{{ route('admin.users.crear') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
            Nuevo usuario
        </a>
    </div>

    {{-- Mensajes --}}
    @if (session()->has('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="status">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">{{ session('error') }}</div>
    @endif

    {{-- Filtros: búsqueda y rol --}}
    <div class="mb-5 space-y-3">
        <label class="flex w-full max-w-sm items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 shadow-xs focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800">
            <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
            <span class="sr-only">Buscar usuarios</span>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo…" class="h-9 w-full rounded-none border-0 bg-transparent px-0 py-2 text-sm text-zinc-900 placeholder-zinc-400 shadow-none focus:outline-none focus:ring-0 dark:text-zinc-100">
        </label>

        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filtrar por rol">
            <button type="button" wire:click="$set('filtroRol', '')" class="{{ $chip }} {{ $filtroRol === '' ? $chipActivo : $chipInactivo }}">
                Todos <span class="opacity-70">{{ $total }}</span>
            </button>
            @foreach ($roles as $rol)
                <button type="button" wire:click="$set('filtroRol', @js($rol->name))" class="{{ $chip }} {{ $filtroRol === $rol->name ? $chipActivo : $chipInactivo }}">
                    {{ ucfirst($rol->name) }} <span class="opacity-70">{{ $rol->users_count }}</span>
                </button>
            @endforeach
            @if ($sinRol > 0)
                <button type="button" wire:click="$set('filtroRol', 'sin_rol')" class="{{ $chip }} {{ $filtroRol === 'sin_rol' ? $chipActivo : $chipInactivo }}">
                    Sin rol <span class="opacity-70">{{ $sinRol }}</span>
                </button>
            @endif
            @if ($hayFiltros)
                <button type="button" wire:click="limpiarFiltros" class="border-0 bg-transparent px-1 py-0 text-sm font-medium text-indigo-600 shadow-none hover:text-indigo-500">Quitar filtros</button>
            @endif
        </div>
    </div>

    @if ($usuarios->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 px-4 py-16 text-center dark:border-zinc-600">
            <p class="text-sm text-zinc-500">{{ $hayFiltros ? 'Ningún usuario coincide con estos filtros.' : 'Aún no hay usuarios.' }}</p>
        </div>
    @else
        {{-- Tabla (escritorio) --}}
        <div class="hidden overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs md:block dark:border-zinc-700 dark:bg-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr class="text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        <th scope="col" class="px-5 py-3">Usuario</th>
                        <th scope="col" class="px-5 py-3">Rol</th>
                        <th scope="col" class="px-5 py-3">Cliente</th>
                        <th scope="col" class="px-5 py-3 text-center">Zonas</th>
                        <th scope="col" class="px-5 py-3">Alta</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                    @foreach ($usuarios as $usuario)
                        <tr wire:key="usuario-{{ $usuario->id }}" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-700/30">
                            <td class="px-5 py-3">
                                @include('livewire.admin.users.partials.identidad', ['usuario' => $usuario])
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($usuario->roles as $rol)
                                        <span class="{{ $badge }} {{ $colorRol[$rol->name] ?? $colorRolDefecto }}">{{ ucfirst($rol->name) }}</span>
                                    @empty
                                        <span class="{{ $badge }} bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300" title="Sin rol no puede usar el panel">Sin rol</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $usuario->cliente?->nombre ?? '—' }}</td>
                            <td class="px-5 py-3 text-center text-sm tabular-nums text-zinc-700 dark:text-zinc-200">{{ $usuario->zonas_count ?: '—' }}</td>
                            <td class="px-5 py-3 text-sm text-zinc-500 dark:text-zinc-400" title="{{ $usuario->created_at?->format('d/m/Y H:i') }}">{{ $usuario->created_at?->translatedFormat('j M Y') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.users.editar', ['userId' => $usuario->id]) }}" wire:navigate class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">Editar</a>
                                    @if ($usuario->id !== auth()->id())
                                        <button type="button" wire:click="confirmarEliminar({{ $usuario->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-zinc-300 bg-white p-0 text-zinc-500 shadow-none hover:border-red-300 hover:bg-red-50 hover:text-red-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400" aria-label="Eliminar a {{ $usuario->name }}">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/></svg>
                                        </button>
                                    @else
                                        <span class="inline-block w-8"></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tarjetas (móvil) --}}
        <div class="space-y-3 md:hidden">
            @foreach ($usuarios as $usuario)
                <div wire:key="usuario-movil-{{ $usuario->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-700 dark:bg-zinc-800">
                    @include('livewire.admin.users.partials.identidad', ['usuario' => $usuario])
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        @forelse ($usuario->roles as $rol)
                            <span class="{{ $badge }} {{ $colorRol[$rol->name] ?? $colorRolDefecto }}">{{ ucfirst($rol->name) }}</span>
                        @empty
                            <span class="{{ $badge }} bg-red-50 text-red-700 ring-red-600/20">Sin rol</span>
                        @endforelse
                        @if ($usuario->cliente)
                            <span class="text-xs text-zinc-500">· {{ $usuario->cliente->nombre }}</span>
                        @endif
                        @if ($usuario->zonas_count)
                            <span class="text-xs text-zinc-500">· {{ $usuario->zonas_count }} {{ $usuario->zonas_count === 1 ? 'zona' : 'zonas' }}</span>
                        @endif
                    </div>
                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('admin.users.editar', ['userId' => $usuario->id]) }}" wire:navigate class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-center text-sm font-medium text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Editar</a>
                        @if ($usuario->id !== auth()->id())
                            <button type="button" wire:click="confirmarEliminar({{ $usuario->id }})" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-red-600 shadow-none dark:border-zinc-600 dark:bg-zinc-800">Eliminar</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $usuarios->links() }}</div>
    @endif

    {{-- Confirmar eliminación --}}
    @if ($eliminando)
        @php $motivo = $this->motivoNoEliminar($eliminando); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" wire:keydown.escape.window="$set('eliminandoId', null)">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800" role="dialog" aria-modal="true" aria-labelledby="titulo-eliminar">
                <h2 id="titulo-eliminar" class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $motivo ? 'No se puede eliminar' : '¿Eliminar este usuario?' }}</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                    @if ($motivo)
                        {{ $motivo }}
                    @else
                        <strong>{{ $eliminando->name }}</strong> ({{ $eliminando->email }}) ya no podrá entrar al panel. Esta acción no se puede deshacer.
                    @endif
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('eliminandoId', null)" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">{{ $motivo ? 'Entendido' : 'Cancelar' }}</button>
                    @unless ($motivo)
                        <button type="button" wire:click="eliminar" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-none hover:bg-red-500">Eliminar</button>
                    @endunless
                </div>
            </div>
        </div>
    @endif
</div>
