@php
    $tarjeta = 'rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-800';
    $nf = fn ($n) => number_format((float) $n, 0, '.', ',');
    $hora = (int) now()->format('G');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $nombre = \Illuminate\Support\Str::of($user->name)->explode(' ')->first();
    $verMetricas = $user->hasAnyRole(['admin', 'cliente']) && $user->can('ver metricas hotspot');

    $kpis = [
        ['titulo' => 'Conectados ahora', 'valor' => $nf($conectados), 'cambio' => null, 'nota' => 'Activos en los últimos 15 min', 'vivo' => true],
        ['titulo' => 'Visitas hoy', 'valor' => $nf($resumenHoy['visitas']), 'cambio' => $variacionHoy['visitas'] ?? null],
        ['titulo' => 'Registros hoy', 'valor' => $nf($resumenHoy['registros']), 'cambio' => $variacionHoy['registros'] ?? null],
        ['titulo' => 'Dispositivos nuevos hoy', 'valor' => $nf($resumenHoy['nuevos']), 'cambio' => $variacionHoy['nuevos'] ?? null],
    ];

    if ($esAdmin) {
        $accesos = [
            ['Nueva zona', 'Configura un hotspot', route('admin.zonas.crear'), 'M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v2.5h-2.5a.75.75 0 000 1.5h2.5v2.5a.75.75 0 001.5 0v-2.5h2.5a.75.75 0 000-1.5h-2.5v-2.5z'],
            ['Nueva campaña', 'Sube una imagen o video', route('admin.campanas.crear'), 'M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 00.75-.75v-2.69l-2.22-2.219a.75.75 0 00-1.06 0l-1.91 1.909.47.47a.75.75 0 11-1.06 1.06L6.53 8.091a.75.75 0 00-1.06 0l-2.97 2.97zM12 7a1 1 0 11-2 0 1 1 0 012 0z'],
            ['Métricas', 'Visitas, registros y dispositivos', route('admin.hotspot-metrics.index'), 'M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z'],
            ['Usuarios', $nf($totalUsuarios) . ' con acceso al panel', route('admin.users.index'), 'M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 017 18a9.953 9.953 0 01-5.385-1.572zM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 00-1.588-3.755 4.502 4.502 0 015.874 2.636.818.818 0 01-.36.98A7.465 7.465 0 0114.5 16z'],
        ];
    } elseif ($user->hasRole('cliente')) {
        $accesos = array_filter([
            ['Mis zonas', $nf($totalZonas) . ' ' . ($totalZonas === 1 ? 'zona' : 'zonas'), route('cliente.zonas.index'), 'M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z'],
            ['Campañas', $nf($campanasActivas) . ' activas en tus zonas', route('cliente.campanas.index'), 'M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5z'],
            $verMetricas ? ['Métricas', 'Visitas, registros y dispositivos', route('hotspot-metrics.index'), 'M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z'] : null,
        ]);
    } else {
        $accesos = [];
    }
@endphp

