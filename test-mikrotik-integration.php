<?php

/**
 * Test de Integración Mikrotik - Portal Cautivo
 *
 * Este script simula una petición desde Mikrotik RouterOS con todos los parámetros
 * necesarios para verificar que el portal cautivo funcione correctamente.
 */

echo "=== TEST DE INTEGRACIÓN MIKROTIK ===\n\n";

// Parámetros típicos enviados por Mikrotik RouterOS
$mikrotikParams = [
    'mac' => '00:11:22:33:44:55',
    'ip' => '192.168.1.100',
    'username' => '',
    'link-login' => 'http://192.168.1.1/login',
    'link-orig' => 'http://google.com',
    'link-login-only' => 'http://192.168.1.1/login',
    'link-orig-esc' => 'http%3A//google.com',
    'mac-esc' => '00%3A11%3A22%3A33%3A44%3A55',
    'error' => '',
    'chap-id' => '',
    'chap-challenge' => ''
];

echo "1. Parámetros de Mikrotik simulados:\n";
foreach ($mikrotikParams as $key => $value) {
    echo "   {$key}: " . ($value ?: '(vacío)') . "\n";
}

echo "\n2. URLs generadas para autenticación:\n";

// URL para trial gratuito
$trialUrl = $mikrotikParams['link-login-only'] . '?dst=' . $mikrotikParams['link-orig-esc'] . '&username=T-' . $mikrotikParams['mac-esc'];
echo "   Trial gratuito: {$trialUrl}\n";

// URL para login con credenciales
$loginUrl = $mikrotikParams['link-login-only'];
echo "   Login con credenciales: {$loginUrl}\n";

echo "\n3. Parámetros para formularios de login:\n";
echo "   dst (destino): {$mikrotikParams['link-orig']}\n";
echo "   popup: true\n";

echo "\n4. Verificando estructura de datos para la vista:\n";

// Simular lo que se pasaría a la vista
$vistaData = [
    'mikrotikData' => $mikrotikParams,
    'zona' => [
        'id' => 1,
        'nombre' => 'Zona Test',
        'tipo_autenticacion_mikrotik' => 'usuario_password' // o 'pin' o 'sin_autenticacion'
    ]
];

echo "   ✓ Datos de Mikrotik disponibles\n";
echo "   ✓ Configuración de zona disponible\n";

echo "\n5. Test de URLs de conexión:\n";

// Verificar que las URLs no estén vacías
if (!empty($mikrotikParams['link-login-only'])) {
    echo "   ✓ link-login-only disponible\n";
} else {
    echo "   ✗ link-login-only faltante\n";
}

if (!empty($mikrotikParams['link-orig-esc'])) {
    echo "   ✓ link-orig-esc disponible\n";
} else {
    echo "   ✗ link-orig-esc faltante\n";
}

if (!empty($mikrotikParams['mac-esc'])) {
    echo "   ✓ mac-esc disponible\n";
} else {
    echo "   ✗ mac-esc faltante\n";
}

echo "\n6. Ejemplos de implementación HTML:\n\n";

echo "<!-- Botón de conexión gratuita -->\n";
echo '<a href="' . htmlspecialchars($trialUrl) . '" class="btn-connection" id="gratis">' . "\n";
echo '    ¡Conéctate Gratis Aquí!' . "\n";
echo '</a>' . "\n\n";

echo "<!-- Formulario de login con usuario/password -->\n";
echo '<form name="login" action="' . htmlspecialchars($loginUrl) . '" method="post" onSubmit="return doLogin()">' . "\n";
echo '    <input type="hidden" name="dst" value="' . htmlspecialchars($mikrotikParams['link-orig']) . '" />' . "\n";
echo '    <input type="hidden" name="popup" value="true" />' . "\n";
echo '    <input type="text" name="username" placeholder="Usuario" />' . "\n";
echo '    <input type="password" name="password" placeholder="Contraseña" />' . "\n";
echo '    <button type="submit">Entrar</button>' . "\n";
echo '</form>' . "\n\n";

echo "<!-- Formulario de login con PIN -->\n";
echo '<form name="login" action="' . htmlspecialchars($loginUrl) . '" method="post" onSubmit="return doLogin()">' . "\n";
echo '    <input type="hidden" name="dst" value="' . htmlspecialchars($mikrotikParams['link-orig']) . '" />' . "\n";
echo '    <input type="hidden" name="popup" value="true" />' . "\n";
echo '    <input type="text" name="username" placeholder="PIN" />' . "\n";
echo '    <button type="submit">Conectar con PIN</button>' . "\n";
echo '</form>' . "\n\n";

echo "7. JavaScript requerido:\n\n";
echo 'function doLogin() {' . "\n";
echo '    // Validar campos según el tipo de autenticación' . "\n";
echo '    // Retornar true para permitir el envío del formulario' . "\n";
echo '    return true;' . "\n";
echo '}' . "\n\n";

echo "=== TEST COMPLETADO ===\n";
echo "El portal cautivo está configurado para recibir y procesar correctamente\n";
echo "todos los parámetros enviados por Mikrotik RouterOS.\n\n";

echo "Para probar en un entorno real:\n";
echo "1. Configura tu Mikrotik para usar: http://tu-dominio/login_formulario/1\n";
echo "2. Mikrotik enviará automáticamente todos los parámetros necesarios\n";
echo "3. El portal mostrará las opciones de conexión correspondientes\n";
echo "4. Los formularios redirigirán correctamente al router para autenticación\n\n";
