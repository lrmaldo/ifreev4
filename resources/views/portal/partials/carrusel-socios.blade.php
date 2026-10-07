{{-- Carrusel de socios para el portal (logos mini, ~230 KB en total) --}}
@php $socios = \App\Support\SociosEvento::lista(mini: true); @endphp
@if (count($socios))
    <style>
        .portal-socios { max-width: 500px; margin: 0 auto 24px; padding: 0 16px; }
        .portal-socios p { margin: 0 0 8px; text-align: center; font-size: 11px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; opacity: .75; }
        .portal-socios-cinta { overflow: hidden; -webkit-mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
        .portal-socios-pista { display: flex; gap: 8px; width: max-content; animation: portal-socios var(--duracion, 120s) linear infinite; }
        .portal-socio { flex: none; display: grid; place-items: center; height: 44px; padding: 0 10px; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .08); }
        .portal-socio img { display: block; max-height: 30px; max-width: 90px; object-fit: contain; }
        @keyframes portal-socios { to { transform: translateX(calc(-50% - 4px)); } }
        @media (prefers-reduced-motion: reduce) { .portal-socios-pista { animation: none; } }
    </style>
    <section class="portal-socios" aria-label="Socios">
        <p>Socios WISPMX</p>
        <div class="portal-socios-cinta">
            <div class="portal-socios-pista" style="--duracion: {{ max(30, count($socios) * 3) }}s">
                @foreach ([false, true] as $copia)
                    @foreach ($socios as $socio)
                        <div class="portal-socio" @if ($copia) aria-hidden="true" @endif>
                            <img src="{{ $socio['url'] }}" alt="{{ $copia ? '' : $socio['nombre'] }}" loading="lazy" decoding="async">
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </section>
@endif
