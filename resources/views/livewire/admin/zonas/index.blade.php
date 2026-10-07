@php
    $etiquetaRegistro = ['formulario' => 'Formulario', 'redes' => 'Redes sociales', 'sin_registro' => 'Sin registro'];
    $colorRegistro = [
        'formulario' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300',
        'redes' => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
        'sin_registro' => 'bg-zinc-100 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-700 dark:text-zinc-300',
    ];
    $etiquetaAuth = ['sin_autenticacion' => 'Sin autenticación', 'usuario_password' => 'Usuario y contraseña', 'pin' => 'PIN'];
    $etiquetaModo = ['aleatorio' => 'Alternancia', 'prioridad' => 'Por prioridad', 'video' => 'Solo videos', 'imagen' => 'Solo imágenes'];
    $badge = 'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset';
    $aviso = 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300';
    $botonSecundario = 'inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700';
@endphp

<div class="w-full">
    {{-- Encabezado --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Zonas</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Hotspots, su portal cautivo y sus campañas · {{ $zonas->total() }} {{ $zonas->total() === 1 ? 'zona' : 'zonas' }}</p>
        </div>
        <a href="{{ route('admin.zonas.crear') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
            Nueva zona
        </a>
    </div>

    {{-- Mensajes --}}
    @if (session()->has('message'))
        <div class="mb-4 break-words rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="status">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">{{ session('error') }}</div>
    @endif

    {{-- Barra de herramientas --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 items-center gap-3">
            <label class="flex w-full max-w-md items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 shadow-xs focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-800">
                <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
                <span class="sr-only">Buscar zonas</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o ID…" class="h-9 w-full rounded-none border-0 bg-transparent px-0 py-2 shadow-none text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100">
            </label>
            <select wire:model.live="perPage" aria-label="Zonas por página" class="w-auto rounded-lg border border-zinc-300 bg-white py-2 pl-3 pr-8 text-sm text-zinc-700 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>

    {{-- Tabla (escritorio) --}}
    <div class="hidden overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-xs lg:block dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400">
                <tr>
                    <th scope="col" class="px-4 py-3 font-medium">Zona</th>
                    <th scope="col" class="px-4 py-3 font-medium">Registro</th>
                    <th scope="col" class="px-4 py-3 font-medium">Acceso</th>
                    <th scope="col" class="px-4 py-3 font-medium">Portal</th>
                    <th scope="col" class="px-4 py-3 font-medium">Campañas</th>
                    <th scope="col" class="px-4 py-3 text-right font-medium"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse ($zonas as $zona)
                    <tr wire:key="zona-{{ $zona->id }}" class="align-top hover:bg-zinc-50/60 dark:hover:bg-zinc-700/30">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.zonas.editar', ['zonaId' => $zona->id]) }}" wire:navigate class="font-medium text-zinc-900 hover:text-indigo-600 dark:text-white">{{ $zona->nombre }}</a>
                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                <span class="font-mono">ID {{ $zona->login_form_id }}</span> · {{ $zona->user?->name ?? 'Sin propietario' }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $badge }} {{ $colorRegistro[$zona->tipo_registro] ?? $colorRegistro['sin_registro'] }}">{{ $etiquetaRegistro[$zona->tipo_registro] ?? $zona->tipo_registro }}</span>
                            @if ($zona->tipo_registro !== 'sin_registro')
                                <div class="mt-1">
                                    <a href="{{ route('admin.zone.form-fields', ['zonaId' => $zona->id]) }}" wire:navigate class="text-xs {{ $zona->campos_count ? 'text-zinc-500 hover:text-indigo-600 dark:text-zinc-400' : 'font-medium text-amber-700 hover:text-amber-900 dark:text-amber-400' }}">
                                        {{ $zona->campos_count ? $zona->campos_count . ' ' . ($zona->campos_count === 1 ? 'campo' : 'campos') : 'Sin campos: agregar' }}
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            {{ $etiquetaAuth[$zona->tipo_autenticacion_mikrotik] ?? $zona->tipo_autenticacion_mikrotik }}
                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Cuenta regresiva {{ $zona->segundos }} s</div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($zona->portal_tema === 'evento')
                                <span class="{{ $badge }} bg-[#0f2148] text-[#f5b82e] ring-[#f5b82e]/40">Evento</span>
                            @else
                                <span class="{{ $badge }} bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-300">Clásico</span>
                            @endif
                            @php $extras = array_filter([$zona->portal_rifa ? 'Rifa' : null, $zona->portal_socios ? 'Socios' : null, $zona->pantalla_token ? 'Pantalla en vivo' : null]); @endphp
                            @if ($extras)
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ implode(' · ', $extras) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($zona->campanas_count)
                                <a href="{{ route('admin.zonas.configuracion-campanas', ['zonaId' => $zona->id]) }}" wire:navigate class="text-zinc-700 hover:text-indigo-600 dark:text-zinc-300">{{ $zona->campanas_count }} {{ $zona->campanas_count === 1 ? 'asignada' : 'asignadas' }}</a>
                            @else
                                <span class="{{ $badge }} {{ $aviso }}" title="Sin campañas asignadas, el portal usa las del cliente y las globales">Sin asignar</span>
                            @endif
                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $etiquetaModo[$zona->seleccion_campanas] ?? 'Alternancia' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.zonas.editar', ['zonaId' => $zona->id]) }}" wire:navigate class="{{ $botonSecundario }}">Editar</a>
                                <a href="{{ route('cliente.zona.preview', ['id' => $zona->id]) }}" target="_blank" class="{{ $botonSecundario }}" title="Vista previa del portal">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                    <span class="sr-only">Vista previa</span>
                                </a>
                                @include('livewire.admin.zonas.partials.menu-acciones', ['zona' => $zona])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500">
                            {{ $search ? 'Ninguna zona coincide con "' . $search . '".' : 'Aún no hay zonas.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tarjetas (celular y tablet) --}}
    <div class="space-y-3 lg:hidden">
        @forelse ($zonas as $zona)
            <article wire:key="zona-card-{{ $zona->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('admin.zonas.editar', ['zonaId' => $zona->id]) }}" wire:navigate class="block truncate font-medium text-zinc-900 dark:text-white">{{ $zona->nombre }}</a>
                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400"><span class="font-mono">ID {{ $zona->login_form_id }}</span> · {{ $zona->user?->name ?? 'Sin propietario' }}</div>
                    </div>
                    @include('livewire.admin.zonas.partials.menu-acciones', ['zona' => $zona])
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="{{ $badge }} {{ $colorRegistro[$zona->tipo_registro] ?? $colorRegistro['sin_registro'] }}">{{ $etiquetaRegistro[$zona->tipo_registro] ?? $zona->tipo_registro }}</span>
                    @if ($zona->tipo_registro !== 'sin_registro' && !$zona->campos_count)
                        <span class="{{ $badge }} {{ $aviso }}">Sin campos</span>
                    @endif
                    @if ($zona->portal_tema === 'evento')
                        <span class="{{ $badge }} bg-[#0f2148] text-[#f5b82e] ring-[#f5b82e]/40">Evento</span>
                    @else
                        <span class="{{ $badge }} bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-300">Clásico</span>
                    @endif
                    @if ($zona->campanas_count)
                        <span class="{{ $badge }} bg-zinc-100 text-zinc-700 ring-zinc-500/20 dark:bg-zinc-700 dark:text-zinc-300">{{ $zona->campanas_count }} {{ $zona->campanas_count === 1 ? 'campaña' : 'campañas' }}</span>
                    @else
                        <span class="{{ $badge }} {{ $aviso }}">Campañas sin asignar</span>
                    @endif
                </div>
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $etiquetaAuth[$zona->tipo_autenticacion_mikrotik] ?? $zona->tipo_autenticacion_mikrotik }} · {{ $zona->segundos }} s · {{ $etiquetaModo[$zona->seleccion_campanas] ?? 'Alternancia' }}</div>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('admin.zonas.editar', ['zonaId' => $zona->id]) }}" wire:navigate class="{{ $botonSecundario }} flex-1 justify-center">Editar</a>
                    <a href="{{ route('cliente.zona.preview', ['id' => $zona->id]) }}" target="_blank" class="{{ $botonSecundario }} flex-1 justify-center">Vista previa</a>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-zinc-300 px-4 py-10 text-center text-sm text-zinc-500 dark:border-zinc-600">
                {{ $search ? 'Ninguna zona coincide con "' . $search . '".' : 'Aún no hay zonas.' }}
            </p>
        @endforelse
    </div>

    <div class="mt-4">{{ $zonas->links() }}</div>

    {{-- Confirmar eliminación --}}
    @if ($confirmingZonaDeletion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-eliminar">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h3 id="titulo-eliminar" class="text-lg font-semibold text-zinc-900 dark:text-white">¿Eliminar esta zona?</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Se eliminan también sus campos de formulario. Esta acción no se puede deshacer.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('confirmingZonaDeletion', false)" class="{{ $botonSecundario }}">Cancelar</button>
                    <button type="button" wire:click="deleteZona" class="rounded-lg bg-red-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-red-500">Eliminar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Instrucciones de instalación en el MikroTik --}}
    @if ($showInstructionsModal && $activeZonaForInstructions)
        @php $zi = $activeZonaForInstructions; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-instrucciones">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h3 id="titulo-instrucciones" class="text-lg font-semibold text-zinc-900 dark:text-white">Instalar en el MikroTik · {{ $zi->nombre }}</h3>
                <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm text-zinc-700 dark:text-zinc-300">
                    <li>Descarga <strong>login.html</strong> y <strong>alogin.html</strong> desde el menú ⋯ de la zona.</li>
                    <li>En el MikroTik (Winbox → Files), súbelos a la carpeta <span class="font-mono">hotspot/</span>, reemplazando los existentes. El login.html apunta a <span class="break-all font-mono">{{ rtrim(url('/login_formulario'), '/') }}/{{ $zi->login_form_id }}</span>.</li>
                    <li>En <span class="font-mono">IP → Hotspot → Walled Garden</span> permite el dominio del portal (<span class="font-mono">{{ request()->getHost() }}</span>) para HTTP y en <em>Walled Garden IP</em> para HTTPS.</li>
                    <li>
                        Autenticación configurada: <strong>{{ $etiquetaAuth[$zi->tipo_autenticacion_mikrotik] ?? $zi->tipo_autenticacion_mikrotik }}</strong>.
                        @if ($zi->tipo_autenticacion_mikrotik === 'sin_autenticacion')
                            Activa <span class="font-mono">trial</span> en el perfil del servidor hotspot (login-by) para que el portal conecte al terminar la cuenta regresiva.
                        @elseif ($zi->tipo_autenticacion_mikrotik === 'pin')
                            Crea usuarios del hotspot cuyo nombre de usuario sea el PIN.
                        @else
                            Crea usuarios del hotspot con su usuario y contraseña.
                        @endif
                    </li>
                </ol>
                <div class="mt-6 flex justify-end">
                    <button type="button" wire:click="closeInstructionsModal" class="{{ $botonSecundario }}">Cerrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
