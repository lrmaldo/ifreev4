<?php
// Script para insertar directamente un bot de Telegram en la base de datos
// Ejecutar: php insertar-bot-telegram.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🤖 Insertando bot de Telegram en la base de datos...\n\n";

// Configuración del bot
$botName = 'iFreeBotV3.0';
$botToken = '7873181208:AAFR3vuwPXbGchzfw1XFwTZjjrNRxeHDqzA'; // Token del bot ya registrado
$baseUrl = 'https://v3.i-free.com.mx'; // URL fija para producción

// Construir la URL del webhook
$webhookUrl = rtrim($baseUrl, '/') . '/telegraph/' . $botToken . '/webhook';

try {
    // Verificar si la tabla existe
    $tablesExist = DB::select("SHOW TABLES LIKE 'telegraph_bots'");

    if (empty($tablesExist)) {
        echo "❌ La tabla telegraph_bots no existe en la base de datos\n";
        echo "   Ejecutando migraciones...\n";

        // Ejecutar migraciones si no existen las tablas
        $exitCode = null;
        passthru('php artisan migrate', $exitCode);

        if ($exitCode !== 0) {
            echo "❌ Error al ejecutar las migraciones\n";
            exit(1);
        }

        echo "✅ Migraciones ejecutadas correctamente\n\n";
    }

    // Verificar si el bot ya existe
    $existingBot = DB::table('telegraph_bots')
        ->where('token', $botToken)
        ->first();

    if ($existingBot) {
        echo "📋 El bot ya existe en la base de datos:\n";
        echo "   ID: {$existingBot->id}\n";
        echo "   Nombre: {$existingBot->name}\n";
        echo "   Token: " . substr($botToken, 0, 6) . "..." . substr($botToken, -5) . "\n";

        // Actualizar la URL del webhook
        DB::table('telegraph_bots')
            ->where('id', $existingBot->id)
            ->update([
                'webhook_url' => $webhookUrl,
                'updated_at' => now()
            ]);

        echo "✅ URL del webhook actualizada a: {$webhookUrl}\n";
    } else {
        // Insertar el bot
        $botId = DB::table('telegraph_bots')->insertGetId([
            'name' => $botName,
            'token' => $botToken,
            'webhook_url' => $webhookUrl,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        echo "✅ Bot insertado correctamente:\n";
        echo "   ID: {$botId}\n";
        echo "   Nombre: {$botName}\n";
        echo "   Token: " . substr($botToken, 0, 6) . "..." . substr($botToken, -5) . "\n";
        echo "   Webhook URL: {$webhookUrl}\n";
    }

    // Configurar el webhook en Telegram
    echo "\n📡 Configurando webhook en Telegram API...\n";

    $ch = curl_init("https://api.telegram.org/bot{$botToken}/setWebhook");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'url' => $webhookUrl,
        'max_connections' => 40
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "❌ Error HTTP {$httpCode} al configurar el webhook\n";
        echo "   Respuesta: {$response}\n";
        exit(1);
    }

    $webhookResponse = json_decode($response, true);

    if (isset($webhookResponse['ok']) && $webhookResponse['ok'] === true) {
        echo "✅ Webhook configurado con éxito en Telegram API\n";

        // Verificar la configuración del webhook
        $ch = curl_init("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $webhookInfo = json_decode($response, true);

            if (isset($webhookInfo['ok']) && $webhookInfo['ok'] === true) {
                $info = $webhookInfo['result'];

                echo "\n📋 Información del webhook:\n";
                echo "   URL: " . ($info['url'] ?? 'No configurada') . "\n";
                echo "   Has custom certificate: " . ($info['has_custom_certificate'] ? 'Sí' : 'No') . "\n";
                echo "   Pending updates: " . ($info['pending_update_count'] ?? 0) . "\n";

                if (isset($info['last_error_date']) && isset($info['last_error_message'])) {
                    $errorDate = date('Y-m-d H:i:s', $info['last_error_date']);
                    echo "   ⚠️ Último error: {$info['last_error_message']} ({$errorDate})\n";
                } else {
                    echo "   ✅ Sin errores reportados\n";
                }
            }
        }
    } else {
        echo "❌ Error al configurar webhook: " . json_encode($webhookResponse) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Traza: " . $e->getTraceAsString() . "\n";

    // Registrar el error en los logs
    Log::error('Error al insertar bot de Telegram', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n✅ Proceso completado.\n";
