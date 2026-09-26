<?php
// Script para verificar el estado del webhook configurado para un bot de Telegram
// Ejecutar: php verificar-webhook-telegram.php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Verificando webhook del bot de Telegram...\n\n";

try {
    // Obtener el primer bot
    $bot = \DefStudio\Telegraph\Models\TelegraphBot::first();

    if (!$bot) {
        echo "❌ No se encontró ningún bot registrado en la base de datos.\n";
        exit(1);
    }

    echo "🤖 Bot encontrado: {$bot->name} (ID: {$bot->id})\n";
    echo "   Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n\n";

    // Verificar el webhook usando curl
    echo "📡 Comprobando webhook usando curl directo...\n";
    $ch = curl_init("https://api.telegram.org/bot{$bot->token}/getWebhookInfo");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "❌ Error HTTP {$httpCode} al obtener información del webhook\n";
        echo "   Respuesta: {$response}\n";
        exit(1);
    }

    $webhookInfo = json_decode($response, true);

    if (!$webhookInfo || !isset($webhookInfo['ok']) || $webhookInfo['ok'] !== true) {
        echo "❌ Error al obtener información del webhook: " . json_encode($webhookInfo) . "\n";
        exit(1);
    }

    // Mostrar información del webhook
    echo "✅ Información del webhook obtenida correctamente\n";
    echo "   URL: " . ($webhookInfo['result']['url'] ?? 'No configurada') . "\n";

    if (!empty($webhookInfo['result']['url'])) {
        echo "   ✅ El webhook está configurado\n";
    } else {
        echo "   ❌ El webhook no está configurado\n";
    }

    echo "   Actualizaciones pendientes: " . ($webhookInfo['result']['pending_update_count'] ?? 0) . "\n";

    // Verificar errores recientes
    if (isset($webhookInfo['result']['last_error_date'])) {
        $errorDate = date('Y-m-d H:i:s', $webhookInfo['result']['last_error_date']);
        $errorMessage = $webhookInfo['result']['last_error_message'] ?? 'No hay mensaje';
        echo "   ⚠️ Último error: {$errorDate} - {$errorMessage}\n";
    } else {
        echo "   ✅ No hay errores recientes reportados\n";
    }

    // Verificar si el webhook está activo para estos comandos
    echo "\n📋 Comandos registrados en el webhook:\n";

    $allowedUpdates = $webhookInfo['result']['allowed_updates'] ?? [];
    if (empty($allowedUpdates)) {
        echo "   ✅ Todos los tipos de actualizaciones están permitidos (comportamiento por defecto)\n";
    } else {
        foreach ($allowedUpdates as $updateType) {
            echo "   - {$updateType}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Verificación completada.\n";
echo "\n📚 Pasos adicionales para probar el webhook:\n";
echo "1. Envíe un mensaje al bot en Telegram\n";
echo "2. Revise los logs en storage/logs/laravel.log para ver si se recibió la petición\n";
echo "3. Si los comandos no funcionan, asegúrese de que el bot tenga los comandos registrados\n";
echo "   php artisan telegraph:commands [BOT_ID] [/start /ayuda /zonas /registrar]\n";
?>
