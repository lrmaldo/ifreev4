@php
    $tarjeta = 'rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-800';
    $botonSecundario = 'rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700';
    $badge = 'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset';
    $campo = 'mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white';
    $velocidad = function (?int $bps) {
        if ($bps === null) {
            return '—';
        }
        return $bps >= 1_000_000 ? number_format($bps / 1_000_000, 1) . ' Mbps' : number_format($bps / 1000) . ' kbps';
    };
    $duracion = function (?int $seg) {
        if ($seg === null) {
            return '—';
        }
        $d = intdiv($seg, 86400);
        $h = intdiv($seg % 86400, 3600);
        $m = intdiv($seg % 3600, 60);
        return $d ? "{$d} d {$h} h" : ($h ? "{$h} h {$m} min" : "{$m} min");
    };
@endphp

{{-- Sin refresco mientras hay un diálogo abierto --}}
<div class="w-full space-y-6" @if (!$mostrarForm && !$eliminando) wire:poll.30s @endif>
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Access points</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Clientes y tráfico por SNMP, cada minuto · <strong class="text-zinc-700 dark:text-zinc-200">{{ number_format($totalClientes) }}</strong> clientes conectados ahora</p>
        </div>
        <button type="button" wire:click="nuevo" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Agregar AP</button>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200" role="status">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">{{ session('error') }}</div>
    @endif

    @if ($tarjetas->isEmpty())
        <section class="{{ $tarjeta }} px-5 py-12 text-center text-sm text-zinc-500">
            Todavía no hay access points. Agrega uno con su IP (o la del MikroTik con el puerto del NAT) y la community SNMP.
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($tarjetas as $t)
            @php
                $ap = $t['ap'];
                $valores = collect($t['grafica'])->pluck('clientes');
                $maximo = max(1, (int) $valores->max());
                $n = max(1, count($t['grafica']) - 1);
                $puntos = collect($t['grafica'])
                    ->map(fn ($p, $i) => $p['clientes'] === null ? null : round($i / $n * 300, 1) . ',' . round(60 - $p['clientes'] / $maximo * 56, 1))
                    ->filter()
                    ->implode(' ');
                $estado = !$ap->activo
                    ? ['Pausado', 'bg-zinc-100 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-700 dark:text-zinc-300']
                    : ($t['responde']
                        ? ['En línea', 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-300']
                        : ['Sin respuesta', 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300']);
            @endphp
            <section wire:key="ap-{{ $ap->id }}" @class([$tarjeta, 'p-5', 'opacity-60' => !$ap->activo])>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-zinc-900 dark:text-white">{{ $ap->nombre }}</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $ap->modelo ?? 'Modelo desconocido' }} · <span class="font-mono">{{ $ap->host }}:{{ $ap->puerto }}</span>
                            @if ($ap->zona) · {{ $ap->zona->nombre }} @endif
                        </p>
                    </div>
                    <span class="{{ $badge }} {{ $estado[1] }}">{{ $estado[0] }}</span>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">Clientes ahora</dt>
                        <dd class="mt-0.5 text-3xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $t['responde'] ? number_format((int) $ap->clientes) : '—' }}</dd>
                        <dd class="text-xs text-zinc-500">máx. hoy {{ number_format($t['maximoHoy']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">Pasaron por el portal hoy</dt>
                        <dd class="mt-0.5 text-xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $ap->zona_id ? number_format($t['cruce']['portal']) : '—' }}</dd>
                        <dd class="text-xs text-zinc-500">{{ $ap->zona_id ? number_format($t['cruce']['registrados']) . ' registrados' : 'Liga una zona' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">Bajada · subida</dt>
                        <dd class="mt-0.5 text-sm font-semibold tabular-nums text-zinc-900 dark:text-white">↓ {{ $t['responde'] ? $velocidad($ap->bajada_bps) : '—' }}</dd>
                        <dd class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-white">↑ {{ $t['responde'] ? $velocidad($ap->subida_bps) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">Encendido hace</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-white">{{ $t['responde'] ? $duracion($ap->uptime_seg) : '—' }}</dd>
                        <dd class="text-xs text-zinc-500">{{ $ap->ultima_respuesta_at ? 'Leído ' . $ap->ultima_respuesta_at->locale('es')->diffForHumans() : 'Nunca ha respondido' }}</dd>
                    </div>
                </dl>

                @if ($ap->activo && !$t['responde'] && $ap->ultimo_error)
                    <p class="mt-3 text-sm text-red-600 dark:text-red-400">{{ $ap->ultimo_error }}</p>
                @endif

                <figure class="mt-4">
                    <figcaption class="mb-1 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>Clientes, últimas {{ \App\Livewire\Admin\Aps\Index::HORAS_GRAFICA }} h</span>
                        <span class="tabular-nums">máx. {{ number_format($valores->max() ?? 0) }}</span>
                    </figcaption>
                    @if ($puntos)
                        <svg viewBox="0 0 300 60" preserveAspectRatio="none" class="h-16 w-full text-indigo-500" role="img" aria-label="Clientes de {{ $ap->nombre }} en las últimas horas, máximo {{ (int) $valores->max() }}">
                            <line x1="0" y1="60" x2="300" y2="60" stroke="currentColor" stroke-opacity=".2" vector-effect="non-scaling-stroke" />
                            <polyline points="{{ $puntos }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                        </svg>
                        <div class="flex justify-between text-[11px] text-zinc-400"><span>{{ $t['grafica'][0]['t'] ?? '' }}</span><span>ahora</span></div>
                    @else
                        <p class="flex h-16 items-center justify-center rounded-lg bg-zinc-50 text-xs text-zinc-400 dark:bg-zinc-900/40">Sin lecturas todavía</p>
                    @endif
                </figure>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="probar({{ $ap->id }})" class="{{ $botonSecundario }}">
                        <span wire:loading.remove wire:target="probar({{ $ap->id }})">Leer ahora</span>
                        <span wire:loading wire:target="probar({{ $ap->id }})">Leyendo…</span>
                    </button>
                    <button type="button" wire:click="editar({{ $ap->id }})" class="{{ $botonSecundario }}">Editar</button>
                    <button type="button" wire:click="alternarActivo({{ $ap->id }})" class="{{ $botonSecundario }}">{{ $ap->activo ? 'Pausar' : 'Reactivar' }}</button>
                    <button type="button" wire:click="confirmarEliminar({{ $ap->id }})" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-red-600 shadow-none hover:bg-red-50 dark:border-zinc-600 dark:bg-zinc-800">Eliminar</button>
                </div>
            </section>
        @endforeach
    </div>

    <section class="{{ $tarjeta }} p-5">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Cómo agregar un AP</h2>
        <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-zinc-600 dark:text-zinc-300">
            <li>Activa SNMP v2c (solo lectura) en el AP o en el Omada Controller.</li>
            <li>Si el AP está detrás de un MikroTik, crea un dst-nat UDP de un puerto libre hacia <code>IP-del-AP:161</code> y usa aquí la IP del MikroTik con ese puerto.</li>
            <li>El servidor lee cada AP activo cada minuto y avisa por Telegram si deja de responder o pasa de {{ config('ifree.alertas.ap_max_clientes') }} clientes.</li>
        </ol>
    </section>

    {{-- Agregar / editar --}}
    @if ($mostrarForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" wire:keydown.escape.window="$set('mostrarForm', false)">
            <form wire:submit="guardar" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800" role="dialog" aria-modal="true" aria-labelledby="titulo-form-ap">
                <h2 id="titulo-form-ap" class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $editandoId ? 'Editar AP' : 'Agregar AP' }}</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-6">
                    <label class="block text-sm sm:col-span-6">
                        <span class="text-zinc-700 dark:text-zinc-200">Nombre</span>
                        <input type="text" wire:model="nombre" class="{{ $campo }}" placeholder="Esquina del GYM" required>
                        @error('nombre') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm sm:col-span-4">
                        <span class="text-zinc-700 dark:text-zinc-200">IP o host</span>
                        <input type="text" wire:model="host" class="{{ $campo }} font-mono" placeholder="192.168.241.108" required>
                        @error('host') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm sm:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-200">Puerto UDP</span>
                        <input type="number" wire:model="puerto" min="1" max="65535" class="{{ $campo }} font-mono" required>
                        @error('puerto') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm sm:col-span-3">
                        <span class="text-zinc-700 dark:text-zinc-200">Community SNMP</span>
                        <input type="text" wire:model="community" class="{{ $campo }} font-mono" autocomplete="off" required>
                        @error('community') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm sm:col-span-3">
                        <span class="text-zinc-700 dark:text-zinc-200">Zona</span>
                        <select wire:model="zona_id" class="{{ $campo }}">
                            <option value="">Sin zona</option>
                            @foreach ($zonas as $zona)
                                <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                            @endforeach
                        </select>
                        @error('zona_id') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 sm:col-span-6 dark:text-zinc-200">
                        <input type="checkbox" wire:model="activo" class="rounded border-zinc-300">
                        Leer cada minuto
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('mostrarForm', false)" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-none hover:bg-indigo-500">
                        <span wire:loading.remove wire:target="guardar">Guardar y probar</span>
                        <span wire:loading wire:target="guardar">Probando…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Confirmar eliminación --}}
    @if ($eliminando)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4" wire:keydown.escape.window="$set('eliminandoId', null)">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800" role="dialog" aria-modal="true" aria-labelledby="titulo-eliminar-ap">
                <h2 id="titulo-eliminar-ap" class="text-lg font-semibold text-zinc-900 dark:text-white">¿Eliminar este AP?</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Se borra <strong>{{ $eliminando->nombre }}</strong> y todo su historial. Si solo quieres dejar de leerlo, usa Pausar.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('eliminandoId', null)" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">Cancelar</button>
                    <button type="button" wire:click="eliminar" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-none hover:bg-red-500">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