<div class="w-full space-y-6" wire:poll.60s>
    {{-- Saludo --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $saludo }}, {{ $nombre }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ ucfirst(now()->translatedFormat('l j \d\e F')) }} ·
                @if ($esCliente)
                    {{ $totalZonas === 0 ? 'Aún no tienes zonas' : $nf($totalZonas) . ' ' . ($totalZonas === 1 ? 'zona' : 'zonas') }}
                @else
                    {{ $nf($totalZonas) }} zonas · {{ $nf($campanasActivas) }} campañas activas
                @endif
            </p>
        </div>
        <p class="flex items-center gap-2 text-xs text-zinc-400">
            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span></span>
            Se actualiza cada minuto
        </p>
    </div>

    @if ($esCliente && $totalZonas === 0)
        <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-14 text-center dark:border-zinc-600">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Bienvenido a i-Free</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-zinc-500 dark:text-zinc-400">Cuando el equipo de Sattlink configure tu primera zona WiFi, aquí verás en tiempo real cuántas personas se conectan, se registran y ven tus anuncios.</p>
        </div>
    @else
        {{-- Hoy --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($kpis as $kpi)
                <div class="{{ $tarjeta }} p-5">
                    <p class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        @if (!empty($kpi['vivo']))<span class="h-2 w-2 rounded-full {{ $conectados > 0 ? 'bg-green-500' : 'bg-zinc-300' }}"></span>@endif
                        {{ $kpi['titulo'] }}
                    </p>
                    <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight text-zinc-900 dark:text-white">{{ $kpi['valor'] }}</p>
                    <p class="mt-1 text-xs">
                        @if (!empty($kpi['nota']))
                            <span class="text-zinc-400">{{ $kpi['nota'] }}</span>
                        @elseif ($kpi['cambio'] === null)
                            <span class="text-zinc-400">Ayer a esta hora: sin datos</span>
                        @else
                            <span @class(['font-medium', 'text-green-600 dark:text-green-400' => $kpi['cambio'] > 0, 'text-red-600 dark:text-red-400' => $kpi['cambio'] < 0, 'text-zinc-500' => $kpi['cambio'] === 0])>{{ $kpi['cambio'] > 0 ? '▲' : ($kpi['cambio'] < 0 ? '▼' : '') }} {{ abs($kpi['cambio']) }}%</span>
                            <span class="text-zinc-400">vs. ayer a esta hora</span>
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Últimos 14 días --}}
            <section class="{{ $tarjeta }} p-5 lg:col-span-2">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Últimos 14 días</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Esta semana: {{ $nf($totalesSemana['visitas']) }} visitas · {{ $nf($totalesSemana['registros']) }} registros</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-200 dark:bg-indigo-500/40"></span> Visitas</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-600"></span> Nuevos</span>
                    </div>
                </div>
                <div class="flex h-44 items-end gap-1.5" role="img" aria-label="Visitas y dispositivos nuevos por día">
                    @foreach ($porDia as $dia)
                        @php $fecha = \Carbon\Carbon::parse($dia['fecha']); @endphp
                        <div class="group relative flex h-full flex-1 items-end justify-center" title="{{ $fecha->translatedFormat('D j M') }} · {{ $nf($dia['visitas']) }} visitas · {{ $nf($dia['nuevos']) }} nuevos · {{ $nf($dia['registros']) }} registros">
                            <div class="absolute bottom-0 w-full rounded-t bg-indigo-200 group-hover:bg-indigo-300 dark:bg-indigo-500/40" style="height: {{ round($dia['visitas'] / $maxDia * 100, 1) }}%"></div>
                            <div class="absolute bottom-0 w-1/2 rounded-t bg-indigo-600" style="height: {{ round($dia['nuevos'] / $maxDia * 100, 1) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 flex gap-1.5 text-center text-[11px] text-zinc-400">
                    @foreach ($porDia as $i => $dia)
                        <span class="flex-1 {{ $i % 2 ? 'hidden sm:block' : '' }}">{{ \Carbon\Carbon::parse($dia['fecha'])->translatedFormat($i === count($porDia) - 1 ? '\H\o\y' : 'j') }}</span>
                    @endforeach
                </div>
            </section>

            {{-- Zonas con más visitas --}}
            <section class="{{ $tarjeta }} overflow-hidden">
                <h2 class="border-b border-zinc-200 px-5 py-4 text-base font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">Zonas más activas <span class="text-sm font-normal text-zinc-400">· 7 días</span></h2>
                @if (empty($rankingZonas))
                    <p class="px-5 py-10 text-center text-sm text-zinc-500">Sin visitas en los últimos 7 días.</p>
                @else
                    @php $maxZona = max(1, $rankingZonas[0]['visitas']); @endphp
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                        @foreach ($rankingZonas as $fila)
                            <li class="px-5 py-3">
                                <div class="mb-1 flex justify-between gap-3 text-sm">
                                    <span class="truncate font-medium text-zinc-900 dark:text-white">{{ $fila['nombre'] }}</span>
                                    <span class="shrink-0 tabular-nums text-zinc-500">{{ $nf($fila['visitas']) }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-zinc-100 dark:bg-zinc-700"><div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ $fila['visitas'] / $maxZona * 100 }}%"></div></div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Accesos rápidos --}}
        @if (!empty($accesos))
            <section class="grid gap-3 sm:grid-cols-2 lg:col-span-2">
                @foreach ($accesos as [$titulo, $detalle, $url, $icono])
                    <a href="{{ $url }}" wire:navigate class="group flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-indigo-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-indigo-500/50">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-300">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="{{ $icono }}" clip-rule="evenodd"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-white">{{ $titulo }}</span>
                            <span class="block truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $detalle }}</span>
                        </span>
                        <svg class="ml-auto h-4 w-4 shrink-0 text-zinc-300 group-hover:text-indigo-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                    </a>
                @endforeach
            </section>
        @endif

        {{-- Campañas por vencer / zonas del cliente --}}
        @if ($esCliente && $zonasCliente->isNotEmpty())
            <section class="{{ $tarjeta }} overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Mis zonas</h2>
                    <a href="{{ route('cliente.zonas.index') }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver todas</a>
                </div>
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                    @foreach ($zonasCliente as $zona)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="truncate font-medium text-zinc-900 dark:text-white">{{ $zona->nombre }}</span>
                            @if ($verMetricas)
                                <a href="{{ route('hotspot-metrics.index', ['zona' => $zona->id]) }}" wire:navigate class="shrink-0 text-indigo-600 hover:text-indigo-500">Métricas</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @elseif (!$esCliente)
            <section class="{{ $tarjeta }} overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Campañas por vencer</h2>
                    @if ($esAdmin)
                        <a href="{{ route('admin.campanas.index', ['estado' => 'activas']) }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ver campañas</a>
                    @endif
                </div>
                @if ($campanasPorVencer->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-zinc-500">Ninguna vence en los próximos 7 días.</p>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                        @foreach ($campanasPorVencer as $campana)
                            @php $faltan = (int) now()->startOfDay()->diffInDays($campana->fecha_fin->startOfDay()); @endphp
                            <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                @if ($esAdmin)
                                    <a href="{{ route('admin.campanas.editar', ['campanaId' => $campana->id]) }}" wire:navigate class="truncate font-medium text-zinc-900 hover:text-indigo-600 dark:text-white">{{ $campana->titulo ?: 'Campaña #' . $campana->id }}</a>
                                @else
                                    <span class="truncate font-medium text-zinc-900 dark:text-white">{{ $campana->titulo ?: 'Campaña #' . $campana->id }}</span>
                                @endif
                                <span @class(['shrink-0 rounded-md px-2 py-0.5 text-xs font-medium', 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' => $faltan <= 1, 'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => $faltan > 1])>
                                    {{ $faltan === 0 ? 'Vence hoy' : ($faltan === 1 ? 'Mañana' : "En {$faltan} días") }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </div>
</div>
