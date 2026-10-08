{{--
    "Wrapped": tarjeta con el resumen del periodo que se descarga como imagen (html2canvas).
    Va fuera de pantalla y solo con estilos en línea: html2canvas no entiende los colores oklch de Tailwind 4.
--}}
@php
    $wMax = max(1, collect($porDia)->max('visitas'));
    $wBarras = count($porDia) > 45 ? collect($porDia)->chunk((int) ceil(count($porDia) / 45))->map(fn ($c) => ['visitas' => $c->sum('visitas')])->values()->all() : $porDia;
    $wMax = max(1, collect($wBarras)->max('visitas'));
    $wStats = [
        ['Visitas', $nf($resumen['visitas'])],
        ['Disp. nuevos', $nf($resumen['nuevos'])],
        ['Registros', $nf($resumen['registros'])],
        ['Regresaron', $nf($resumen['recurrentes'])],
        ['Conversión', $resumen['conversion'] . '%'],
    ];
@endphp
<div id="wrapped-outer-container" style="position: fixed; left: -9999px; top: 0; pointer-events: none;" aria-hidden="true">
    <div id="wrapped-card" data-nombre="{{ Str::slug($zonaActual?->nombre ?? 'zonas') }}" style="width: 600px; height: 1000px; box-sizing: border-box; padding: 44px; background: linear-gradient(160deg, #ff3f00 0%, #b32c00 55%, #3d0c00 100%); color: #ffffff; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; display: flex; flex-direction: column; gap: 22px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -90px; right: -90px; width: 300px; height: 300px; border-radius: 9999px; background: rgba(255,255,255,0.10);"></div>

        <div style="position: relative; text-align: center;">
            <p style="margin: 0; font-size: 13px; font-weight: 700; letter-spacing: 0.25em; color: #ffd5c2; text-transform: uppercase;">Resumen de tu hotspot</p>
            <h1 style="margin: 8px 0 0; font-size: 40px; font-weight: 900; letter-spacing: -0.02em; line-height: 1; color: #ffffff;">WRAPPED</h1>
        </div>

        <div style="position: relative; background: rgba(0,0,0,0.25); border-radius: 18px; padding: 20px 24px;">
            <p style="margin: 0; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; color: #ffd5c2; text-transform: uppercase;">Zona</p>
            <p style="margin: 4px 0 0; font-size: 24px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $zonaActual?->nombre ?? ($zonas->count() === 1 ? $zonas->first()->nombre : 'Todas las zonas') }}</p>
            <p style="margin: 6px 0 0; font-size: 14px; color: #ffe8de;">{{ $textoPeriodo }}</p>
        </div>

        <div style="position: relative; display: flex; flex-wrap: wrap; gap: 10px;">
            @foreach ($wStats as $i => [$titulo, $valor])
                <div style="flex: 1 1 {{ $i < 2 ? 'calc(50% - 10px)' : 'calc(33.33% - 10px)' }}; box-sizing: border-box; background: rgba(255,255,255,0.12); border-radius: 14px; padding: 14px; text-align: center;">
                    <p style="margin: 0; font-size: 10px; font-weight: 800; letter-spacing: 0.1em; color: #ffd5c2; text-transform: uppercase;">{{ $titulo }}</p>
                    <p style="margin: 4px 0 0; font-size: {{ $i < 2 ? '30px' : '20px' }}; font-weight: 900; color: #ffffff;">{{ $valor }}</p>
                </div>
            @endforeach
        </div>

        <div style="position: relative; background: #ffffff; border-radius: 18px; padding: 20px 22px;">
            <p style="margin: 0 0 12px; font-size: 11px; font-weight: 800; letter-spacing: 0.18em; color: #ff3f00; text-transform: uppercase; text-align: center;">Visitas por día</p>
            <div style="display: flex; align-items: flex-end; gap: 2px; height: 150px;">
                @foreach ($wBarras as $barra)
                    <div style="flex: 1; background: #ff3f00; border-radius: 3px 3px 0 0; height: {{ max(1, round($barra['visitas'] / $wMax * 100)) }}%; opacity: {{ $barra['visitas'] ? 1 : 0.15 }};"></div>
                @endforeach
            </div>
        </div>

        @if (!empty($topDispositivos))
            <div style="position: relative; background: rgba(0,0,0,0.2); border-radius: 18px; padding: 18px 22px;">
                <p style="margin: 0 0 10px; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; color: #ffd5c2; text-transform: uppercase; text-align: center;">Dispositivos más comunes</p>
                @foreach (array_slice($topDispositivos, 0, 3) as $i => $fila)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 14px;">
                        <span style="color: #ffffff; font-weight: 600;">{{ $i + 1 }}. {{ \Illuminate\Support\Str::limit($fila['nombre'], 30) }}</span>
                        <span style="color: #ffd5c2; font-weight: 800;">{{ $nf($fila['total']) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div style="position: relative; margin-top: auto; text-align: center;">
            <p style="margin: 0; font-size: 20px; font-weight: 900; letter-spacing: 0.3em; color: #ffffff;">i-FREE</p>
            <p style="margin: 6px 0 0; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; color: #ffd5c2;">#YourHotspotWrapped</p>
        </div>
    </div>
</div>

@script
<script>
    window.generateWrapped = async function (boton) {
        const textoOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.textContent = 'Generando…';

        try {
            if (typeof html2canvas === 'undefined') {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            }

            const canvas = await html2canvas(document.getElementById('wrapped-card'), {
                scale: 2,
                backgroundColor: null,
                logging: false,
                // Solo la tarjeta en el clon: el resto de la página usa oklch y html2canvas falla
                onclone: (doc) => {
                    const contenedor = doc.getElementById('wrapped-outer-container');
                    contenedor.style.left = '0';
                    doc.body.innerHTML = '';
                    doc.body.appendChild(contenedor);
                    doc.querySelectorAll('style, link[rel="stylesheet"]').forEach((el) => el.remove());
                },
            });

            const enlace = document.createElement('a');
            enlace.download = 'wrapped-' + (document.getElementById('wrapped-card').dataset.nombre || 'zonas') + '.png';
            enlace.href = canvas.toDataURL('image/png');
            enlace.click();
        } catch (e) {
            console.error('Error al generar el Wrapped:', e);
            alert('No se pudo generar la imagen. Recarga la página e inténtalo de nuevo.');
        } finally {
            boton.disabled = false;
            boton.innerHTML = textoOriginal;
        }
    };
</script>
@endscript
