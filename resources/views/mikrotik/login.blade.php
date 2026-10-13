{{--
    login.html que se sube al MikroTik (hotspot → HTML Directory). El MikroTik reemplaza las $(variables).

    Primero pregunta a {{ $portalUrl }}/portal/ping si el servidor responde. Si sí, manda al asistente
    al portal como siempre. Si no responde en {{ $timeout }} s (servidor caído, saturado o sin DNS),
    usa el respaldo para que nadie se quede sin WiFi:
      - zonas sin autenticación: conecta directo con el trial del MikroTik (sin anuncio ni registro);
      - zonas con usuario/PIN: muestra el login del propio MikroTik.

    JavaScript ES5 a propósito: lo abren los mini navegadores de "iniciar sesión en la red" de los celulares.
    Generado por i-Free para la zona {{ $zonaId }}.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="-1">
    <title>Conectando…</title>
    <style>
        body { margin: 0; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; background: #f4f4f5; color: #18181b; }
        .caja { max-width: 360px; margin: 18vh auto 0; padding: 28px 24px; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); text-align: center; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { font-size: 15px; color: #52525b; margin: 0 0 16px; line-height: 1.4; }
        .giro { width: 40px; height: 40px; margin: 8px auto 16px; border: 4px solid #e4e4e7; border-top-color: #ff3f00; border-radius: 50%; animation: g 1s linear infinite; }
        @keyframes g { to { transform: rotate(360deg); } }
        input[type=text], input[type=password] { box-sizing: border-box; width: 100%; padding: 12px; margin: 0 0 10px; font-size: 16px; border: 1px solid #d4d4d8; border-radius: 10px; }
        button, input[type=submit] { width: 100%; padding: 12px; font-size: 16px; font-weight: bold; color: #fff; background: #ff3f00; border: 0; border-radius: 10px; }
        .enlace { display: inline-block; margin-top: 12px; color: #71717a; font-size: 14px; background: none; width: auto; padding: 0; font-weight: normal; }
        .oculto { display: none; }
    </style>
</head>
<body>
    <div class="caja">
        <div id="esperando">
            <div class="giro"></div>
            <h1>Conectando…</h1>
            <p>Estamos abriendo el portal de WiFi gratis.</p>
        </div>

        <div id="directo" class="oculto">
            <div class="giro"></div>
            <h1>Te estamos conectando</h1>
            <p>El portal tarda en responder, así que te conectamos directo.</p>
        </div>

        <div id="manual" class="oculto">
            <h1>Inicia sesión</h1>
            <p>El portal no está disponible en este momento. Ingresa con tu usuario.</p>
            <form name="login" action="$(link-login-only)" method="post">
                <input type="hidden" name="dst" value="$(link-orig)">
                <input type="hidden" name="popup" value="false">
                <input type="text" name="username" placeholder="Usuario o PIN" autocomplete="username" autocapitalize="off">
                <input type="password" name="password" placeholder="Contraseña" autocomplete="current-password">
                <input type="submit" value="Conectarme">
            </form>
            <button type="button" class="enlace" onclick="location.reload()">Reintentar el portal</button>
        </div>

        <form name="redirect" action="{{ $portalUrl }}/login_formulario/{{ $zonaId }}" method="post">
            <input type="hidden" name="mac" value="$(mac)">
            <input type="hidden" name="ip" value="$(ip)">
            <input type="hidden" name="username" value="$(username)">
            <input type="hidden" name="link-login" value="$(link-login)">
            <input type="hidden" name="link-orig" value="$(link-orig)">
            <input type="hidden" name="error" value="$(error)">
            <input type="hidden" name="chap-id" value="$(chap-id)">
            <input type="hidden" name="chap-challenge" value="$(chap-challenge)">
            <input type="hidden" name="link-login-only" value="$(link-login-only)">
            <input type="hidden" name="link-orig-esc" value="$(link-orig-esc)">
            <input type="hidden" name="mac-esc" value="$(mac-esc)">
            <noscript><input type="submit" value="Continuar"></noscript>
        </form>
    </div>

    <script>
    (function () {
        var PING = '{{ $portalUrl }}/portal/ping';
        var TIMEOUT = {{ $timeout * 1000 }};
        var TRIAL = {{ $trial ? 'true' : 'false' }};
        var listo = false;

        function mostrar(id) {
            var ids = ['esperando', 'directo', 'manual'];
            for (var i = 0; i < ids.length; i++) {
                document.getElementById(ids[i]).className = ids[i] === id ? '' : 'oculto';
            }
        }

        function alPortal() {
            if (listo) { return; }
            listo = true;
            document.redirect.submit();
        }

        function respaldo() {
            if (listo) { return; }
            listo = true;
            if (TRIAL) {
                mostrar('directo');
                location.href = '$(link-login-only)?dst=$(link-orig-esc)&username=T-$(mac-esc)';
            } else {
                mostrar('manual');
            }
        }

        function probar(intento) {
            var xhr;
            try {
                xhr = new XMLHttpRequest();
                xhr.open('GET', PING + '?t=' + new Date().getTime(), true);
                xhr.timeout = TIMEOUT;
            } catch (e) {
                // Navegador sin XHR/CORS: como antes, directo al portal
                alPortal();
                return;
            }
            var fallo = function () {
                if (intento < 2) { setTimeout(function () { probar(intento + 1); }, 800); } else { respaldo(); }
            };
            xhr.onload = function () { if (xhr.status === 200) { alPortal(); } else { fallo(); } };
            xhr.onerror = fallo;
            xhr.ontimeout = fallo;
            xhr.send();
        }

        // Si algo se atora, que nunca se quede esperando indefinidamente
        setTimeout(respaldo, TIMEOUT * 2 + 3000);
        probar(1);
    })();
    </script>
</body>
</html>
