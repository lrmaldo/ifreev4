<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>En vivo · {{ $zona->nombre }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts-local.css') }}">
</head>
<body class="pev-body">
    <livewire:evento.pantalla-en-vivo :token="$token" />
</body>
</html>
