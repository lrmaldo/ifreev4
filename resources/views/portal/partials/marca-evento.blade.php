{{-- Encabezado del tema "evento": logos reducidos para celular (public/img/evento/portal) y marca en CSS --}}
<div class="marca-evento">
    {{-- El logo trae letras negras que no se leen en el fondo oscuro: se pinta en blanco --}}
    <span class="marca-evento-ifree">
        <img class="marca-evento-logo marca-evento-logo-blanco" src="{{ asset('img/evento/portal/logo-ifree.webp') }}" alt="i-Free" width="40" height="40">
        <span class="marca-evento-by">by Sattlink</span>
    </span>
    @if (file_exists(public_path('img/evento/portal/logo-wispmx.webp')))
        <img class="marca-evento-logo" src="{{ asset('img/evento/portal/logo-wispmx.webp') }}" alt="WISPMX" width="66" height="36">
    @endif
    <span class="marca-evento-titulo" aria-label="Expo MX-ISP 2026">
        <span class="marca-evento-expo" aria-hidden="true">EXPO</span>
        <span aria-hidden="true"><span class="marca-evento-mxisp">MX-ISP</span> 2026</span>
    </span>
</div>
<p class="marca-evento-sub">WiFi oficial del evento</p>
