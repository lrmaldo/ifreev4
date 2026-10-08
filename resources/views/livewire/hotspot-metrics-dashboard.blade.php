@php
    $tarjeta = 'rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-800';
    $control = 'w-auto rounded-lg border border-zinc-300 bg-white py-2 pl-3 pr-8 text-sm text-zinc-700 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200';
    $fechaInput = 'rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200';
    $nf = fn ($n) => number_format((float) $n, 0, '.', ',');
    $dias = count($porDia);
    $textoPeriodo = $periodo === 'hoy'
        ? 'Hoy, ' . $hastaFecha->translatedFormat('j \d\e F')
        : $desdeFecha->translatedFormat('j M Y') . ' – ' . $hastaFecha->translatedFormat('j M Y');
    $principales = [
        ['clave' => 'visitas', 'titulo' => 'Visitas al portal', 'valor' => $nf($resumen['visitas']), 'ayuda' => 'Veces que se abrió el portal cautivo.'],
        ['clave' => 'nuevos', 'titulo' => 'Dispositivos nuevos', 'valor' => $nf($resumen['nuevos']), 'ayuda' => 'Celulares o equipos que se conectaron por primera vez.'],
        ['clave' => 'registros', 'titulo' => 'Registros', 'valor' => $nf($resumen['registros']), 'ayuda' => 'Formularios enviados en el portal.'],
        ['clave' => null, 'titulo' => 'Conversión', 'valor' => $resumen['conversion'] . '%', 'ayuda' => 'Registros entre dispositivos nuevos.'],
    ];
    $secundarias = [
        ['Dispositivos activos', $nf($resumen['activos'])],
        ['Regresaron', $nf($resumen['recurrentes'])],
        ['Clics en anuncios', $nf($resumen['clics']) . ($resumen['visitas'] ? ' · ' . $resumen['ctr'] . '%' : '')],
        ['Tiempo viendo anuncio', $resumen['duracion_promedio'] . ' s'],
    ];
    $totalPlataformas = max(1, array_sum($plataformas));
    $colorPlataforma = ['Android' => 'bg-emerald-500', 'iPhone / iPad' => 'bg-zinc-700 dark:bg-zinc-300', 'Windows' => 'bg-sky-500', 'Mac' => 'bg-violet-500', 'Otros' => 'bg-zinc-300 dark:bg-zinc-600'];
@endphp

