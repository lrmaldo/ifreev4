<!-- Widget informativo para depuración -->
<div class="debug-info" style="position:fixed; bottom:0; right:0; background:rgba(0,0,0,0.8); color:white; padding:8px; font-size:11px; z-index:1000; border-top-left-radius:5px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
    <div style="margin-bottom:5px; font-weight:bold; border-bottom:1px solid rgba(255,255,255,0.3); padding-bottom:2px;">Diagnóstico de Alternancia</div>
    <p style="margin:2px 0;"><span style="color:#ffcc00;">Mostrando ahora:</span> {{ !empty($videoUrl) ? '📹 Video' : (!empty($imagenes) ? '🖼️ Imágenes ('.count($imagenes).')' : 'Sin multimedia') }}</p>
    <p style="margin:2px 0;"><span style="color:#ffcc00;">Método configurado:</span> {{ ucfirst($zona->seleccion_campanas ?? 'aleatorio') }}</p>
    <p style="margin:2px 0;"><span style="color:#ffcc00;">Último tipo (cookie):</span> {{ \Illuminate\Support\Facades\Cookie::get('ultimo_tipo_zona_' . $zona->id) ?? 'sin valor' }}</p>
    <p style="margin:2px 0;"><span style="color:#ffcc00;">Último tipo (sesión):</span> {{ session('ultimo_tipo_mostrado_' . $zona->id, 'sin valor') }}</p>
    <p style="margin:2px 0;"><span style="color:#ffcc00;">ID de Sesión:</span> {{ substr(session()->getId(), 0, 8) }}...</p>
    <p style="margin:2px 0; font-size:9px; color:#aaa;">[{{ now()->format('H:i:s') }}]</p>
</div>
