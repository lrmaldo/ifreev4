@php $evento = $zona->portal_tema === 'evento'; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Vista previa · {{ $zona->nombre }}</title>
    <style>
        :root { --fondo: {{ $evento ? '#071229' : '#f9fafb' }}; --tarjeta: {{ $evento ? '#0f2148' : '#ffffff' }}; --texto: {{ $evento ? '#ffffff' : '#1f2937' }}; --texto-2: {{ $evento ? '#c7d2ea' : '#6b7280' }}; --acento: {{ $evento ? '#f5b82e' : '#ff5e2c' }}; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: var(--fondo); color: var(--texto); font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .caja { width: min(440px, 100%); padding: 32px 24px; border-radius: 16px; background: var(--tarjeta); text-align: center; box-shadow: 0 20px 40px rgba(0, 0, 0, .25); }
        .icono { width: 64px; height: 64px; margin: 0 auto 16px; display: grid; place-items: center; border-radius: 50%; background: var(--acento); color: {{ $evento ? '#1a1300' : '#ffffff' }}; font-size: 32px; font-weight: 700; }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { margin: 0 0 12px; color: var(--texto-2); line-height: 1.5; }
        .aviso { margin-top: 20px; padding: 10px 12px; border-radius: 8px; border: 1px dashed var(--texto-2); font-size: 13px; }
        a { display: inline-block; margin-top: 16px; color: var(--acento); font-weight: 600; }
    </style>
</head>
<body>
    <div class="caja">
        <div class="icono" aria-hidden="true">✓</div>
        <h1>¡Listo, ya tienes internet!</h1>
        <p>En el portal real, en este punto el MikroTik le da acceso a internet a la persona y la lleva a la página que quería visitar.</p>
        <p class="aviso">Esto es una vista previa de <strong>{{ $zona->nombre }}</strong>: no se guardó ningún dato ni se contó como visita.</p>
        <a href="{{ route('cliente.zona.preview', ['id' => $zona->id]) }}">Volver a ver el portal</a>
    </div>
</body>
</html>
