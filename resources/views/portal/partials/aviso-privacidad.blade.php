{{-- Aviso de privacidad simplificado del portal cautivo. Texto base: revisarlo con un abogado. --}}
@php
    $privacidad = config('ifree.privacidad');
    $responsable = $zona->user?->cliente?->nombre_comercial ?: $privacidad['responsable'];
@endphp
<style>
    .aviso-privacidad { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(0, 0, 0, .6); }
    .aviso-privacidad.hidden { display: none; }
    .aviso-privacidad-contenido { max-width: 480px; max-height: 85vh; overflow-y: auto; padding: 20px; border-radius: 12px; background: #fff; color: #1f2937; font-size: 14px; line-height: 1.5; }
    .aviso-privacidad-contenido h2 { margin: 0 0 12px; font-size: 18px; font-weight: 700; }
    .aviso-privacidad-contenido p { margin: 0 0 10px; }
    .aceptar-privacidad { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; }
    .aceptar-privacidad input { margin-top: 3px; width: 18px; height: 18px; flex: none; accent-color: var(--color-primary, #ff5e2c); }
    .aceptar-privacidad a { text-decoration: underline; cursor: pointer; }
</style>
<div id="aviso-privacidad" class="aviso-privacidad hidden" role="dialog" aria-modal="true" aria-labelledby="aviso-privacidad-titulo">
    <div class="aviso-privacidad-contenido">
        <h2 id="aviso-privacidad-titulo">Aviso de privacidad simplificado</h2>
        <p><strong>{{ $responsable }}</strong>@if($privacidad['domicilio']), con domicilio en {{ $privacidad['domicilio'] }},@endif es responsable del tratamiento de los datos personales que proporcionas en este formulario.</p>
        <p><strong>Finalidad principal:</strong> darte acceso a la red WiFi de este lugar.</p>
        <p><strong>Finalidades adicionales:</strong> elaborar estadísticas de uso de la red y enviarte información o promociones. Si no deseas que tus datos se usen para estas finalidades, puedes indicarlo
            @if($privacidad['contacto']) escribiendo a {{ $privacidad['contacto'] }}@else al responsable @endif;
            tu negativa no impide que te conectes.</p>
        <p>Si en este lugar hay una rifa o dinámica, al registrarte participas en ella, y tu nombre con la inicial de tu apellido, junto con los últimos 4 dígitos de tu teléfono, podrán mostrarse en una pantalla del evento.</p>
        <p>Además de lo que escribas en el formulario, se registran datos técnicos de tu dispositivo (dirección MAC, modelo, sistema operativo y navegador).</p>
        <p>Puedes ejercer tus derechos de acceso, rectificación, cancelación y oposición (ARCO)
            @if($privacidad['contacto']) en {{ $privacidad['contacto'] }}@else con el responsable @endif.
            @if($privacidad['url_aviso_integral']) Consulta el aviso de privacidad integral en {{ $privacidad['url_aviso_integral'] }}.@endif
        </p>
        <button type="button" class="btn-primary" onclick="document.getElementById('aviso-privacidad').classList.add('hidden')">Entendido</button>
    </div>
</div>
