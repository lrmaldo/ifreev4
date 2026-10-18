@php
    // Cifras reales de la plataforma (ver la ruta "/"); se redondean hacia abajo
    $abreviar = fn (int $n) => $n >= 1000 ? number_format(intdiv($n, 1000)) . ' mil' : (string) $n;
    $whatsapp = config('ifree.whatsapp');
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>i-Free Hotspot · WiFi gratis que se convierte en publicidad</title>

        <link rel="icon" href="{{ asset('img/Logo i-Free.png') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('img/Logo i-Free.png') }}">
        <meta name="author" content="i-Free · Sattlink">
        <meta name="description" content="Ofrece internet gratis en tu negocio o evento: cada persona ve tu anuncio, deja sus datos y se conecta. Tú ves en tiempo real quién llegó.">
        <meta name="keywords" content="i-Free, portal cautivo, WiFi gratis, hotspot, MikroTik, publicidad, eventos">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#0b0f19">
        <meta property="og:title" content="i-Free Hotspot · WiFi gratis que se convierte en publicidad" />
        <meta property="og:description" content="Cada persona que se conecta ve tu anuncio y deja sus datos. Métricas en vivo, avisos por Telegram y pantalla para eventos." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:image" content="{{ asset('img/Logo i-Free.png') }}" />
        <meta name="twitter:card" content="summary_large_image" />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root { --naranja: #ff3f00; --naranja-2: #ff7a45; --tinta: #0b0f19; --tinta-2: #131a2b; }
            html { scroll-behavior: smooth; }
            body { background: #fbfaf8; color: #16161a; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }

            /* ── Aparecer al hacer scroll ── */
            [data-reveal] { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s cubic-bezier(.2,.7,.2,1); }
            [data-reveal].visible { opacity: 1; transform: none; }
            [data-reveal="2"] { transition-delay: .12s; } [data-reveal="3"] { transition-delay: .24s; } [data-reveal="4"] { transition-delay: .36s; }
            /* El hero entra solo al cargar, sin esperar al scroll */
            [data-entrada] { animation: entrada .8s cubic-bezier(.2,.7,.2,1) both; animation-delay: var(--retraso, 0s); }
            @keyframes entrada { from { opacity: 0; transform: translateY(24px); } }

            /* ── Fondo del hero ── */
            .hero { background: radial-gradient(1200px 600px at 85% 10%, rgba(255,63,0,.28), transparent 60%), radial-gradient(800px 500px at 0% 100%, rgba(255,122,69,.14), transparent 60%), var(--tinta); }
            .malla { background-image: linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px); background-size: 48px 48px; mask-image: radial-gradient(ellipse at 70% 30%, #000 30%, transparent 75%); }
            .orbe { position: absolute; border-radius: 9999px; filter: blur(60px); opacity: .55; animation: flotar 14s ease-in-out infinite; }
            @keyframes flotar { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(30px,-40px) scale(1.1); } }

            .titulo-degradado { background: linear-gradient(90deg, #ff3f00, #ff9a6b, #ff3f00); background-size: 200% auto; -webkit-background-clip: text; background-clip: text; color: transparent; animation: brillo 6s linear infinite; }
            @keyframes brillo { to { background-position: 200% center; } }

            /* ── Teléfono: 4 escenas en un ciclo de 12 s ── */
            .telefono { width: 280px; height: 570px; border-radius: 44px; background: #05070d; padding: 12px; box-shadow: 0 40px 80px -20px rgba(0,0,0,.6), 0 0 0 1px rgba(255,255,255,.08) inset; position: relative; }
            .pantalla { position: relative; width: 100%; height: 100%; border-radius: 34px; overflow: hidden; background: #fff; }
            .muesca { position: absolute; top: 10px; left: 50%; transform: translateX(-50%); width: 92px; height: 24px; border-radius: 999px; background: #05070d; z-index: 5; }
            .escena { position: absolute; inset: 0; display: flex; flex-direction: column; opacity: 0; animation: escena 12s infinite; }
            .escena-2 { animation-delay: 3s; } .escena-3 { animation-delay: 6s; } .escena-4 { animation-delay: 9s; }
            @keyframes escena { 0% { opacity: 0; transform: translateY(10px) scale(.98); } 3%, 22% { opacity: 1; transform: none; } 25%, 100% { opacity: 0; transform: translateY(-6px); } }

            .ondas span { position: absolute; inset: 0; border-radius: 9999px; border: 2px solid var(--naranja); animation: onda 1.8s ease-out infinite; }
            .ondas span:nth-child(2) { animation-delay: .6s; } .ondas span:nth-child(3) { animation-delay: 1.2s; }
            @keyframes onda { from { transform: scale(.4); opacity: .9; } to { transform: scale(1.6); opacity: 0; } }

            .anillo { stroke-dasharray: 151; animation: cuenta 12s linear infinite; animation-delay: 3s; }
            @keyframes cuenta { 0% { stroke-dashoffset: 0; } 24% { stroke-dashoffset: 151; } 100% { stroke-dashoffset: 151; } }
            .anuncio { animation: zoom-anuncio 12s ease-in-out infinite; animation-delay: 3s; }
            @keyframes zoom-anuncio { 0% { transform: scale(1.12); } 24% { transform: scale(1); } 100% { transform: scale(1); } }

            .tecleo { display: block; height: 10px; border-radius: 4px; background: #27272a; width: 0; animation: teclear 12s steps(14) infinite; animation-delay: 6.2s; }
            .tecleo-2 { animation-delay: 6.9s; }
            @keyframes teclear { 0% { width: 0; } 6% { width: 70%; } 100% { width: 70%; } }
            .boton-presion { animation: presion 12s infinite; animation-delay: 6s; }
            @keyframes presion { 0%, 15% { transform: scale(1); filter: none; } 17% { transform: scale(.95); filter: brightness(.9); } 19%, 100% { transform: scale(1); filter: none; } }

            .palomita path { stroke-dasharray: 60; stroke-dashoffset: 60; animation: trazar 12s infinite; animation-delay: 9s; }
            @keyframes trazar { 0%, 3% { stroke-dashoffset: 60; } 9%, 100% { stroke-dashoffset: 0; } }

            /* Pasos bajo el teléfono, sincronizados con las escenas */
            .paso-vivo { animation: paso 12s infinite; }
            .paso-vivo:nth-child(2) { animation-delay: 3s; } .paso-vivo:nth-child(3) { animation-delay: 6s; } .paso-vivo:nth-child(4) { animation-delay: 9s; }
            @keyframes paso { 0%, 24% { color: #fff; border-color: var(--naranja); background: rgba(255,63,0,.18); } 25%, 100% { color: rgba(255,255,255,.45); border-color: rgba(255,255,255,.12); background: transparent; } }

            /* Avisos flotantes alrededor del teléfono */
            .aviso { opacity: 0; animation: aviso 12s infinite; }
            .aviso-a { animation-delay: 3.6s; } .aviso-b { animation-delay: 7.4s; } .aviso-c { animation-delay: 9.6s; }
            @keyframes aviso { 0% { opacity: 0; transform: translateX(24px) scale(.96); } 4%, 20% { opacity: 1; transform: none; } 24%, 100% { opacity: 0; transform: translateX(-12px); } }

            /* Línea de "Cómo funciona" y barras del panel */
            .linea-pasos { transform: scaleX(0); transform-origin: left; transition: transform 1.4s cubic-bezier(.6,0,.2,1) .2s; }
            .visible .linea-pasos { transform: scaleX(1); }
            .barra { transform: scaleY(0); transform-origin: bottom; transition: transform .9s cubic-bezier(.2,.8,.2,1); }
            .visible .barra { transform: scaleY(1); }

            .punto-vivo { animation: latido 1.6s ease-in-out infinite; }
            @keyframes latido { 0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,.6); } 50% { box-shadow: 0 0 0 8px rgba(34,197,94,0); } }

            .cta-fondo { background: linear-gradient(120deg, #ff3f00, #ff6a2b, #e23300, #ff3f00); background-size: 300% 300%; animation: mover-fondo 10s ease infinite; }
            @keyframes mover-fondo { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

            @media (prefers-reduced-motion: reduce) {
                *, *::before, *::after { animation: none !important; transition: none !important; }
                [data-reveal] { opacity: 1; transform: none; }
                .escena-2, .aviso-a { opacity: 1; }
                .anillo { stroke-dashoffset: 60; }
                .barra, .linea-pasos { transform: none; }
            }
        </style>
    </head>
    <body class="antialiased">
        {{-- Navegación --}}
        <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#0b0f19]/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <img src="/img/Logo-i-Free.png" alt="" class="h-10 w-auto">
                    <span class="text-xl font-bold text-white">i-Free <span class="font-medium text-white/60">Hotspot</span></span>
                </a>
                <nav class="flex items-center gap-1 sm:gap-2">
                    <a href="#como-funciona" class="hidden rounded-lg px-3 py-2 text-sm text-white/70 hover:text-white md:block">Cómo funciona</a>
                    <a href="#eventos" class="hidden rounded-lg px-3 py-2 text-sm text-white/70 hover:text-white md:block">Eventos</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20">Ir al panel</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-white/80 hover:text-white">Iniciar sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-[#ff3f00] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-[#ff3f00]/30 hover:bg-[#ff5a22]">Solicitar acceso</a>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="hero relative overflow-hidden pt-28 pb-20 text-white lg:pt-32 lg:pb-28">
                <div class="malla pointer-events-none absolute inset-0"></div>
                <div class="orbe left-[8%] top-[18%] h-64 w-64 bg-[#ff3f00]/40"></div>
                <div class="orbe right-[6%] bottom-[8%] h-72 w-72 bg-[#ff7a45]/25" style="animation-delay:-6s"></div>

                <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-[1.1fr_1fr]">
                    <div>
                        <p style="--retraso: 0s" data-entrada class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-sm text-white/80">
                            <span class="punto-vivo h-2 w-2 rounded-full bg-green-500"></span>
                            Portal cautivo para MikroTik · métricas en vivo
                        </p>
                        <h1 style="--retraso: 0.12s" data-entrada class="mt-6 text-4xl font-bold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                            Tu WiFi gratis,<br>
                            <span class="titulo-degradado">convertido en publicidad</span><br>
                            que sí se ve.
                        </h1>
                        <p style="--retraso: 0.24s" data-entrada class="mt-6 max-w-xl text-lg leading-relaxed text-white/70">
                            Cada persona que se conecta en tu negocio o evento ve tu anuncio, deja sus datos y navega.
                            Tú ves en tiempo real quién llegó, y te avisamos por Telegram.
                        </p>
                        <div style="--retraso: 0.36s" data-entrada class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ Route::has('register') ? route('register') : route('login') }}" class="group inline-flex items-center gap-2 rounded-xl bg-[#ff3f00] px-6 py-3.5 font-semibold text-white shadow-xl shadow-[#ff3f00]/30 transition hover:-translate-y-0.5 hover:bg-[#ff5a22]">
                                Quiero i-Free en mi negocio
                                <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
                            </a>
                            <a href="#como-funciona" class="inline-flex items-center rounded-xl border border-white/20 px-6 py-3.5 font-semibold text-white/90 transition hover:bg-white/10">Ver cómo funciona</a>
                        </div>

                        <dl style="--retraso: 0.36s" data-entrada class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-8">
                            <div>
                                <dt class="text-sm text-white/55">Zonas WiFi</dt>
                                <dd class="mt-1 text-3xl font-bold tabular-nums" data-contar="{{ $cifras['zonas'] }}">{{ $cifras['zonas'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-white/55">Dispositivos conectados</dt>
                                <dd class="mt-1 text-3xl font-bold tabular-nums">{{ $abreviar($cifras['dispositivos']) }}+</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-white/55">Registros captados</dt>
                                <dd class="mt-1 text-3xl font-bold tabular-nums">{{ $abreviar($cifras['registros']) }}+</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Teléfono animado: el recorrido real del visitante --}}
                    <div class="relative mx-auto" style="--retraso: 0.24s" data-entrada aria-label="Animación: el celular se conecta, ve un anuncio, se registra y queda conectado" role="img">
                        <div class="telefono">
                            <div class="muesca"></div>
                            <div class="pantalla">
                                {{-- 1. Se conecta al WiFi --}}
                                <div class="escena escena-1 items-center justify-center bg-gradient-to-b from-orange-50 to-white px-6 text-center">
                                    <div class="ondas relative flex h-28 w-28 items-center justify-center">
                                        <span></span><span></span><span></span>
                                        <div class="relative flex h-16 w-16 items-center justify-center rounded-full bg-[#ff3f00] text-white shadow-lg">
                                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 12.55a11 11 0 0114 0M8.5 16.1a6 6 0 017 0M12 20h.01M1.4 9a16 16 0 0121.2 0"/></svg>
                                        </div>
                                    </div>
                                    <p class="mt-8 text-lg font-bold text-zinc-900">WiFi Gratis</p>
                                    <p class="mt-1 text-sm text-zinc-500">Conectándote a <strong class="text-zinc-700">Café Central</strong>…</p>
                                </div>

                                {{-- 2. Ve el anuncio con cuenta regresiva --}}
                                <div class="escena escena-2 bg-zinc-950">
                                    <div class="relative h-[62%] overflow-hidden">
                                        <div class="anuncio absolute inset-0 bg-gradient-to-br from-[#ff3f00] via-[#ff7a45] to-[#ffb38f]"></div>
                                        <div class="absolute inset-0 flex flex-col justify-end p-6 text-white">
                                            <p class="text-xs font-semibold uppercase tracking-widest text-white/80">Hoy en Café Central</p>
                                            <p class="mt-1 text-4xl font-black leading-none">2×1 en<br>capuchinos</p>
                                            <p class="mt-2 text-sm text-white/85">Muestra esta pantalla en caja</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-1 flex-col items-center justify-center gap-3 text-white">
                                        <svg class="h-16 w-16 -rotate-90" viewBox="0 0 56 56" aria-hidden="true"><circle cx="28" cy="28" r="24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="5"/><circle class="anillo" cx="28" cy="28" r="24" fill="none" stroke="#ff3f00" stroke-width="5" stroke-linecap="round"/></svg>
                                        <p class="text-sm text-white/70">Tu internet inicia en unos segundos</p>
                                    </div>
                                </div>

                                {{-- 3. Deja sus datos --}}
                                <div class="escena escena-3 bg-white px-6 pt-14">
                                    <p class="text-xl font-bold text-zinc-900">Un último paso</p>
                                    <p class="mt-1 text-sm text-zinc-500">Regístrate para navegar gratis</p>
                                    <div class="mt-6 space-y-4">
                                        <div><p class="mb-1.5 text-xs font-medium text-zinc-500">Nombre</p><div class="rounded-xl border border-zinc-200 px-3 py-3"><span class="tecleo"></span></div></div>
                                        <div><p class="mb-1.5 text-xs font-medium text-zinc-500">Teléfono</p><div class="rounded-xl border border-zinc-200 px-3 py-3"><span class="tecleo tecleo-2"></span></div></div>
                                        <label class="flex items-start gap-2 text-xs text-zinc-500"><span class="mt-0.5 h-4 w-4 shrink-0 rounded border-2 border-[#ff3f00] bg-[#ff3f00]"></span>Acepto el aviso de privacidad</label>
                                    </div>
                                    <div class="boton-presion mt-6 rounded-xl bg-[#ff3f00] py-3.5 text-center text-sm font-semibold text-white shadow-lg shadow-[#ff3f00]/30">Conectarme</div>
                                </div>

                                {{-- 4. Conectado --}}
                                <div class="escena escena-4 items-center justify-center bg-gradient-to-b from-green-50 to-white text-center">
                                    <svg class="palomita h-24 w-24" viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24" fill="#22c55e"/><path d="M15 27l7 7 15-15" fill="none" stroke="#fff" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <p class="mt-6 text-2xl font-bold text-zinc-900">¡Conectado!</p>
                                    <p class="mt-1 text-sm text-zinc-500">Disfruta tu internet gratis</p>
                                </div>
                            </div>
                        </div>

                        {{-- Lo que pasa del lado del negocio --}}
                        <div class="aviso aviso-a absolute -left-28 top-24 hidden w-56 rounded-2xl border border-white/10 bg-white/95 p-3 text-zinc-800 shadow-2xl sm:block">
                            <p class="text-xs font-semibold text-zinc-500">Métricas · en vivo</p>
                            <p class="mt-0.5 text-sm font-semibold">👁 +1 vista del anuncio</p>
                        </div>
                        <div class="aviso aviso-b absolute -right-36 top-[58%] hidden w-60 rounded-2xl border border-white/10 bg-white/95 p-3 text-zinc-800 shadow-2xl sm:block">
                            <p class="flex items-center gap-1.5 text-xs font-semibold text-sky-600"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.6l-3.1 14.8c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.6 13.2 1.9 11.7c-1-.3-1-1 .2-1.5L20.6 3c.9-.3 1.6.2 1.3 1.6z"/></svg> Telegram</p>
                            <p class="mt-0.5 text-sm font-semibold">📝 Nuevo registro · Ana G.</p>
                        </div>
                        <div class="aviso aviso-c absolute -left-20 bottom-28 hidden w-52 rounded-2xl border border-white/10 bg-white/95 p-3 text-zinc-800 shadow-2xl sm:block">
                            <p class="text-xs font-semibold text-zinc-500">Hoy</p>
                            <p class="mt-0.5 flex items-center gap-2 text-sm font-semibold"><span class="punto-vivo h-2 w-2 rounded-full bg-green-500"></span>58 conectados ahora</p>
                        </div>

                        <ol class="mt-6 grid grid-cols-4 gap-2 text-center text-[11px] font-semibold">
                            <li class="paso-vivo rounded-lg border px-1 py-1.5">Se conecta</li>
                            <li class="paso-vivo rounded-lg border px-1 py-1.5">Ve tu anuncio</li>
                            <li class="paso-vivo rounded-lg border px-1 py-1.5">Se registra</li>
                            <li class="paso-vivo rounded-lg border px-1 py-1.5">Navega</li>
                        </ol>
                    </div>
                </div>
            </section>

            {{-- Cómo funciona --}}
            <section id="como-funciona" class="scroll-mt-20 py-20 lg:py-28">
                <div class="mx-auto max-w-7xl px-4 sm:px-6">
                    <div class="max-w-2xl" data-reveal>
                        <p class="text-sm font-semibold uppercase tracking-widest text-[#ff3f00]">Cómo funciona</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Funciona con el MikroTik que ya tienes</h2>
                        <p class="mt-4 text-lg text-zinc-600">Sin apps ni equipos nuevos: subimos un archivo a tu router y tu WiFi empieza a trabajar para ti.</p>
                    </div>

                    <div class="relative mt-14" data-reveal="2">
                        <div class="absolute left-0 right-0 top-7 hidden h-0.5 bg-zinc-200 md:block"><div class="linea-pasos h-full bg-[#ff3f00]"></div></div>
                        <div class="grid gap-10 md:grid-cols-3">
                            @foreach ([
                                ['1', 'Conectamos tu hotspot', 'Configuramos tu MikroTik con el portal de i-Free. Tus APs siguen igual.'],
                                ['2', 'Sube tus anuncios', 'Imágenes o videos con fechas, días y prioridad. Se muestran durante la cuenta regresiva.'],
                                ['3', 'Mira quién llegó', 'Visitas, registros y dispositivos en tiempo real, avisos por Telegram y exportación a Excel.'],
                            ] as [$numero, $titulo, $texto])
                                <div class="relative">
                                    <div class="relative z-10 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0b0f19] text-xl font-bold text-white shadow-lg ring-4 ring-[#fbfaf8]">{{ $numero }}</div>
                                    <h3 class="mt-5 text-xl font-semibold">{{ $titulo }}</h3>
                                    <p class="mt-2 text-zinc-600">{{ $texto }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- El panel --}}
            <section class="bg-white py-20 lg:py-28">
                <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2">
                    <div data-reveal>
                        <p class="text-sm font-semibold uppercase tracking-widest text-[#ff3f00]">Tu panel</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Sabes cuánta gente vio tu anuncio. Hoy, no a fin de mes.</h2>
                        <ul class="mt-8 space-y-4 text-zinc-700">
                            @foreach (['Visitas, dispositivos nuevos y registros por día, comparados con el periodo anterior.', 'Cada registro con nombre y teléfono, listo para exportar a CSV.', 'Avisos por Telegram al instante o en resumen cada pocos minutos.', 'Varias zonas y varios clientes, cada uno ve solo lo suyo.'] as $punto)
                                <li class="flex gap-3"><svg class="mt-0.5 h-5 w-5 shrink-0 text-[#ff3f00]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>{{ $punto }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div data-reveal="2" class="rounded-3xl border border-zinc-200 bg-[#fbfaf8] p-6 shadow-xl shadow-zinc-200/60" aria-hidden="true">
                        <div class="grid grid-cols-3 gap-3">
                            @foreach ([['Visitas hoy', '1,284', '▲ 18%'], ['Registros', '342', '▲ 9%'], ['Conectados', '58', 'ahora']] as [$t, $v, $c])
                                <div class="rounded-2xl bg-white p-4 shadow-sm">
                                    <p class="text-xs text-zinc-500">{{ $t }}</p>
                                    <p class="mt-1 text-2xl font-bold tabular-nums">{{ $v }}</p>
                                    <p class="text-xs font-medium text-green-600">{{ $c }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 rounded-2xl bg-white p-4 shadow-sm">
                            <p class="text-sm font-semibold">Últimos 14 días</p>
                            <div class="mt-4 flex h-36 items-end gap-1.5">
                                @foreach ([30, 42, 38, 55, 48, 62, 58, 45, 70, 66, 80, 74, 92, 100] as $i => $alto)
                                    <div class="barra flex-1 rounded-t-md {{ $i === 13 ? 'bg-[#ff3f00]' : 'bg-[#ff3f00]/25' }}" style="height: {{ $alto }}%; transition-delay: {{ $i * 45 }}ms"></div>
                                @endforeach
                            </div>
                        </div>
                        <p class="mt-3 text-center text-xs text-zinc-400">Datos de ejemplo</p>
                    </div>
                </div>
            </section>

            {{-- Eventos --}}
            <section id="eventos" class="scroll-mt-20 relative overflow-hidden bg-[#0b0f19] py-20 text-white lg:py-28">
                <div class="malla pointer-events-none absolute inset-0 opacity-60"></div>
                <div class="relative mx-auto max-w-7xl px-4 sm:px-6">
                    <div class="max-w-2xl" data-reveal>
                        <p class="text-sm font-semibold uppercase tracking-widest text-[#ff7a45]">Para eventos y expos</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Convierte el WiFi del evento en el centro del stand</h2>
                        <p class="mt-4 text-lg text-white/70">Aforo en vivo en una pantalla grande, rifas entre quienes se registran y avisos si algo falla.</p>
                    </div>
                    <div class="mt-12 grid gap-5 md:grid-cols-3">
                        @foreach ([
                            ['📺', 'Pantalla en vivo', 'Conectados ahora, registros del día y llegadas por hora en la TV del stand, con el anuncio del patrocinador.'],
                            ['🎁', 'Rifa en pantalla', 'Un sorteo animado entre los asistentes registrados. Cada quien participa una vez.'],
                            ['🛟', 'Sin caídas', 'Si el servidor no responde, el WiFi conecta directo y te avisamos por Telegram.'],
                        ] as $i => [$icono, $titulo, $texto])
                            <div data-reveal="{{ $i + 2 }}" class="rounded-2xl border border-white/10 bg-white/[.04] p-6 transition hover:-translate-y-1 hover:border-[#ff3f00]/60 hover:bg-white/[.07]">
                                <p class="text-3xl" aria-hidden="true">{{ $icono }}</p>
                                <h3 class="mt-4 text-lg font-semibold">{{ $titulo }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-white/65">{{ $texto }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Llamado final --}}
            <section class="cta-fondo py-20 text-white">
                <div class="mx-auto max-w-3xl px-4 text-center" data-reveal>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">¿Listo para que tu WiFi trabaje para ti?</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-white/85">Solicita tu acceso: el equipo de Sattlink revisa tu solicitud y te ayuda a conectar tu primer hotspot.</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <a href="{{ Route::has('register') ? route('register') : route('login') }}" class="rounded-xl bg-white px-7 py-3.5 font-semibold text-[#ff3f00] shadow-xl transition hover:-translate-y-0.5">Solicitar acceso</a>
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="rounded-xl border border-white/40 px-7 py-3.5 font-semibold text-white transition hover:bg-white/10">Hablar por WhatsApp</a>
                        @endif
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-[#0b0f19] py-10 text-white/60">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 text-sm sm:flex-row sm:px-6">
                <div class="flex items-center gap-2.5">
                    <img src="/img/Logo-i-Free.png" alt="" class="h-8 w-auto">
                    <span class="font-semibold text-white">i-Free Hotspot</span>
                    <span>· un servicio de Sattlink</span>
                </div>
                <p>&copy; {{ date('Y') }} i-Free. Todos los derechos reservados.</p>
            </div>
        </footer>

        @if ($whatsapp)
            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-full bg-[#25D366] py-3 pl-3 pr-5 font-semibold text-white shadow-xl shadow-black/20 transition hover:-translate-y-0.5" aria-label="Escríbenos por WhatsApp">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.2a8.2 8.2 0 01-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1112 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 01-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 00-.7.3 3 3 0 00-.9 2.2 5.2 5.2 0 001.1 2.7 11.8 11.8 0 004.5 4c1.7.7 2.3.8 3.2.7a2.7 2.7 0 001.8-1.3 2.2 2.2 0 00.2-1.3c-.1-.1-.3-.2-.5-.3z"/></svg>
                <span class="hidden text-sm sm:inline">¿Hablamos?</span>
            </a>
        @endif

        <script>
            (function () {
                var animar = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                // Aparecer al entrar en pantalla
                var observador = 'IntersectionObserver' in window ? new IntersectionObserver(function (entradas) {
                    entradas.forEach(function (e) {
                        if (e.isIntersecting) { e.target.classList.add('visible'); observador.unobserve(e.target); }
                    });
                }, { threshold: 0.15 }) : null;
                document.querySelectorAll('[data-reveal]').forEach(function (el) {
                    observador && animar ? observador.observe(el) : el.classList.add('visible');
                });

                // Contadores que suben desde cero
                document.querySelectorAll('[data-contar]').forEach(function (el) {
                    var fin = parseInt(el.getAttribute('data-contar'), 10) || 0;
                    if (!animar || fin < 2) { return; }
                    var inicio = null;
                    el.textContent = '0';
                    function paso(t) {
                        inicio = inicio || t;
                        var p = Math.min((t - inicio) / 1400, 1);
                        el.textContent = Math.round(fin * (1 - Math.pow(1 - p, 3))).toLocaleString('es-MX');
                        if (p < 1) { requestAnimationFrame(paso); }
                    }
                    setTimeout(function () { requestAnimationFrame(paso); }, 600);
                });
            })();
        </script>
    </body>
</html>
