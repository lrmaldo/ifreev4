@php
    $kpis = [
        ['clave' => 'conectados', 'etiqueta' => 'Conectados ahora', 'nota' => 'activos en los últimos ' . \App\Services\EventoEnVivoService::MINUTOS_ACTIVO . ' min', 'vivo' => true],
        ['clave' => 'dispositivosHoy', 'etiqueta' => 'Dispositivos hoy', 'nota' => 'equipos únicos conectados'],
        ['clave' => 'registrosHoy', 'etiqueta' => 'Registros hoy', 'nota' => 'personas que llenaron el formulario'],
        ['clave' => 'impresionesHoy', 'etiqueta' => 'Anuncios vistos hoy', 'nota' => ($stats['clicsHoy'] ?? 0) . ' clics en anuncios'],
    ];
    $horas = $stats['llegadasPorHora'] ?? [];
    $maxHora = max(1, collect($horas)->max('total') ?? 0);
    $horaPico = collect($horas)->sortByDesc('total')->first();
    $plataformas = $stats['plataformas'] ?? ['android' => 0, 'iphone' => 0, 'otros' => 0];
    $totalPlataformas = max(1, array_sum($plataformas));
    $series = [
        ['clave' => 'android', 'nombre' => 'Android', 'color' => 'var(--serie-1)'],
        ['clave' => 'iphone', 'nombre' => 'iPhone', 'color' => 'var(--serie-2)'],
        ['clave' => 'otros', 'nombre' => 'Otros', 'color' => 'var(--serie-3)'],
    ];
