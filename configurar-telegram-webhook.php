<?php
// Script para configurar webhook en bots de Telegram registrados
// Ejecutar: php configurar-telegram-webhook.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Iniciando configuración de webhooks para bots de Telegram...\n\n";

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

// Obtener la ruta del webhook desde la configuración
$webhookPath = config('telegraph.webhook.url', '/telegram/webhook');

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
            // Verificar si el bot es válido
            $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
            $telegraph = $telegraph->bot($bot);

            // Verificar información del bot
            $botInfoResponse = $telegraph->botInfo()->send();

            if (isset($botInfoResponse['ok']) && $botInfoResponse['ok'] === true) {
                $botInfo = $botInfoResponse['result'];
                echo "   ✓ Bot válido: {$botInfo['username']} ({$botInfo['id']})\n";

                // Construir la URL del webhook
                // La URL debe ser de la forma: https://tudominio.com/telegraph/{token}/webhook
                $webhookUrl = rtrim($baseUrl, '/') . '/telegraph/' . $bot->token . '/webhook';

                echo "   🔗 Configurando webhook URL: {$webhookUrl}\n";

                // Configurar el webhook
                $response = $telegraph->setWebhook($webhookUrl)->send();

                if (isset($response['ok']) && $response['ok'] === true) {
                    echo "   ✅ Webhook configurado con éxito\n";

                    // Actualizar la URL del webhook en la base de datos
                    $bot->webhook_url = $webhookUrl;
                    $bot->save();

                    echo "   ✓ URL de webhook guardada en la base de datos\n";
                } else {
                    echo "   ❌ Error al configurar webhook: " . json_encode($response) . "\n";
                }

                // Verificar la configuración actual
                $webhookInfoResponse = $telegraph->getWebhookInfo()->send();

                if (isset($webhookInfoResponse['ok']) && $webhookInfoResponse['ok'] === true) {
                    $webhookInfo = $webhookInfoResponse['result'];
                    echo "   📊 Información del webhook:\n";
                    echo "      URL: " . ($webhookInfo['url'] ?? 'No configurado') . "\n";
                    echo "      Pendientes: " . ($webhookInfo['pending_update_count'] ?? 0) . " actualizaciones\n";

                    if (isset($webhookInfo['last_error_date'])) {
                        $errorDate = date('Y-m-d H:i:s', $webhookInfo['last_error_date']);
                        $errorMessage = $webhookInfo['last_error_message'] ?? 'No hay mensaje';
                        echo "      Último error: {$errorDate} - {$errorMessage}\n";
                    }
                } else {
                    echo "   ❌ Error al obtener información del webhook: " . json_encode($webhookInfoResponse) . "\n";
                }
            } else {
                echo "   ❌ Error al verificar bot: " . json_encode($botInfoResponse) . "\n";
                echo "      Posible causa: Token inválido o bot no accesible\n";
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
echo "4. Ejecute php check-telegraph-bots.php para confirmar la configuración\n";
echo "5. Use php verificar-y-solucionar-webhook-telegram.php para análisis adicional\n";
?>
