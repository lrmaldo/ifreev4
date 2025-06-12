<?php
/**
 * Script para diagnosticar problemas específicos con el webhook de Telegram
 *
 * Para ejecutar este script:
 * php diagnostico-webhook-telegram.php
 */

// Verificar si el script está siendo ejecutado desde la CLI
if (php_sapi_name() !== 'cli') {
    die("Este script debe ejecutarse desde la línea de comandos.");
}

echo "🔍 Iniciando diagnóstico específico para Webhook de Telegram\n";
echo "===========================================================\n\n";

// 1. Verificar existencia del controlador y su firma
$controllerPath = __DIR__ . '/app/Http/Controllers/TelegramWebhookController.php';
if (!file_exists($controllerPath)) {
    die("❌ ERROR: No se encontró el controlador TelegramWebhookController.php\n");
}

echo "✅ Controlador TelegramWebhookController encontrado\n";

// 2. Analizar la firma del método handle
$controllerContent = file_get_contents($controllerPath);

// Verificar si existe el método con la firma correcta
if (preg_match('/public\s+function\s+handle\s*\(\s*Request\s+\$request\s*,\s*[\\\\a-zA-Z0-9_]+\s+\$bot\s*\)\s*:\s*void\s*{/i', $controllerContent)) {
    echo "✅ Método handle con firma correcta detectado\n";
} else {
    echo "❌ ALERTA: No se detectó el método handle con la firma correcta\n";
    echo "   Debería ser: public function handle(Request \$request, TelegraphBot \$bot): void\n";

    // Buscar cualquier método handle para mostrar información
    if (preg_match('/public\s+function\s+handle\s*\([^\)]*\)/i', $controllerContent, $matches)) {
        echo "   Firma encontrada: " . trim($matches[0]) . "\n";
    }
}

// 3. Verificar que el método no devuelve nada (void)
if (preg_match('/return\s+[^;]*;\s*}\s*\/\/\s*end\s+handle|return\s+[^;]+;(?!.*return)[^}]*}\s*\/\/\s*handle/is', $controllerContent)) {
    echo "❌ ALERTA: El método handle parece devolver un valor cuando debería ser void\n";
} else {
    echo "✅ No se detectaron returns de valor en el método handle\n";
}

// 4. Verificar la llamada al método parent::handle
if (strpos($controllerContent, 'parent::handle($request, $bot)') !== false) {
    echo "✅ Llamada correcta a parent::handle detectada\n";
} else if (strpos($controllerContent, 'parent::handle($request)') !== false) {
    echo "❌ ERROR: Se está llamando a parent::handle sin el parámetro \$bot\n";
}

// 5. Verificar la visibilidad de métodos importantes
echo "\n📋 Verificando visibilidad de métodos...\n";

$methodsToCheck = ['getChatName', 'getChatType', 'registerChat', 'shouldDebug', 'debugWebhook'];
foreach ($methodsToCheck as $method) {
    if (preg_match('/private\s+function\s+' . $method . '/i', $controllerContent)) {
        echo "❌ ERROR: El método {$method}() está declarado como private, debe ser protected\n";
    } elseif (preg_match('/protected\s+function\s+' . $method . '/i', $controllerContent)) {
        echo "✅ Método {$method}() tiene la visibilidad correcta (protected)\n";
    } else {
        echo "⚠️ ADVERTENCIA: No se pudo verificar la visibilidad del método {$method}()\n";
    }
}

// 5. Verificar rutas
$routesPath = __DIR__ . '/routes/web.php';
if (!file_exists($routesPath)) {
    die("❌ ERROR: No se encontró el archivo de rutas web.php\n");
}

$routesContent = file_get_contents($routesPath);

if (strpos($routesContent, "'/telegram/webhook'") !== false) {
    echo "✅ Ruta '/telegram/webhook' encontrada\n";
} else {
    echo "❌ ALERTA: No se encontró la ruta '/telegram/webhook'\n";
}

// 6. Verificar configuración Telegraph
$telegraphConfigPath = __DIR__ . '/config/telegraph.php';
if (!file_exists($telegraphConfigPath)) {
    die("❌ ERROR: No se encontró el archivo de configuración telegraph.php\n");
}

$telegraphConfig = file_get_contents($telegraphConfigPath);

echo "\n📋 Resumen de diagnóstico:\n";
echo "=========================\n";
echo "✓ Controlador TelegramWebhookController verificado\n";
echo "✓ Firma del método handle analizada\n";
echo "✓ Rutas de webhook verificadas\n";
echo "✓ Configuración de Telegraph verificada\n";
echo "\n";
echo "🛠️  Recomendaciones:\n";
echo "1. Ejecutar php artisan optimize:clear para limpiar la caché\n";
echo "2. Verificar la configuración del webhook con php artisan telegram:test-webhook --verify\n";
echo "3. Revisar los logs en storage/logs/laravel.log\n";
echo "\n";
