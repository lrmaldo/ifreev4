<?php
// Script para configurar webhook en bots de Telegram registrados
// Ejecutar: php configurar-telegram-webhook-curl.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Iniciando configuración de webhooks para bots de Telegram (usando CURL)...\n\n";

// Obtener la URL base de la aplicación
$baseUrl = config('app.url');
echo "📌 URL base de la aplicación: {$baseUrl}\n";

if (empty($baseUrl)) {
    echo "❌ ERROR: La URL base no está configurada en config/app.php\n";
    echo "   Por favor configure la URL base en el archivo .env (APP_URL)\n";
    exit(1);
}

// Verificar que la URL tiene formato correcto
if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
    echo "❌ ERROR: La URL base no parece ser válida: {$baseUrl}\n";
    echo "   Por favor configure una URL válida en el archivo .env (APP_URL)\n";
    exit(1);
}

// Buscar todos los bots registrados
try {
    $bots = \DefStudio\Telegraph\Models\TelegraphBot::all();

    if ($bots->isEmpty()) {
        echo "❌ No se encontraron bots registrados en la base de datos\n";
        exit(1);
    }

    echo "✅ Se encontraron " . $bots->count() . " bots registrados\n\n";

    // Para cada bot, configurar el webhook
    foreach ($bots as $bot) {
        echo "🤖 Configurando webhook para bot: {$bot->name} (ID: {$bot->id})\n";
        echo "   Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n";

        try {
            // Verificar si el bot es válido usando curl
            echo "   🔍 Verificando información del bot...\n";

            // Hacer petición para verificar el bot
            $ch = curl_init("https://api.telegram.org/bot{$bot->token}/getMe");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                echo "   ❌ Error HTTP {$httpCode} al verificar bot\n";
                echo "      Respuesta: {$response}\n";
                continue;
            }

            $botInfo = json_decode($response, true);

            if (!isset($botInfo['ok']) || $botInfo['ok'] !== true) {
                echo "   ❌ Error al verificar bot: " . json_encode($botInfo) . "\n";
                continue;
            }

            echo "   ✓ Bot válido: {$botInfo['result']['username']} ({$botInfo['result']['id']})\n";

            // Construir la URL del webhook
            $webhookUrl = rtrim($baseUrl, '/') . '/telegraph/' . $bot->token . '/webhook';

            echo "   🔗 Configurando webhook URL: {$webhookUrl}\n";

            // Configurar el webhook usando curl
            $ch = curl_init("https://api.telegram.org/bot{$bot->token}/setWebhook");
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
                echo "   ❌ Error HTTP {$httpCode} al configurar webhook\n";
                echo "      Respuesta: {$response}\n";
                continue;
            }

            $webhookResponse = json_decode($response, true);

            if (isset($webhookResponse['ok']) && $webhookResponse['ok'] === true) {
                echo "   ✅ Webhook configurado con éxito\n";

                // Verificar si la columna webhook_url existe antes de intentar guardarla
                try {
                    $hasWebhookUrlColumn = \Illuminate\Support\Facades\Schema::hasColumn('telegraph_bots', 'webhook_url');

                    if ($hasWebhookUrlColumn) {
                        // La columna existe, podemos guardar la URL
                        $bot->webhook_url = $webhookUrl;
                        $bot->save();
                        echo "   ✓ URL de webhook guardada en la base de datos\n";
                    } else {
                        echo "   ⚠️ La columna 'webhook_url' no existe en la tabla telegraph_bots\n";
                        echo "      La URL no se guardará en la base de datos, pero el webhook está configurado correctamente\n";

                        // Crear un archivo con la información de configuración para referencia
                        $configFile = __DIR__ . '/storage/app/telegram_webhook_config.json';
                        $configData = json_decode(file_exists($configFile) ? file_get_contents($configFile) : '[]') ?? [];
                        $configData[] = [
                            'bot_id' => $bot->id,
                            'bot_name' => $bot->name,
                            'webhook_url' => $webhookUrl,
                            'configured_at' => date('Y-m-d H:i:s')
                        ];
                        @file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
                        echo "      Información guardada en: storage/app/telegram_webhook_config.json\n";
                    }
                } catch (\Exception $schemaException) {
                    echo "   ❌ Error al verificar esquema de la base de datos: " . $schemaException->getMessage() . "\n";
                    echo "      La URL de webhook no se guardará, pero el webhook está configurado correctamente\n";
                }
            } else {
                echo "   ❌ Error al configurar webhook: " . json_encode($webhookResponse) . "\n";
            }

            // Verificar la configuración actual usando curl
            echo "   🔍 Verificando configuración actual del webhook...\n";

            $ch = curl_init("https://api.telegram.org/bot{$bot->token}/getWebhookInfo");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                echo "   ❌ Error HTTP {$httpCode} al verificar configuración de webhook\n";
                echo "      Respuesta: {$response}\n";
                continue;
            }

            $webhookInfo = json_decode($response, true);

            if (isset($webhookInfo['ok']) && $webhookInfo['ok'] === true) {
                $webhookInfo = $webhookInfo['result'];
                echo "   📊 Información del webhook:\n";
                echo "      URL: " . ($webhookInfo['url'] ?? 'No configurado') . "\n";
                echo "      Pendientes: " . ($webhookInfo['pending_update_count'] ?? 0) . " actualizaciones\n";

                if (isset($webhookInfo['last_error_date'])) {
                    $errorDate = date('Y-m-d H:i:s', $webhookInfo['last_error_date']);
                    $errorMessage = $webhookInfo['last_error_message'] ?? 'No hay mensaje';
                    echo "      Último error: {$errorDate} - {$errorMessage}\n";
                }
            } else {
                echo "   ❌ Error al obtener información del webhook: " . json_encode($webhookInfo) . "\n";
            }

        } catch (\Exception $e) {
            echo "   ❌ Excepción al configurar webhook: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error al acceder a la base de datos: " . $e->getMessage() . "\n";
}

echo "\n🏁 Proceso completado.\n";
echo "\n📚 Pasos adicionales recomendados:\n";
echo "1. Verifique que el webhook está funcionando enviando un mensaje de prueba al bot\n";
echo "2. Si no funciona, asegúrese de que la URL sea accesible desde Internet\n";
echo "3. Verifique que el servidor pueda conectarse a la API de Telegram (api.telegram.org)\n";
echo "4. Ejecute php verificar-webhook-telegram.php para confirmar la configuración\n";
?>