<div class="w-full space-y-6">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Métricas</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $zonaActual?->nombre ?? ($zonas->count() === 1 ? $zonas->first()->nombre : 'Todas las zonas') }} · {{ $textoPeriodo }}
            </p>
        </div>
        @can('gestionar metricas hotspot')
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="generateWrapped(this)" class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-none hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200" title="Imagen para compartir con el resumen del periodo">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 00.75-.75v-2.69l-2.22-2.219a.75.75 0 00-1.06 0l-1.91 1.909.47.47a.75.75 0 11-1.06 1.06L6.53 8.091a.75.75 0 00-1.06 0l-2.97 2.97zM12 7a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/></svg>
                    Wrapped
                </button>
                <a href="{{ route('hotspot-metrics.export', ['zona_id' => $zona_id, 'fecha_inicio' => $desdeFecha->toDateString(), 'fecha_fin' => $hastaFecha->toDateString(), 'mac_address' => $mac_address]) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
                    Exportar CSV
                </a>
            </div>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-center gap-3">
        @if ($zonas->count() > 1)
            <select wire:model.live="zona_id" aria-label="Zona" class="{{ $control }} max-w-full">
                <option value="">Todas las zonas</option>
                @foreach ($zonas as $zona)
                    <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                @endforeach
            </select>
        @endif

        <div class="inline-flex rounded-lg border border-zinc-300 bg-white p-0.5 dark:border-zinc-600 dark:bg-zinc-800" role="group" aria-label="Periodo">
            @foreach ($periodos as $valor => $etiqueta)
                <button type="button" wire:click="$set('periodo', @js((string) $valor))" @class([
                    'rounded-md border-0 px-3 py-1.5 text-sm font-medium shadow-none transition',
                    'bg-indigo-600 text-white' => $periodo === (string) $valor,
                    'bg-transparent text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700' => $periodo !== (string) $valor,
                ])>{{ $etiqueta }}</button>
            @endforeach
        </div>

        @if ($periodo === 'rango')
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="desde" aria-label="Desde" class="{{ $fechaInput }}" max="{{ now()->toDateString() }}">
                <span class="text-sm text-zinc-400">a</span>
                <input type="date" wire:model.live="hasta" aria-label="Hasta" class="{{ $fechaInput }}" max="{{ now()->toDateString() }}">
            </div>
        @endif

        <span wire:loading class="text-sm text-zinc-400">Actualizando…</span>
    </div>

    @if ($zonas->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 px-4 py-16 text-center dark:border-zinc-600">
            <p class="text-sm text-zinc-500">Aún no tienes zonas. Cuando tu hotspot reciba visitas, las métricas aparecerán aquí.</p>
        </div>
    @else
        {{-- Indicadores principales --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($principales as $kpi)
                <div class="{{ $tarjeta }} p-5" title="{{ $kpi['ayuda'] }}">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $kpi['titulo'] }}</p>
                    <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-zinc-900 dark:text-white">{{ $kpi['valor'] }}</p>
                    @if ($kpi['clave'] && $periodo !== 'rango')
                        @php $cambio = $variacion[$kpi['clave']] ?? null; @endphp
                        <p class="mt-1 text-xs">
                            @if ($cambio === null)
                                <span class="text-zinc-400">Sin datos del periodo anterior</span>
                            @else
                                <span @class(['font-medium', 'text-green-600 dark:text-green-400' => $cambio > 0, 'text-red-600 dark:text-red-400' => $cambio < 0, 'text-zinc-500' => $cambio === 0])>
                                    {{ $cambio > 0 ? '▲' : ($cambio < 0 ? '▼' : '') }} {{ abs($cambio) }}%
                                </span>
                                <span class="text-zinc-400">vs. {{ $periodo === 'hoy' ? 'ayer' : 'periodo anterior' }}</span>
                            @endif
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-zinc-200 bg-zinc-200 sm:grid-cols-4 dark:border-zinc-700 dark:bg-zinc-700">
            @foreach ($secundarias as [$titulo, $valor])
                <div class="bg-white px-5 py-3 dark:bg-zinc-800">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $titulo }}</p>
                    <p class="mt-0.5 text-lg font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $valor }}</p>
                </div>
            @endforeach
        </div>

        {{-- Actividad por día --}}
        @if ($dias > 1)
            <section class="{{ $tarjeta }} p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Actividad por día</h2>
                    <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-200 dark:bg-indigo-500/40"></span> Visitas</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-600"></span> Dispositivos nuevos</span>
                    </div>
                </div>
                <div class="flex h-48 items-end gap-px sm:gap-0.5" role="img" aria-label="Visitas y dispositivos nuevos por día">
                    @foreach ($porDia as $dia)
                        @php $fecha = \Carbon\Carbon::parse($dia['fecha']); @endphp
                        <div class="group relative flex h-full flex-1 items-end justify-center" title="{{ $fecha->translatedFormat('D j M') }} · {{ $nf($dia['visitas']) }} visitas · {{ $nf($dia['nuevos']) }} nuevos · {{ $nf($dia['registros']) }} registros">
                            <div class="absolute bottom-0 w-full rounded-t-sm bg-indigo-200 group-hover:bg-indigo-300 dark:bg-indigo-500/40" style="height: {{ round($dia['visitas'] / $maxDia * 100, 1) }}%"></div>
                            <div class="absolute bottom-0 w-1/2 rounded-t-sm bg-indigo-600" style="height: {{ round($dia['nuevos'] / $maxDia * 100, 1) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 flex justify-between text-xs text-zinc-400">
                    <span>{{ \Carbon\Carbon::parse($porDia[0]['fecha'])->translatedFormat('j M') }}</span>
                    @if ($dias > 6)
                        <span>{{ \Carbon\Carbon::parse($porDia[intdiv($dias, 2)]['fecha'])->translatedFormat('j M') }}</span>
                    @endif
                    <span>{{ \Carbon\Carbon::parse($porDia[$dias - 1]['fecha'])->translatedFormat('j M') }}</span>
                </div>
            </section>
        @endif

        {{-- Desgloses --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="{{ $tarjeta }} p-5">
                <h2 class="mb-4 text-base font-semibold text-zinc-900 dark:text-white">Plataformas</h2>
                @if (empty($plataformas))
                    <p class="text-sm text-zinc-500">Sin dispositivos en este periodo.</p>
                @else
                    <div class="mb-4 flex h-2.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                        @foreach ($plataformas as $nombre => $total)
                            <div class="{{ $colorPlataforma[$nombre] ?? 'bg-zinc-400' }}" style="width: {{ $total / $totalPlataformas * 100 }}%"></div>
                        @endforeach
                    </div>
                    <ul class="space-y-2 text-sm">
                        @foreach ($plataformas as $nombre => $total)
                            <li class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-zinc-700 dark:text-zinc-200"><span class="h-2.5 w-2.5 rounded-full {{ $colorPlataforma[$nombre] ?? 'bg-zinc-400' }}"></span>{{ $nombre }}</span>
                                <span class="tabular-nums text-zinc-500">{{ $nf($total) }} · {{ round($total / $totalPlataformas * 100) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @foreach (['Dispositivos más comunes' => $topDispositivos, 'Navegadores' => $topNavegadores] as $titulo => $lista)
                <section class="{{ $tarjeta }} p-5">
                    <h2 class="mb-4 text-base font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</h2>
                    @if (empty($lista))
                        <p class="text-sm text-zinc-500">Sin datos en este periodo.</p>
                    @else
                        @php $maxLista = max(1, $lista[0]['total']); @endphp
                        <ul class="space-y-3 text-sm">
                            @foreach ($lista as $fila)
                                <li>
                                    <div class="mb-1 flex justify-between gap-2">
                                        <span class="truncate text-zinc-700 dark:text-zinc-200" title="{{ $fila['nombre'] }}">{{ $fila['nombre'] }}</span>
                                        <span class="shrink-0 tabular-nums text-zinc-500">{{ $nf($fila['total']) }}</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-700"><div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ $fila['total'] / $maxLista * 100 }}%"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>

        {{-- Ranking de zonas --}}
        @if (!empty($rankingZonas))
            <section class="{{ $tarjeta }} overflow-hidden">
                <h2 class="border-b border-zinc-200 px-5 py-4 text-base font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">Zonas con más visitas</h2>
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                    @php $maxZona = max(1, $rankingZonas[0]['visitas']); @endphp
                    @foreach ($rankingZonas as $i => $fila)
                        <li class="flex items-center gap-4 px-5 py-3">
                            <span class="w-5 text-sm font-semibold tabular-nums text-zinc-400">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <button type="button" wire:click="$set('zona_id', '{{ $fila['id'] }}')" class="max-w-full border-0 bg-transparent p-0 text-left text-sm font-medium text-zinc-900 shadow-none hover:text-indigo-600 dark:text-white" title="Ver solo esta zona">
                                    <span class="truncate">{{ $fila['nombre'] }}</span>
                                </button>
                                <div class="mt-1 h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-700"><div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ $fila['visitas'] / $maxZona * 100 }}%"></div></div>
                            </div>
                            <span class="w-24 text-right text-sm tabular-nums text-zinc-700 dark:text-zinc-200">{{ $nf($fila['visitas']) }} <span class="text-zinc-400">visitas</span></span>
                            <span class="hidden w-28 text-right text-sm tabular-nums text-zinc-700 sm:block dark:text-zinc-200">{{ $nf($fila['registros']) }} <span class="text-zinc-400">registros</span></span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Dispositivos --}}
        <section class="{{ $tarjeta }} overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Dispositivos</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $nf($dispositivos->total()) }} con actividad en el periodo</p>
                </div>
                <label class="flex w-full max-w-xs items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 shadow-xs focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/30 dark:border-zinc-600 dark:bg-zinc-900">
                    <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
                    <span class="sr-only">Buscar por MAC</span>
                    <input type="search" wire:model.live.debounce.300ms="mac_address" placeholder="Buscar MAC…" class="h-9 w-full rounded-none border-0 bg-transparent px-0 py-2 font-mono text-sm text-zinc-900 placeholder-zinc-400 shadow-none focus:outline-none focus:ring-0 dark:text-zinc-100">
                </label>
            </div>

            @if ($dispositivos->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-zinc-500">{{ $mac_address ? 'Ninguna MAC coincide con la búsqueda.' : 'Sin dispositivos en este periodo.' }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                <th scope="col" class="px-5 py-3">Dispositivo</th>
                                @if (!$zonaActual && $zonas->count() > 1)<th scope="col" class="px-5 py-3">Zona</th>@endif
                                @foreach (['veces_entradas' => 'Entradas', 'updated_at' => 'Última vez', 'created_at' => 'Primera vez'] as $col => $etiqueta)
                                    <th scope="col" class="px-5 py-3 {{ $col === 'veces_entradas' ? 'text-center' : '' }}">
                                        <button type="button" wire:click="sortBy('{{ $col }}')" class="inline-flex items-center gap-1 border-0 bg-transparent p-0 text-xs font-medium uppercase tracking-wide text-zinc-500 shadow-none hover:text-zinc-800 dark:text-zinc-400">
                                            {{ $etiqueta }}
                                            @if ($order_by === $col)<span aria-hidden="true">{{ $order_direction === 'asc' ? '↑' : '↓' }}</span>@endif
                                        </button>
                                    </th>
                                @endforeach
                                <th scope="col" class="px-5 py-3">Registro</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Detalle</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                            @foreach ($dispositivos as $metrica)
                                <tr wire:key="metrica-{{ $metrica->id }}" class="hover:bg-zinc-50/60 dark:hover:bg-zinc-700/30">
                                    <td class="px-5 py-3">
                                        <p class="max-w-56 truncate font-medium text-zinc-900 dark:text-white" title="{{ $metrica->dispositivo }}">{{ $metrica->dispositivo ?: 'Desconocido' }}</p>
                                        <p class="font-mono text-xs text-zinc-500">{{ $metrica->mac_address }} · {{ \Illuminate\Support\Str::limit($metrica->sistema_operativo ?: 'SO desconocido', 18) }}</p>
                                    </td>
                                    @if (!$zonaActual && $zonas->count() > 1)
                                        <td class="max-w-40 truncate px-5 py-3 text-zinc-600 dark:text-zinc-300">{{ $metrica->zona->nombre ?? '—' }}</td>
                                    @endif
                                    <td class="px-5 py-3 text-center font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $metrica->veces_entradas }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-zinc-600 dark:text-zinc-300" title="{{ $metrica->updated_at?->format('d/m/Y H:i') }}">{{ $metrica->updated_at?->diffForHumans() }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-zinc-500 dark:text-zinc-400">{{ $metrica->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-3">
                                        @if ($metrica->formulario_id)
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-300">Registrado</span>
                                        @else
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('hotspot-metrics.detalles', $metrica->id) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($dispositivos->hasPages())
                    <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-700">{{ $dispositivos->links() }}</div>
                @endif
            @endif
        </section>
    @endif

    @can('gestionar metricas hotspot')
        @include('livewire.partials.metricas-wrapped')
    @endcan
</div>
