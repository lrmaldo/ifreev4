<?php
// Script para configurar webhook en bots de Telegram registrados
// Ejecutar: php configurar-telegram-webhook.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Iniciando configuración de webhooks para bots de Telegram...\n\n";

// Verificar la versión de Telegraph instalada
try {
    $telegraphClass = new \ReflectionClass(\DefStudio\Telegraph\Telegraph::class);
    echo "ℹ️ Versión de Telegraph: ";

    // Intentar obtener la versión desde un posible atributo o constante
    if (defined('\DefStudio\Telegraph\Telegraph::VERSION')) {
        echo \DefStudio\Telegraph\Telegraph::VERSION . "\n";
    } else {
        // Si no hay VERSION constante, verificamos métodos disponibles
        $methods = $telegraphClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(function ($method) {
            return $method->getName();
        }, $methods);

        echo "No detectada (métodos disponibles: " .
            (in_array('registerWebhook', $methodNames) ? 'registerWebhook✓' : 'registerWebhook✗') . ", " .
            (in_array('setWebhook', $methodNames) ? 'setWebhook✓' : 'setWebhook✗') . ", " .
            (in_array('botInfo', $methodNames) ? 'botInfo✓' : 'botInfo✗') . ")\n";
    }
} catch (\ReflectionException $e) {
    echo "⚠️ No se pudo detectar información de la versión de Telegraph\n";
}

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
            $telegraph = $telegraph->bot($bot);            // Verificar información del bot
            try {
                // Intentar usar botInfo() si existe
                if (method_exists($telegraph, 'botInfo')) {
                    $botInfoResponse = $telegraph->botInfo()->send();
                }
                // Si no existe, usar getMe() que es otra forma común
                else if (method_exists($telegraph, 'getMe')) {
                    $botInfoResponse = $telegraph->getMe()->send();
                }
                // Si ninguno existe, usar la API directa con getMe
                else {
                    echo "   ⚠️ No se encontró método para botInfo, usando API directa...\n";
                    $botInfoResponse = $telegraph->get('getMe')->send();
                }
            } catch (\Exception $botInfoException) {
                echo "   ⚠️ Error al obtener información del bot: " . $botInfoException->getMessage() . "\n";
                // Fallback a llamada API directa
                try {
                    $botInfoResponse = $telegraph->get('getMe')->send();
                } catch (\Exception $e) {
                    echo "   ❌ Error al usar API directa para getMe: " . $e->getMessage() . "\n";
                    $botInfoResponse = ['ok' => false, 'error' => $e->getMessage()];
                }
            }

            if (isset($botInfoResponse['ok']) && $botInfoResponse['ok'] === true) {
                $botInfo = $botInfoResponse['result'];
                echo "   ✓ Bot válido: {$botInfo['username']} ({$botInfo['id']})\n";

                // Construir la URL del webhook
                // La URL debe ser de la forma: https://tudominio.com/telegraph/{token}/webhook
                $webhookUrl = rtrim($baseUrl, '/') . '/telegraph/' . $bot->token . '/webhook';

                echo "   🔗 Configurando webhook URL: {$webhookUrl}\n";

                // Configurar el webhook (usando registerWebhook en lugar de setWebhook)
                try {
                    // Primero intentamos con registerWebhook (método recomendado en versiones más recientes)
                    if (method_exists($telegraph, 'registerWebhook')) {
                        $response = $telegraph->registerWebhook($webhookUrl)->send();
                    }
                    // Si no existe registerWebhook, intentamos con setWebhook (versiones anteriores)
                    else if (method_exists($telegraph, 'setWebhook')) {
                        $response = $telegraph->setWebhook($webhookUrl)->send();
                    }
                    // Si ninguno de los métodos existe, usamos la API directa
                    else {
                        echo "   ⚠️ No se encontró método para configurar webhook, usando API directa...\n";
                        $response = $telegraph->post('setWebhook', [
                            'url' => $webhookUrl
                        ])->send();
                    }
                } catch (\Exception $methodException) {
                    echo "   ⚠️ Error con método específico, intentando con API directa: " . $methodException->getMessage() . "\n";
                    // Fallback a llamada API directa
                    try {
                        $response = $telegraph->post('setWebhook', [
                            'url' => $webhookUrl
                        ])->send();
                    } catch (\Exception $e) {
                        echo "   ❌ Error al usar API directa: " . $e->getMessage() . "\n";
                        $response = ['ok' => false, 'error' => $e->getMessage()];
                    }
                }

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
                try {
                    // Primero intentamos con getWebhookInfo si existe
                    if (method_exists($telegraph, 'getWebhookInfo')) {
                        $webhookInfoResponse = $telegraph->getWebhookInfo()->send();
                    }
                    // Si no existe, usamos la API directa
                    else {
                        echo "   ⚠️ No se encontró método getWebhookInfo, usando API directa...\n";
                        $webhookInfoResponse = $telegraph->get('getWebhookInfo')->send();
                    }
                } catch (\Exception $webhookException) {
                    echo "   ⚠️ Error al obtener información del webhook: " . $webhookException->getMessage() . "\n";
                    // Fallback a llamada API directa
                    try {
                        $webhookInfoResponse = $telegraph->get('getWebhookInfo')->send();
                    } catch (\Exception $e) {
                        echo "   ❌ Error al usar API directa para getWebhookInfo: " . $e->getMessage() . "\n";
                        $webhookInfoResponse = ['ok' => false, 'error' => $e->getMessage()];
                    }
                }

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
