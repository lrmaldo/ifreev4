@php
    $n = fn ($v) => number_format((int) $v);
    $maxHora = max(1, collect($c['por_hora'])->max('total') ?? 1);
    $pico = collect($c['por_hora'])->sortByDesc('total')->first();
    $maxEstado = max(1, collect($c['estados'])->max('total') ?? 1);
    $estadosTop = collect($c['estados'])->where('nombre', '!=', 'Otros')->take(5)->pluck('nombre')->values();
    $socios = count(\App\Support\SociosEvento::lista());
    $colores = ['#ff3f00', '#ffb020', '#3fa7ff', '#9b7bff', '#5b6478'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resultados {{ $caso['titulo'] }} · i-Free by Sattlink</title>
    <meta name="description" content="{{ $n($c['registros']) }} personas se registraron en el WiFi oficial de {{ $caso['titulo'] }}. Así funcionó i-Free, el portal cautivo con métricas en vivo.">
    <meta property="og:title" content="{{ $caso['titulo'] }}: {{ $n($c['registros']) }} registros en el WiFi oficial">
    <meta property="og:description" content="Resultados reales del portal cautivo i-Free by Sattlink en {{ $caso['titulo'] }}.">
    <meta property="og:image" content="{{ asset('img/Logo i-Free.png') }}">
    <meta name="theme-color" content="#0b0f19">
    <link rel="icon" href="{{ asset('img/Logo i-Free.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <style>
        :root {
            --tinta: #0b0f19; --tinta-2: #121a2c; --tinta-3: #1b2540; --linea: rgba(255,255,255,.09);
            --texto: #f3f5fa; --suave: #a9b3c9; --naranja: #ff3f00; --naranja-2: #ff8a55; --oro: #ffb020;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: var(--tinta); color: var(--texto); font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        .contenedor { width: min(1120px, 100% - 32px); margin-inline: auto; }
        section { padding: 88px 0; position: relative; }
        .ceja { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--naranja-2); }
        .ceja::before { content: ''; width: 18px; height: 2px; background: currentColor; border-radius: 2px; }
        h1, h2, h3 { margin: 0; line-height: 1.08; letter-spacing: -.02em; }
        h2 { font-size: clamp(28px, 4vw, 44px); margin-top: 12px; }
        .lead { color: var(--suave); font-size: clamp(16px, 1.6vw, 19px); max-width: 640px; margin: 16px 0 0; }
        .naranja { color: var(--naranja); }
        .degradado { background: linear-gradient(90deg, var(--naranja), var(--naranja-2), var(--oro), var(--naranja)); background-size: 300% auto; -webkit-background-clip: text; background-clip: text; color: transparent; animation: brillo 8s linear infinite; }
        @keyframes brillo { to { background-position: 300% center; } }

        /* Barra superior */
        .barra { position: sticky; top: 0; z-index: 20; backdrop-filter: blur(12px); background: rgba(11,15,25,.72); border-bottom: 1px solid var(--linea); }
        .barra .contenedor { display: flex; align-items: center; justify-content: space-between; gap: 12px; height: 64px; }
        .marcas { display: flex; align-items: center; gap: 14px; }
        .marcas img { height: 30px; width: auto; display: block; }
        .logo-ifree { filter: brightness(0) invert(1); }
        .marcas .x { color: var(--suave); font-size: 13px; }
        .acciones { display: flex; gap: 8px; }
        .boton { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 18px; border-radius: 999px; font-weight: 600; font-size: 15px; text-decoration: none; border: 1px solid transparent; cursor: pointer; font-family: inherit; transition: transform .2s, box-shadow .2s, background .2s; }
        .boton:hover { transform: translateY(-1px); }
        .boton-primario { background: var(--naranja); color: #fff; box-shadow: 0 10px 30px -10px rgba(255,63,0,.7); }
        .boton-secundario { background: transparent; color: var(--texto); border-color: rgba(255,255,255,.22); }
        .boton-chico { padding: 8px 14px; font-size: 14px; }

        /* Hero */
        .hero { padding: 72px 0 96px; overflow: hidden; background: radial-gradient(900px 520px at 85% 0%, rgba(255,63,0,.26), transparent 60%), radial-gradient(700px 480px at 0% 100%, rgba(255,176,32,.10), transparent 60%); }
        .malla { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px); background-size: 48px 48px; mask-image: radial-gradient(ellipse at 70% 30%, #000 25%, transparent 72%); pointer-events: none; }
        .hero-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 48px; align-items: center; position: relative; }
        .hero h1 { font-size: clamp(38px, 6vw, 72px); margin-top: 18px; }
        .hero h1 .cifra { display: block; font-size: 1.45em; }
        .hero .lead { margin-top: 22px; }
        .hero-botones { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 32px; }
        .lugar { margin-top: 28px; display: flex; flex-wrap: wrap; gap: 8px 18px; color: var(--suave); font-size: 14px; }
        .lugar span::before { content: '●'; color: var(--naranja); margin-right: 8px; font-size: 9px; vertical-align: middle; }

        /* Animación del hero: el router reparte WiFi y llegan los registros */
        .escenario { position: relative; aspect-ratio: 1; max-width: 440px; margin-inline: auto; width: 100%; }
        .onda { position: absolute; inset: 50%; width: 0; height: 0; border-radius: 50%; border: 2px solid rgba(255,63,0,.55); transform: translate(-50%,-50%); animation: onda 4s ease-out infinite; }
        .onda:nth-child(2) { animation-delay: 1.33s; } .onda:nth-child(3) { animation-delay: 2.66s; }
        @keyframes onda { from { width: 0; height: 0; opacity: 1; } to { width: 100%; height: 100%; opacity: 0; } }
        .router { position: absolute; left: 50%; top: 50%; width: 92px; height: 92px; transform: translate(-50%,-50%); border-radius: 26px; background: linear-gradient(145deg, var(--naranja), #c22f00); display: grid; place-items: center; box-shadow: 0 0 0 10px rgba(255,63,0,.12), 0 30px 60px -20px rgba(255,63,0,.8); z-index: 2; }
        .router svg { width: 46px; height: 46px; }
        .punto { position: absolute; width: 12px; height: 12px; border-radius: 50%; background: #fff; box-shadow: 0 0 12px rgba(255,255,255,.8); animation: llegar 5s ease-in infinite; opacity: 0; }
        @keyframes llegar { 0% { opacity: 0; transform: translate(0,0) scale(1); } 10% { opacity: 1; } 75% { opacity: 1; } 100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(.3); } }
        .ticket { position: absolute; right: -4%; display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 14px; background: rgba(27,37,64,.92); border: 1px solid var(--linea); box-shadow: 0 20px 40px -20px rgba(0,0,0,.8); font-size: 13px; white-space: nowrap; opacity: 0; animation: ticket 9s ease-in-out infinite; }
        .ticket b { display: block; font-size: 14px; }
        .ticket small { color: var(--suave); }
        .ticket .ico { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: rgba(255,63,0,.18); color: var(--naranja-2); font-size: 15px; }
        .ticket-1 { top: 6%; } .ticket-2 { top: 72%; left: -6%; right: auto; animation-delay: 3s; } .ticket-3 { top: 24%; left: -4%; right: auto; animation-delay: 6s; }
        @keyframes ticket { 0%, 100% { opacity: 0; transform: translateY(12px); } 8%, 30% { opacity: 1; transform: none; } 38% { opacity: 0; transform: translateY(-12px); } }

        /* Cifras principales */
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 40px; }
        .kpi { padding: 26px; border-radius: 22px; background: linear-gradient(180deg, var(--tinta-2), rgba(18,26,44,.6)); border: 1px solid var(--linea); position: relative; overflow: hidden; }
        .kpi::after { content: ''; position: absolute; right: -40px; top: -40px; width: 120px; height: 120px; border-radius: 50%; background: radial-gradient(circle, rgba(255,63,0,.18), transparent 70%); }
        .kpi .valor { font-size: clamp(36px, 4.4vw, 54px); font-weight: 700; letter-spacing: -.03em; line-height: 1; }
        .kpi .valor small { font-size: .45em; color: var(--suave); font-weight: 600; margin-left: 2px; }
        .kpi .etiqueta { margin-top: 10px; font-weight: 600; }
        .kpi .nota { margin-top: 4px; font-size: 14px; color: var(--suave); }

        /* Cómo funciona */
        .pasos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 44px; position: relative; }
        .pasos::before { content: ''; position: absolute; top: 31px; left: 12%; right: 12%; height: 2px; background: linear-gradient(90deg, rgba(255,63,0,.1), rgba(255,63,0,.6), rgba(255,63,0,.1)); }
        .pasos::after { content: ''; position: absolute; top: 26px; left: 12%; width: 12px; height: 12px; border-radius: 50%; background: var(--oro); box-shadow: 0 0 16px var(--oro); animation: viaje 6s ease-in-out infinite; }
        @keyframes viaje { 0% { left: 12%; } 100% { left: calc(88% - 12px); } }
        .paso { text-align: center; position: relative; z-index: 1; }
        .paso .num { width: 64px; height: 64px; margin: 0 auto; border-radius: 20px; display: grid; place-items: center; background: var(--tinta-3); border: 1px solid rgba(255,63,0,.4); font-size: 26px; }
        .paso h3 { font-size: 18px; margin-top: 16px; }
        .paso p { color: var(--suave); font-size: 15px; margin: 6px 0 0; }

        /* Gráficas */
        .panel { margin-top: 40px; padding: 28px; border-radius: 24px; background: var(--tinta-2); border: 1px solid var(--linea); }
        .dos { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .dos .panel { margin-top: 0; }
        .panel h3 { font-size: 18px; }
        .panel .sub { color: var(--suave); font-size: 14px; margin-top: 4px; }
        .horas { display: flex; align-items: flex-end; gap: 6px; height: 220px; margin-top: 28px; padding-bottom: 26px; position: relative; }
        .hora { flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; position: relative; }
        .hora .b { width: 100%; max-width: 38px; height: var(--v); border-radius: 8px 8px 3px 3px; background: linear-gradient(180deg, #ff6a33, rgba(255,63,0,.45)); transform-origin: bottom; }
        .hora.pico .b { background: linear-gradient(180deg, var(--oro), #ff7a1a); box-shadow: 0 0 24px rgba(255,176,32,.5); }
        .hora .h { position: absolute; bottom: -24px; font-size: 12px; color: var(--suave); }
        .hora .t { font-size: 12px; font-weight: 600; margin-bottom: 4px; opacity: .85; }
        .estado { display: grid; grid-template-columns: 150px 1fr 44px; gap: 12px; align-items: center; margin-top: 14px; font-size: 15px; }
        .estado .pista { height: 12px; border-radius: 99px; background: rgba(255,255,255,.06); overflow: hidden; }
        .estado .relleno { display: block; height: 100%; width: var(--v); border-radius: 99px; background: linear-gradient(90deg, var(--naranja), var(--oro)); transform-origin: left; }
        .estado .num { text-align: right; font-weight: 600; }
        .apilada { display: flex; height: 18px; border-radius: 99px; overflow: hidden; margin-top: 24px; gap: 3px; }
        .apilada span { width: var(--v); background: var(--color); transform-origin: left; }
        .leyenda { display: flex; flex-wrap: wrap; gap: 10px 22px; margin-top: 16px; font-size: 15px; }
        .leyenda i { display: inline-block; width: 11px; height: 11px; border-radius: 3px; background: var(--color); margin-right: 8px; }
        .leyenda b { margin-left: 6px; }
        .mini-kpis { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-top: 22px; }
        .mini { padding: 18px; border-radius: 16px; background: rgba(255,255,255,.04); border: 1px solid var(--linea); }
        .mini .valor { font-size: 32px; font-weight: 700; letter-spacing: -.02em; }
        .mini .etiqueta { color: var(--suave); font-size: 14px; }

        /* Para quién */
        .tarjetas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 44px; }
        .tarjeta { padding: 28px; border-radius: 24px; background: linear-gradient(180deg, var(--tinta-2), var(--tinta)); border: 1px solid var(--linea); transition: transform .3s, border-color .3s; }
        .tarjeta:hover { transform: translateY(-4px); border-color: rgba(255,63,0,.45); }
        .tarjeta .ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: rgba(255,63,0,.14); font-size: 22px; }
        .tarjeta h3 { font-size: 21px; margin-top: 18px; }
        .tarjeta ul { margin: 14px 0 0; padding: 0; list-style: none; color: var(--suave); }
        .tarjeta li { padding-left: 24px; position: relative; margin-top: 9px; }
        .tarjeta li::before { content: '✓'; position: absolute; left: 0; color: var(--naranja-2); font-weight: 700; }

        /* Demo */
        .demo { background: radial-gradient(800px 400px at 15% 0%, rgba(255,63,0,.2), transparent 60%); }
        .demo-grid { display: grid; grid-template-columns: .9fr 1.1fr; gap: 48px; align-items: start; }
        form { padding: 28px; border-radius: 24px; background: var(--tinta-2); border: 1px solid var(--linea); display: grid; gap: 14px; }
        .fila { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        label { display: grid; gap: 6px; font-size: 14px; font-weight: 600; }
        input, select, textarea { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,.14); background: var(--tinta); color: var(--texto); font: inherit; font-size: 16px; }
        input:focus, select:focus, textarea:focus { outline: 2px solid var(--naranja); outline-offset: 1px; border-color: transparent; }
        .error { color: #ff8a8a; font-size: 13px; font-weight: 500; }
        .trampa { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
        .exito { padding: 22px; border-radius: 18px; background: rgba(46,204,113,.12); border: 1px solid rgba(46,204,113,.4); }
        .contacto-alterno { margin-top: 22px; color: var(--suave); font-size: 15px; }
        .aviso { font-size: 12px; color: var(--suave); margin: 0; }

        footer { padding: 36px 0 48px; border-top: 1px solid var(--linea); color: var(--suave); font-size: 14px; }
        footer .contenedor { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; }

        /* Cifras que cuentan desde cero: el texto real reserva el ancho y la cuenta va encima */
        @media screen {
            [data-mostrar] { position: relative; -webkit-text-fill-color: transparent; }
            [data-mostrar]::after { content: attr(data-mostrar); position: absolute; left: 0; top: 0; -webkit-text-fill-color: currentColor; }
            .degradado[data-mostrar]::after { -webkit-text-fill-color: transparent; }
        }

        /* El hero entra solo al cargar, sin esperar al scroll */
        [data-entrada] { animation: entrada .9s cubic-bezier(.2,.7,.2,1) both; animation-delay: var(--retraso, 0s); }
        @keyframes entrada { from { opacity: 0; transform: translateY(24px); } }

        /* Aparecer al hacer scroll (solo si hay JavaScript) */
        .js [data-reveal] { opacity: 0; transform: translateY(26px); transition: opacity .8s ease, transform .8s cubic-bezier(.2,.7,.2,1); transition-delay: var(--retraso, 0s); }
        .js [data-reveal].visible { opacity: 1; transform: none; }
        .js .crecer .b, .js .crecer .relleno, .js .crecer .apilada span { transform: scale(0, 1); }
        .js .crecer .b { transform: scale(1, 0); }
        .js .crecer.visible .b, .js .crecer.visible .relleno, .js .crecer.visible .apilada span { transform: none; transition: transform 1.1s cubic-bezier(.2,.7,.2,1); transition-delay: calc(var(--i, 0) * 60ms); }

        @media (max-width: 900px) {
            .hero-grid, .demo-grid, .dos { grid-template-columns: 1fr; }
            .escenario { max-width: 320px; }
            .kpis, .tarjetas { grid-template-columns: 1fr 1fr; }
            .pasos { grid-template-columns: 1fr 1fr; row-gap: 32px; }
            .pasos::before, .pasos::after { display: none; }
        }
        @media (max-width: 600px) {
            section { padding: 64px 0; }
            .kpis, .tarjetas, .fila { grid-template-columns: 1fr; }
            .marcas .wisp, .marcas .x, .acciones .boton-secundario { display: none; }
            .estado { grid-template-columns: 110px 1fr 36px; font-size: 14px; }
            .horas { gap: 3px; } .hora .t { display: none; } .hora:nth-child(even) .h { display: none; }
            .ticket { font-size: 12px; } .ticket-2 { left: -2%; } .ticket-1, .ticket-3 { right: -2%; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .js [data-reveal], .js .crecer .b, .js .crecer .relleno, .js .crecer .apilada span { opacity: 1; transform: none; }
        }

        /* PDF (Imprimir → Guardar como PDF): fondo oscuro conservado, sin animaciones ni formulario */
        @media print {
            @page { size: A4; margin: 10mm; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; animation: none !important; transition: none !important; }
            .barra, .hero-botones, form, .solo-pantalla, .escenario .punto, .ticket { display: none !important; }
            [data-reveal], .crecer .b, .crecer .relleno, .crecer .apilada span { opacity: 1 !important; transform: none !important; }
            section { padding: 28px 0; break-inside: avoid; }
            .hero { padding-top: 24px; }
            .panel, .kpi, .tarjeta { break-inside: avoid; }
            .demo-grid { grid-template-columns: 1fr; }
            .solo-pdf { display: block !important; }
            .degradado { background: none; color: var(--naranja); -webkit-text-fill-color: var(--naranja); }
        }
        .solo-pdf { display: none; }
    </style>
</head>
<body>
<script>document.documentElement.classList.add('js');</script>

<header class="barra">
    <div class="contenedor">
        <div class="marcas">
            <img class="logo-ifree" src="{{ asset('img/evento/portal/logo-ifree.webp') }}" alt="i-Free by Sattlink" width="30" height="30">
            <span class="x">×</span>
            <img class="wisp" src="{{ asset('img/evento/portal/logo-wispmx.webp') }}" alt="{{ $caso['organizador'] }}" width="55" height="30">
        </div>
        <div class="acciones">
            <button type="button" class="boton boton-secundario boton-chico" onclick="window.print()">Guardar PDF</button>
            <a class="boton boton-primario boton-chico" href="#demo">Solicita una demo</a>
        </div>
    </div>
</header>

<main>
    {{-- 1. Hero --}}
    <section class="hero">
        <div class="malla"></div>
        <div class="contenedor hero-grid">
            <div>
                <span class="ceja" data-entrada>Resultados · WiFi oficial de {{ $caso['titulo'] }}</span>
                <h1 data-entrada style="--retraso:.1s">
                    <span class="cifra degradado" data-contar="{{ $c['registros'] }}">{{ $n($c['registros']) }}</span>
                    personas se conectaron y nos dejaron sus datos.
                </h1>
                <p class="lead" data-entrada style="--retraso:.2s">
                    Cada asistente que quiso internet en la Expo pasó por el portal de i-Free: vio a los patrocinadores,
                    se registró y navegó. Todo quedó medido en tiempo real. Estos son los números.
                </p>
                <div class="hero-botones" data-entrada style="--retraso:.3s">
                    <a class="boton boton-primario" href="#demo">Quiero esto en mi red o evento</a>
                    <a class="boton boton-secundario" href="#resultados">Ver los resultados</a>
                </div>
                <div class="lugar" data-entrada style="--retraso:.4s">
                    <span>{{ $caso['fechas'] }}</span>
                    <span>{{ $caso['lugar'] }}</span>
                    <span>Organiza {{ $caso['organizador'] }}</span>
                </div>
            </div>

            <div class="escenario" aria-hidden="true">
                <span class="onda"></span><span class="onda"></span><span class="onda"></span>
                <div class="router">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1" fill="#fff"/></svg>
                </div>
                @foreach ([[8, 14, '170px', '150px', 0], [86, 10, '-150px', '160px', 1.2], [92, 78, '-170px', '-120px', 2.4], [10, 84, '160px', '-150px', 3.1], [50, 2, '0px', '190px', 1.8], [48, 96, '0px', '-190px', 3.8]] as [$x, $y, $dx, $dy, $d])
                    <span class="punto" style="left:{{ $x }}%;top:{{ $y }}%;--dx:{{ $dx }};--dy:{{ $dy }};animation-delay:{{ $d }}s"></span>
                @endforeach
                @foreach ([0, 1, 2] as $i)
                    <div class="ticket ticket-{{ $i + 1 }}">
                        <span class="ico">{{ ['✓', '📍', '📊'][$i] }}</span>
                        <span>
                            <b>{{ ['Nuevo registro', 'Llegó desde ' . ($estadosTop[$i] ?? 'CDMX'), $n($c['impresiones']) . ' anuncios vistos'][$i] }}</b>
                            <small>{{ ['Aviso al instante por Telegram', 'Mapa de asistentes por estado', 'Métricas en vivo para patrocinadores'][$i] }}</small>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 2. Cifras --}}
    <section id="resultados">
        <div class="contenedor">
            <span class="ceja" data-reveal>Lo que pasó en {{ $caso['fechas'] }}</span>
            <h2 data-reveal>Un WiFi gratis que se volvió una <span class="naranja">base de datos</span> y un <span class="naranja">canal de publicidad</span>.</h2>
            <div class="kpis">
                @foreach ([
                    [$c['dispositivos'], '', 'Celulares conectados', 'Dispositivos únicos que abrieron el portal'],
                    [$c['registros'], '', 'Registros con datos', 'Nombre, teléfono, correo y estado de cada persona'],
                    [$c['impresiones'], '', 'Anuncios vistos', 'Cada visita mostró a los patrocinadores antes de navegar'],
                    [$c['conversion'], '%', 'Se registró', 'De los celulares nuevos, este porcentaje dejó sus datos'],
                    [$c['segundos_vista'], 's', 'Viendo publicidad', 'Tiempo promedio frente al anuncio en cada visita'],
                    [$c['con_empresa'] ?: $c['recurrentes'], '', $c['con_empresa'] ? 'Dejaron su empresa' : 'Regresaron', $c['con_empresa'] ? 'Contactos de la industria listos para seguimiento' : 'Celulares que volvieron a conectarse'],
                ] as $i => [$valor, $unidad, $etiqueta, $nota])
                    <div class="kpi" data-reveal style="--retraso:{{ $i * .08 }}s">
                        <div class="valor"><span data-contar="{{ $valor }}">{{ $n($valor) }}</span>@if ($unidad)<small>{{ $unidad }}</small>@endif</div>
                        <div class="etiqueta">{{ $etiqueta }}</div>
                        <div class="nota">{{ $nota }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 3. Cómo funciona --}}
    <section>
        <div class="contenedor">
            <span class="ceja" data-reveal>Cómo funciona</span>
            <h2 data-reveal>Cuatro pasos en menos de <span class="naranja">un minuto</span>.</h2>
            <p class="lead" data-reveal>Funciona con el MikroTik que ya tienes. No hay que instalar nada en los celulares.</p>
            <div class="pasos">
                @foreach ([
                    ['📶', 'Se conecta', 'Elige el WiFi del evento o del negocio y se abre el portal.'],
                    ['📣', 'Ve los anuncios', 'Video o carrusel de patrocinadores, con tu marca y la del evento.'],
                    ['📝', 'Deja sus datos', 'Formulario a tu medida, validado y con aviso de privacidad.'],
                    ['🚀', 'Navega', 'Se conecta solo y tú lo ves al instante en el panel y en Telegram.'],
                ] as $i => [$ico, $titulo, $texto])
                    <div class="paso" data-reveal style="--retraso:{{ $i * .12 }}s">
                        <div class="num">{{ $ico }}</div>
                        <h3>{{ $titulo }}</h3>
                        <p>{{ $texto }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Cuándo y de dónde --}}
    <section>
        <div class="contenedor">
            <span class="ceja" data-reveal>Conoce a tu audiencia</span>
            <h2 data-reveal>Sabes <span class="naranja">cuándo</span> llegan y <span class="naranja">de dónde</span> vienen.</h2>

            @if ($c['por_hora'])
                <div class="panel crecer" data-reveal>
                    <h3>Registros por hora del día</h3>
                    <p class="sub">La hora pico fue a las <b>{{ sprintf('%02d:00', $pico['hora']) }}</b> con {{ $n($pico['total']) }} registros: el mejor momento para anunciar.</p>
                    <div class="horas" role="img" aria-label="Registros por hora; pico a las {{ $pico['hora'] }}:00 con {{ $pico['total'] }}">
                        @foreach ($c['por_hora'] as $i => $h)
                            <div class="hora {{ $h['hora'] === $pico['hora'] ? 'pico' : '' }}" style="--i:{{ $i }}">
                                @if ($h['total'])<span class="t">{{ $h['total'] }}</span>@endif
                                <span class="b" style="--v:{{ max(2, round($h['total'] / $maxHora * 100)) }}%"></span>
                                <span class="h">{{ $h['hora'] }}h</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="dos" style="margin-top:20px">
                @if ($c['estados'])
                    <div class="panel crecer" data-reveal>
                        <h3>Asistentes por estado</h3>
                        <p class="sub">Del campo "Estado" del formulario.</p>
                        @foreach (collect($c['estados'])->take(8) as $i => $e)
                            <div class="estado" style="--i:{{ $i }}">
                                <span>{{ $e['nombre'] }}</span>
                                <span class="pista"><span class="relleno" style="--v:{{ round($e['total'] / $maxEstado * 100) }}%"></span></span>
                                <span class="num">{{ $n($e['total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($c['plataformas'])
                    <div class="panel crecer" data-reveal style="--retraso:.1s">
                        <h3>Con qué celular llegaron</h3>
                        <p class="sub">Para saber en qué formato conviene cada anuncio.</p>
                        <div class="apilada">
                            @foreach ($c['plataformas'] as $i => $p)
                                <span style="--v:{{ $p['pct'] }}%;--color:{{ $colores[$i % 5] }};--i:{{ $i }}" title="{{ $p['nombre'] }} {{ $p['pct'] }}%"></span>
                            @endforeach
                        </div>
                        <div class="leyenda">
                            @foreach ($c['plataformas'] as $i => $p)
                                <span><i style="--color:{{ $colores[$i % 5] }}"></i>{{ $p['nombre'] }}<b>{{ $p['pct'] }}%</b></span>
                            @endforeach
                        </div>
                        <div class="mini-kpis">
                            <div class="mini"><div class="valor" data-contar="{{ $c['recurrentes'] }}">{{ $n($c['recurrentes']) }}</div><div class="etiqueta">celulares regresaron más de una vez</div></div>
                            <div class="mini"><div class="valor" data-contar="{{ $c['clics'] }}">{{ $n($c['clics']) }}</div><div class="etiqueta">clics en anuncios y botones</div></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 5. Patrocinadores y red --}}
    <section>
        <div class="contenedor">
            <span class="ceja" data-reveal>Valor para patrocinadores</span>
            <h2 data-reveal>Publicidad que <span class="naranja">sí se puede medir</span>.</h2>
            <p class="lead" data-reveal>En un stand nadie sabe cuántos vieron tu lona. En el portal de i-Free cada vista y cada clic se cuentan.</p>
            <div class="kpis">
                <div class="kpi" data-reveal>
                    <div class="valor"><span data-contar="{{ $c['impresiones'] }}">{{ $n($c['impresiones']) }}</span></div>
                    <div class="etiqueta">Pantallas con anuncios</div>
                    <div class="nota">{{ $c['campanas'] ? $c['campanas'] . ' campañas del evento' : 'Las campañas del evento' }} rotando en orden aleatorio</div>
                </div>
                <div class="kpi" data-reveal style="--retraso:.08s">
                    <div class="valor"><span data-contar="{{ $socios }}">{{ $n($socios) }}</span></div>
                    <div class="etiqueta">Socios {{ $caso['organizador'] }} en cada pantalla</div>
                    <div class="nota">La cinta de logos acompañó cada registro</div>
                </div>
                @if ($c['red'])
                    <div class="kpi" data-reveal style="--retraso:.16s">
                        <div class="valor"><span data-contar="{{ $c['red']['pico'] }}">{{ $n($c['red']['pico']) }}</span></div>
                        <div class="etiqueta">Conectados a la vez (pico)</div>
                        <div class="nota">{{ $c['red']['aps'] }} access points monitoreados minuto a minuto · {{ number_format($c['red']['gb'], 1) }} GB servidos</div>
                    </div>
                @else
                    <div class="kpi" data-reveal style="--retraso:.16s">
                        <div class="valor"><span data-contar="{{ $c['rifa'] }}">{{ $n($c['rifa']) }}</span></div>
                        <div class="etiqueta">Ganadores de la rifa en pantalla</div>
                        <div class="nota">Sorteo en vivo entre los registrados</div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 6. Para quién --}}
    <section>
        <div class="contenedor">
            <span class="ceja" data-reveal>Lo que i-Free puede hacer por ti</span>
            <h2 data-reveal>El mismo sistema, <span class="naranja">tres formas de ganar</span>.</h2>
            <div class="tarjetas">
                @foreach ([
                    ['🎪', 'Organizadores y asociaciones', ['Base de datos de asistentes, con estado y empresa', 'Pantalla en vivo con registros y rifa', 'Patrocinios con métricas para renovarlos', 'Avisos por Telegram si algo falla']],
                    ['🏷️', 'Expositores y patrocinadores', ['Tu anuncio en cada celular que se conecta', 'Vistas, clics y horario de mayor audiencia', 'Contactos interesados en tu marca', 'Reporte al terminar el evento']],
                    ['📡', 'ISP y WISP', ['Monetiza el WiFi de plazas, parques y negocios', 'Funciona con tu MikroTik, sin cambiar la red', 'Panel por cliente y por zona', 'Monitoreo de access points por SNMP']],
                ] as $i => [$ico, $titulo, $puntos])
                    <div class="tarjeta" data-reveal style="--retraso:{{ $i * .1 }}s">
                        <div class="ico">{{ $ico }}</div>
                        <h3>{{ $titulo }}</h3>
                        <ul>@foreach ($puntos as $p)<li>{{ $p }}</li>@endforeach</ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 7. Demo --}}
    <section class="demo" id="demo">
        <div class="contenedor demo-grid">
            <div>
                <span class="ceja" data-reveal>Solicita una demo</span>
                <h2 data-reveal>Llévalo a tu <span class="degradado">red o tu próximo evento</span>.</h2>
                <p class="lead" data-reveal>Te mostramos el panel con datos reales y armamos una prueba con tu MikroTik. Sin compromiso.</p>
                @if ($whatsapp)
                    <p class="contacto-alterno solo-pantalla" data-reveal>
                        ¿Prefieres escribir? <a class="naranja" href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, vi los resultados de i-Free en ' . $caso['titulo'] . ' y quiero una demo.') }}" target="_blank" rel="noopener">Mándanos un WhatsApp</a>.
                    </p>
                @endif
                <div class="solo-pdf contacto-alterno">
                    Solicita tu demo en <b>{{ route('casos.show', $slug) }}#demo</b>@if ($whatsapp) o por WhatsApp al <b>+{{ $whatsapp }}</b>@endif.
                </div>
            </div>

            @if (session('demo_enviada'))
                <div class="exito" data-reveal>
                    <h3>¡Listo, recibimos tu solicitud! 🎉</h3>
                    <p class="lead" style="margin-top:8px">Te contactamos en menos de un día hábil para agendar la demo.</p>
                </div>
            @else
                <form method="POST" action="{{ route('casos.demo', $slug) }}" data-reveal novalidate>
                    @csrf
                    <div class="trampa" aria-hidden="true"><label>Sitio web <input name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
                    <div class="fila">
                        <label>Nombre y apellido
                            <input name="nombre" value="{{ old('nombre') }}" autocomplete="name" required>
                            @error('nombre')<span class="error">{{ $message }}</span>@enderror
                        </label>
                        <label>Empresa
                            <input name="empresa" value="{{ old('empresa') }}" autocomplete="organization" required>
                            @error('empresa')<span class="error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <div class="fila">
                        <label>Teléfono (10 dígitos)
                            <input name="telefono" value="{{ old('telefono') }}" inputmode="numeric" autocomplete="tel-national" required>
                            @error('telefono')<span class="error">{{ $message }}</span>@enderror
                        </label>
                        <label>Correo
                            <input name="correo" type="email" value="{{ old('correo') }}" autocomplete="email" required>
                            @error('correo')<span class="error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label>¿Qué te interesa?
                        <select name="interes" required>
                            <option value="">Elige una opción</option>
                            @foreach ($intereses as $valor => $texto)
                                <option value="{{ $valor }}" @selected(old('interes') === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                        @error('interes')<span class="error">{{ $message }}</span>@enderror
                    </label>
                    <label>Cuéntanos (opcional)
                        <textarea name="mensaje" rows="3" placeholder="Número de zonas, fecha de tu evento, número de clientes…">{{ old('mensaje') }}</textarea>
                    </label>
                    <button class="boton boton-primario" type="submit">Solicitar demo</button>
                    <p class="aviso">Usamos tus datos solo para contactarte sobre esta demo.</p>
                </form>
            @endif
        </div>
    </section>
</main>

<footer>
    <div class="contenedor">
        <span>i-Free by Sattlink · Portal cautivo con métricas para MikroTik</span>
        <span>Cifras reales de la plataforma, sin datos personales · Actualizado {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</footer>

<script>
    // Aparecer al hacer scroll y contar las cifras desde cero
    (function () {
        const reducido = matchMedia('(prefers-reduced-motion: reduce)').matches;
        const formato = new Intl.NumberFormat('es-MX');

        // El número real nunca se toca (es lo que sale en el PDF); la cuenta se dibuja encima con ::after
        function contar(el) {
            const meta = parseInt(el.dataset.contar, 10) || 0;
            if (reducido || meta === 0 || el.dataset.contado) return;
            el.dataset.contado = '1';
            const inicio = performance.now(), duracion = 1400;
            (function paso(ahora) {
                const t = Math.min(1, (ahora - inicio) / duracion);
                el.dataset.mostrar = formato.format(Math.round(meta * (1 - Math.pow(1 - t, 3))));
                if (t < 1) requestAnimationFrame(paso); else delete el.dataset.mostrar;
            })(inicio);
        }

        document.querySelectorAll('[data-entrada] [data-contar]').forEach(contar);

        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('visible'));
            return;
        }

        const observador = new IntersectionObserver(entradas => {
            entradas.forEach(e => {
                if (!e.isIntersecting) return;
                e.target.classList.add('visible');
                e.target.querySelectorAll('[data-contar]').forEach(contar);
                if (e.target.matches('[data-contar]')) contar(e.target);
                observador.unobserve(e.target);
            });
        }, { threshold: .18 });

        document.querySelectorAll('[data-reveal]').forEach(el => observador.observe(el));

        // Al imprimir, todo visible aunque no se haya hecho scroll
        addEventListener('beforeprint', () => document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('visible')));
    })();
</script>
</body>
</html>