@endphp
<div class="pev" wire:poll.5s="actualizar" x-data="rifa" @keydown.window="tecla($event)">
    <style>
        :root {
            --fondo: #071229;
            --superficie: #0f2148;
            --superficie-2: #15295a;
            --borde: rgba(255, 255, 255, .08);
            --texto-1: #ffffff;
            --texto-2: #c7d2ea;
            --texto-3: #8fa0c4;
            --oro: #f5b82e;
            --oro-2: #ffd56b;
            --vivo: #2fd17c;
            /* Paleta categórica validada contra --superficie (scripts/validate_palette.js, modo oscuro) */
            --serie-1: #3987e5;
            --serie-2: #d95926;
            --serie-3: #199e70;
        }
        /* Fuente de la marca Expo MX-ISP (OFL), local para no depender de internet en el evento */
        @font-face { font-family: 'Plus Jakarta Sans'; font-style: normal; font-weight: 800 900; font-display: swap; src: url('{{ asset('fonts/plus-jakarta-sans-800-latin.woff2') }}') format('woff2'); }
        * { box-sizing: border-box; }
        [x-cloak] { display: none !important; }
        html, body.pev-body { margin: 0; min-height: 100%; background: var(--fondo); }
        .pev {
            min-height: 100vh; padding: clamp(16px, 2vw, 40px); color: var(--texto-1);
            font-family: 'Poppins', 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(ellipse at top, #13306b 0%, var(--fondo) 60%);
            display: grid; grid-template-rows: auto auto 1fr auto auto; gap: clamp(12px, 1.6vw, 28px);
        }
        .pev-cabecera { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .pev-logo { height: clamp(36px, 4vw, 72px); width: auto; }
        .pev-titulo { text-align: center; flex: 1; }
        .pev-titulo h1 { margin: 0; font-size: clamp(18px, 2vw, 40px); font-weight: 700; letter-spacing: .02em; }
        .pev-titulo p { margin: 4px 0 0; color: var(--texto-2); font-size: clamp(12px, 1vw, 20px); }
        .pev-evento { display: flex; align-items: center; gap: clamp(10px, 1vw, 20px); }
        .pev-evento-texto { text-align: right; }
        /* "EXPO MX-ISP 2026" como en wisp.mx: es texto con estilo, no imagen */
        .pev-marca { display: inline-flex; align-items: center; gap: clamp(4px, .4vw, 10px); font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif; font-weight: 900; line-height: 1; }
        .pev-marca-expo { writing-mode: vertical-rl; transform: rotate(180deg); font-size: clamp(8px, .7vw, 15px); letter-spacing: .16em; }
        .pev-marca-titulo { font-size: clamp(22px, 2.6vw, 54px); letter-spacing: -.04em; }
        .pev-marca-mxisp { background: linear-gradient(#ffd75a, #dbac2f 35%, #e0922e 75%, #cf7818); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; color: transparent; filter: drop-shadow(0 4px 22px rgba(219, 172, 47, .55)); }
        .pev-reloj { display: block; margin-top: 4px; color: var(--texto-2); font-variant-numeric: tabular-nums; font-size: clamp(12px, 1vw, 20px); }

        .pev-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: clamp(10px, 1.2vw, 24px); }
        .pev-tarjeta { background: var(--superficie); border: 1px solid var(--borde); border-radius: 16px; padding: clamp(14px, 1.4vw, 28px); }
        .pev-kpi-etiqueta { display: flex; align-items: center; gap: 8px; color: var(--texto-2); font-size: clamp(12px, 1.05vw, 22px); font-weight: 500; }
        .pev-kpi-valor { display: block; margin-top: 6px; font-size: clamp(40px, 5.2vw, 112px); font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; }
        .pev-kpi-nota { display: block; margin-top: 8px; color: var(--texto-3); font-size: clamp(11px, .8vw, 16px); }
        .pev-kpi--vivo .pev-kpi-valor { color: var(--oro); }
        .pev-punto { width: 10px; height: 10px; border-radius: 50%; background: var(--vivo); box-shadow: 0 0 0 0 rgba(47, 209, 124, .7); animation: pev-latido 1.8s infinite; }
        @keyframes pev-latido { 70% { box-shadow: 0 0 0 10px rgba(47, 209, 124, 0); } 100% { box-shadow: 0 0 0 0 rgba(47, 209, 124, 0); } }

        .pev-centro { display: grid; grid-template-columns: 2fr 1fr; gap: clamp(10px, 1.2vw, 24px); min-height: 0; }
        .pev-columna { display: grid; grid-template-rows: auto 1fr; gap: clamp(10px, 1.2vw, 24px); min-height: 0; }
        .pev-tarjeta h2 { margin: 0 0 4px; font-size: clamp(14px, 1.2vw, 26px); font-weight: 600; }
        .pev-subtitulo { margin: 0 0 16px; color: var(--texto-3); font-size: clamp(11px, .85vw, 17px); }

        .pev-grafica { display: flex; flex-direction: column; height: 100%; min-height: 220px; }
        .pev-columnas { flex: 1; display: flex; align-items: flex-end; gap: 2px; border-bottom: 1px solid var(--texto-3); padding-top: 28px; }
        .pev-hora { flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; position: relative; }
        .pev-barra { width: 100%; max-width: 24px; min-height: 2px; background: var(--oro); border-radius: 4px 4px 0 0; transition: height .6s ease; }
        .pev-hora--actual .pev-barra { background: var(--oro-2); }
        .pev-valor-barra { position: absolute; transform: translateY(-120%); color: var(--texto-1); font-size: clamp(11px, .9vw, 18px); font-weight: 600; white-space: nowrap; }
        .pev-tip { position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%); padding: 6px 10px; border-radius: 6px; background: #000; color: #fff; font-size: 13px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .15s; z-index: 2; }
        .pev-hora:hover .pev-tip { opacity: 1; }
        .pev-ejes { display: flex; gap: 2px; margin-top: 6px; }
        .pev-ejes span { flex: 1; text-align: center; color: var(--texto-3); font-size: clamp(9px, .75vw, 15px); }
        .pev-vacio { flex: 1; display: grid; place-items: center; color: var(--texto-3); font-size: clamp(13px, 1vw, 20px); }

        .pev-apilada { display: flex; gap: 2px; height: 24px; }
        .pev-apilada span { height: 100%; transition: width .6s ease; }
        .pev-apilada span:first-child { border-radius: 4px 0 0 4px; }
        .pev-apilada span:last-child { border-radius: 0 4px 4px 0; }
        .pev-apilada span:only-child { border-radius: 4px; }
        .pev-leyenda { display: grid; gap: 8px; margin: 16px 0 0; padding: 0; list-style: none; }
        .pev-leyenda li { display: flex; align-items: center; gap: 10px; color: var(--texto-2); font-size: clamp(12px, 1vw, 20px); }
        .pev-leyenda b { margin-left: auto; color: var(--texto-1); font-variant-numeric: tabular-nums; }
        .pev-muestra { width: 12px; height: 12px; border-radius: 3px; flex: none; }

        .pev-feed { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; -webkit-mask-image: linear-gradient(#000 70%, transparent); mask-image: linear-gradient(#000 70%, transparent); }
        .pev-feed li { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; background: var(--superficie-2); font-size: clamp(13px, 1.05vw, 22px); animation: pev-entra .5s ease both; }
        .pev-feed li span { margin-left: auto; color: var(--texto-3); font-size: .8em; }
        @keyframes pev-entra { from { opacity: 0; transform: translateY(-8px); } }

        /* Carrusel de socios: cinta infinita (la lista va duplicada y se desplaza la mitad) */
        .pev-socios { display: flex; align-items: center; gap: clamp(10px, 1vw, 20px); min-width: 0; }
        .pev-socios-titulo { flex: none; color: var(--texto-2); font-size: clamp(11px, .85vw, 17px); font-weight: 600; text-transform: uppercase; letter-spacing: .12em; }
        .pev-cinta { flex: 1; min-width: 0; overflow: hidden; -webkit-mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); }
        .pev-cinta-pista { display: flex; gap: clamp(8px, .8vw, 16px); width: max-content; animation: pev-cinta var(--duracion, 120s) linear infinite; }
        .pev-socio { --alto-socio: clamp(44px, 3.6vw, 72px); flex: none; display: grid; place-items: center; height: var(--alto-socio); overflow: hidden; padding: 0 clamp(10px, .9vw, 18px); border-radius: 10px; background: #fff; }
        .pev-socio img { display: block; max-height: calc(var(--alto-socio) * .72); max-width: clamp(80px, 7vw, 150px); object-fit: contain; }
        @keyframes pev-cinta { to { transform: translateX(calc(-50% - clamp(4px, .4vw, 8px))); } }
        .pev-pie { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: clamp(12px, 1.2vw, 22px) clamp(16px, 1.6vw, 32px); border-radius: 16px; background: linear-gradient(90deg, #b8860b, var(--oro)); color: #1a1300; }
        .pev-pie strong { font-size: clamp(14px, 1.4vw, 30px); }
        .pev-pie p { margin: 0; font-size: clamp(12px, 1vw, 20px); }
        .pev-ganadores { text-align: right; }
        .pev-boton-rifa { border: 0; border-radius: 999px; padding: 10px 18px; background: #1a1300; color: var(--oro); font: inherit; font-weight: 700; cursor: pointer; }
        .pev-boton-rifa:focus-visible, .pev-rifa button:focus-visible, .pev-rifa input:focus-visible { outline: 3px solid var(--oro-2); outline-offset: 2px; }

        .pev-rifa { position: fixed; inset: 0; z-index: 50; display: grid; place-items: center; padding: 16px; background: rgba(3, 8, 20, .92); }
        .pev-rifa-caja { width: min(900px, 100%); text-align: center; position: relative; }
        .pev-rifa h2 { margin: 0; color: var(--oro); font-size: clamp(24px, 3vw, 64px); }
        .pev-rifa p { color: var(--texto-2); font-size: clamp(14px, 1.3vw, 26px); }
        .pev-rifa input { width: min(520px, 100%); margin: 16px auto; display: block; padding: 14px 18px; border-radius: 12px; border: 1px solid var(--borde); background: var(--superficie); color: var(--texto-1); font: inherit; font-size: clamp(14px, 1.2vw, 24px); text-align: center; }
        .pev-rifa .pev-sortear { border: 0; border-radius: 999px; padding: 16px 40px; background: var(--oro); color: #1a1300; font: inherit; font-size: clamp(16px, 1.6vw, 32px); font-weight: 800; cursor: pointer; }
        .pev-rifa .pev-sortear:disabled { opacity: .5; cursor: not-allowed; }
        .pev-rifa .pev-cerrar { margin-top: 20px; border: 1px solid var(--borde); border-radius: 999px; padding: 10px 24px; background: transparent; color: var(--texto-2); font: inherit; cursor: pointer; }
        .pev-ruleta { font-size: clamp(40px, 7vw, 150px); font-weight: 800; min-height: 1.3em; margin: 24px 0; }
        .pev-ganador-nombre { font-size: clamp(48px, 8vw, 170px); font-weight: 800; color: var(--oro); line-height: 1.05; margin: 12px 0; }
        .pev-ganador-tel { font-size: clamp(20px, 2.4vw, 50px); color: var(--texto-1); font-variant-numeric: tabular-nums; }
        .pev-error { color: #ff8a8a !important; }
        .pev-confeti { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
        .pev-confeti i { position: absolute; top: -20px; width: 10px; height: 16px; border-radius: 2px; animation: pev-cae 3.2s linear forwards; }
        @keyframes pev-cae { to { transform: translateY(110vh) rotate(720deg); } }
        .pev-oculto { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

        /* En TV/laptop todo cabe en una sola pantalla, sin scroll */
        @media (min-width: 901px) {
            .pev { height: 100vh; overflow: hidden; }
            .pev-centro > .pev-tarjeta, .pev-columna > .pev-tarjeta { min-height: 0; overflow: hidden; }
            .pev-columna { grid-template-rows: auto minmax(0, 1fr); }
        }
        @media (max-width: 900px) {
            .pev-kpis { grid-template-columns: repeat(2, 1fr); }
            .pev-centro { grid-template-columns: 1fr; }
            .pev-pie { flex-direction: column; text-align: center; }
            .pev-ganadores { text-align: center; }
            .pev-evento { display: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .pev-punto, .pev-feed li, .pev-confeti i, .pev-cinta-pista { animation: none; }
            .pev-barra, .pev-apilada span { transition: none; }
        }
    </style>

    {{-- Cabecera --}}
    <header class="pev-cabecera">
        <img class="pev-logo" src="{{ asset('img/Logo-i-Free.png') }}" alt="i-Free">
        <div class="pev-titulo">
            <h1>Red WiFi oficial · En vivo</h1>
            <p>{{ $zona->nombre }}</p>
        </div>
        <div class="pev-evento">
            @if ($logoWispmx)
                <img class="pev-logo" src="{{ $logoWispmx }}" alt="WISPMX · Asociación Nacional de Proveedores de Internet Inalámbrico">
            @endif
            <div class="pev-evento-texto">
                <span class="pev-marca" aria-label="Expo MX-ISP 2026">
                    <span class="pev-marca-expo" aria-hidden="true">EXPO</span>
                    <span class="pev-marca-titulo" aria-hidden="true"><span class="pev-marca-mxisp">MX-ISP</span> 2026</span>
                </span>
                <span class="pev-reloj" x-data="{ hora: '' }" x-init="const f = () => hora = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }); f(); setInterval(f, 10000)" x-text="hora" wire:ignore></span>
            </div>
        </div>
    </header>

    {{-- Números principales --}}
    <section class="pev-kpis" aria-label="Resumen en vivo">
        @foreach ($kpis as $kpi)
            <div class="pev-tarjeta {{ !empty($kpi['vivo']) ? 'pev-kpi--vivo' : '' }}">
                <span class="pev-kpi-etiqueta">
                    @if (!empty($kpi['vivo']))<span class="pev-punto" aria-hidden="true"></span>@endif
                    {{ $kpi['etiqueta'] }}
                </span>
                <span class="pev-kpi-valor" wire:ignore x-data="contador({{ (int) ($stats[$kpi['clave']] ?? 0) }})" x-effect="animar($wire.stats.{{ $kpi['clave'] }})" x-text="texto">{{ number_format($stats[$kpi['clave']] ?? 0) }}</span>
                <span class="pev-kpi-nota">{{ $kpi['nota'] }}</span>
            </div>
        @endforeach
    </section>

    <section class="pev-centro">
        {{-- Llegadas por hora --}}
        <div class="pev-tarjeta pev-grafica">
            <h2>Llegadas por hora</h2>
            <p class="pev-subtitulo">
                Dispositivos nuevos conectados hoy
                @if ($horaPico && $horaPico['total'] > 0) · hora pico {{ $horaPico['hora'] }} ({{ number_format($horaPico['total']) }}) @endif
            </p>
            @if (count($horas))
                <div class="pev-columnas" role="img" aria-label="Columnas de dispositivos nuevos por hora">
                    @foreach ($horas as $i => $h)
                        @php $esActual = $loop->last; $esPico = $horaPico && $h['hora'] === $horaPico['hora']; @endphp
                        <div class="pev-hora {{ $esActual ? 'pev-hora--actual' : '' }}" wire:key="hora-{{ $h['hora'] }}">
                            <span class="pev-tip">{{ $h['hora'] }} · {{ number_format($h['total']) }} dispositivos</span>
                            @if (($esPico || $esActual) && $h['total'] > 0)
                                <span class="pev-valor-barra" style="bottom: {{ round($h['total'] / $maxHora * 100, 1) }}%">{{ number_format($h['total']) }}</span>
                            @endif
                            <div class="pev-barra" style="height: {{ round($h['total'] / $maxHora * 100, 1) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="pev-ejes" aria-hidden="true">
                    @foreach ($horas as $h)
                        <span>{{ count($horas) > 12 && !$loop->first && !$loop->last && $loop->index % 2 ? '' : substr($h['hora'], 0, 2) }}</span>
                    @endforeach
                </div>
                <table class="pev-oculto">
                    <caption>Dispositivos nuevos por hora</caption>
                    <tr><th>Hora</th><th>Dispositivos</th></tr>
                    @foreach ($horas as $h)<tr><td>{{ $h['hora'] }}</td><td>{{ $h['total'] }}</td></tr>@endforeach
                </table>
            @else
                <div class="pev-vacio">Esperando las primeras conexiones…</div>
            @endif
        </div>

        <div class="pev-columna">
            {{-- Plataformas --}}
            <div class="pev-tarjeta">
                <h2>Dispositivos por plataforma</h2>
                <p class="pev-subtitulo">Hoy</p>
                <div class="pev-apilada" role="img" aria-label="Android {{ $plataformas['android'] }}, iPhone {{ $plataformas['iphone'] }}, otros {{ $plataformas['otros'] }}">
                    @foreach ($series as $s)
                        @if ($plataformas[$s['clave']] > 0)
                            <span title="{{ $s['nombre'] }}: {{ number_format($plataformas[$s['clave']]) }}" style="width: {{ $plataformas[$s['clave']] / $totalPlataformas * 100 }}%; background: {{ $s['color'] }}"></span>
                        @endif
                    @endforeach
                </div>
                <ul class="pev-leyenda">
                    @foreach ($series as $s)
                        <li>
                            <span class="pev-muestra" style="background: {{ $s['color'] }}"></span>
                            {{ $s['nombre'] }}
                            <b>{{ number_format($plataformas[$s['clave']]) }} · {{ round($plataformas[$s['clave']] / $totalPlataformas * 100) }}%</b>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Últimos registros --}}
            <div class="pev-tarjeta">
                <h2>Últimos registros</h2>
                @if (count($stats['ultimosRegistros'] ?? []))
                    <ul class="pev-feed">
                        @foreach ($stats['ultimosRegistros'] as $r)
                            <li wire:key="registro-{{ $r['id'] }}">👋 {{ $r['nombre'] }} <span>{{ $r['hace'] }}</span></li>
                        @endforeach
                    </ul>
                @else
                    <p class="pev-subtitulo">Aún no hay registros.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Socios de WISPMX --}}
    @if (count($socios))
        <section class="pev-socios" aria-label="Socios de WISPMX">
            <span class="pev-socios-titulo">Socios WISPMX</span>
            <div class="pev-cinta" wire:ignore>
                <div class="pev-cinta-pista" style="--duracion: {{ max(30, count($socios) * 3) }}s">
                    @foreach ([false, true] as $copia)
                        @foreach ($socios as $socio)
                            <div class="pev-socio" @if ($copia) aria-hidden="true" @endif>
                                <img src="{{ $socio['url'] }}" alt="{{ $copia ? '' : $socio['nombre'] }}" decoding="async">
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Llamado a la acción + rifa --}}
    <footer class="pev-pie">
        <div>
            <strong>🎁 Conéctate al WiFi, regístrate y participa en la rifa</strong>
            <p>{{ number_format($stats['registrosTotal'] ?? 0) }} personas registradas</p>
        </div>
        <div class="pev-ganadores">
            @if ($ganadores->isNotEmpty())
                <p>🏆 {{ $ganadores->map(fn ($g) => $g->nombre . ($g->premio ? " ({$g->premio})" : ''))->join(' · ') }}</p>
            @endif
            <button type="button" class="pev-boton-rifa" @click="abrir()">Rifa (R)</button>
        </div>
    </footer>

    {{-- Sorteo --}}
    <div class="pev-rifa" x-show="abierta" x-cloak x-transition.opacity wire:ignore role="dialog" aria-modal="true" aria-label="Rifa">
        <div class="pev-rifa-caja">
            <template x-if="fase === 'listo'">
                <div>
                    <h2>🎁 Rifa en vivo</h2>
                    <p x-text="participantes === null ? 'Contando participantes…' : participantes.toLocaleString('es-MX') + ' participantes con teléfono registrado'"></p>
                    <input type="text" x-model="premio" maxlength="120" placeholder="Premio (opcional)" @keydown.enter="sortear()">
                    <button type="button" class="pev-sortear" @click="sortear()" :disabled="!participantes">Sortear</button>
                    <p class="pev-error" x-show="error" x-text="error"></p>
                </div>
            </template>
            <template x-if="fase === 'girando'">
                <div>
                    <h2>🎁 Sorteando…</h2>
                    <div class="pev-ruleta" x-text="nombreActual" aria-live="off"></div>
                </div>
            </template>
            <template x-if="fase === 'ganador'">
                <div aria-live="assertive">
                    <h2>🏆 ¡Felicidades!</h2>
                    <div class="pev-ganador-nombre" x-text="ganador.nombre"></div>
                    <div class="pev-ganador-tel">Teléfono terminación <b x-text="ganador.telefono_final"></b></div>
                    <p x-show="ganador.premio" x-text="'Premio: ' + ganador.premio"></p>
                    <p>Pasa al stand de i-Free para reclamar tu premio</p>
                    <div class="pev-confeti" aria-hidden="true">
                        <template x-for="c in confeti" :key="c.id">
                            <i :style="`left:${c.x}%; background:${c.color}; animation-delay:${c.retraso}s`"></i>
                        </template>
                    </div>
                    <button type="button" class="pev-sortear" @click="fase = 'listo'">Otro sorteo</button>
                </div>
            </template>
            <button type="button" class="pev-cerrar" @click="cerrar()" x-show="fase !== 'girando'">Cerrar (Esc)</button>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('contador', (inicial = 0) => ({
                mostrado: inicial,
                raf: null,
                get texto() { return this.mostrado.toLocaleString('es-MX'); },
                animar(destino) {
                    destino = Number(destino) || 0;
                    const desde = this.mostrado, inicio = performance.now(), duracion = 900;
                    if (destino === desde) return;
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.mostrado = destino; return; }
                    cancelAnimationFrame(this.raf);
                    const paso = (t) => {
                        const p = Math.min(1, (t - inicio) / duracion);
                        this.mostrado = Math.round(desde + (destino - desde) * (1 - Math.pow(1 - p, 3)));
                        if (p < 1) this.raf = requestAnimationFrame(paso);
                    };
                    this.raf = requestAnimationFrame(paso);
                },
            }));

            Alpine.data('rifa', () => ({
                abierta: false,
                fase: 'listo',
                premio: '',
                participantes: null,
                nombreActual: '',
                ganador: null,
                error: '',
                confeti: [],
                tecla(e) {
                    if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;
                    if (e.key.toLowerCase() === 'r' && !this.abierta) this.abrir();
                    if (e.key === 'Escape') this.cerrar();
                },
                async abrir() {
                    this.abierta = true;
                    this.fase = 'listo';
                    this.error = '';
                    this.participantes = null;
                    this.participantes = await this.$wire.contarParticipantes();
                },
                cerrar() {
                    if (this.fase !== 'girando') this.abierta = false;
                },
                async sortear() {
                    if (!this.participantes || this.fase === 'girando') return;
                    this.fase = 'girando';
                    this.error = '';
                    let r;
                    try {
                        r = await this.$wire.sortear(this.premio);
                    } catch (e) {
                        this.fase = 'listo';
                        this.error = 'No se pudo hacer el sorteo. Revisa la conexión e intenta de nuevo.';
                        return;
                    }
                    if (!r.ganador) {
                        this.fase = 'listo';
                        this.error = 'No hay participantes con teléfono registrado.';
                        return;
                    }
                    const nombres = r.nombres.length ? r.nombres : [r.ganador.nombre];
                    const duracion = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 4500;
                    const inicio = Date.now();
                    let i = 0;
                    await new Promise((listo) => {
                        const giro = () => {
                            const t = Date.now() - inicio;
                            if (t >= duracion) return listo();
                            this.nombreActual = nombres[i++ % nombres.length];
                            setTimeout(giro, 50 + Math.pow(t / duracion, 3) * 400);
                        };
                        giro();
                    });
                    this.ganador = r.ganador;
                    this.participantes = r.participantes - 1;
                    const colores = ['#f5b82e', '#ffd56b', '#3987e5', '#ffffff', '#d95926'];
                    this.confeti = Array.from({ length: 60 }, (_, id) => ({ id, x: Math.random() * 100, retraso: Math.random() * 1.2, color: colores[id % colores.length] }));
                    this.premio = '';
                    this.fase = 'ganador';
                },
            }));
        });
    </script>
</div>
