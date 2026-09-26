<?php
// Script para verificar y solucionar problemas comunes del webhook de Telegram
// Ejecutar: php verificar-y-solucionar-webhook-telegram.php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Iniciando verificación y solución de problemas del webhook de Telegram...\n\n";

// 1. Verificar que las rutas estén correctamente configuradas
echo "1️⃣ Verificando rutas de webhook...\n";

$routeCollection = \Illuminate\Support\Facades\Route::getRoutes();
$telegramRoutes = [];
$telegraphRoutes = [];

foreach ($routeCollection as $route) {
    if (strpos($route->uri(), 'telegram/webhook') !== false) {
        $telegramRoutes[] = [
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
            'controller' => isset($route->getAction()['controller']) ? $route->getAction()['controller'] : 'No controller'
        ];
    }

    if (strpos($route->getActionName(), 'Telegraph') !== false) {
        $telegraphRoutes[] = [
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName()
        ];
    }
}

if (count($telegramRoutes) > 1) {
    echo "⚠️ Se encontraron múltiples rutas para el webhook de Telegram:\n";
    foreach ($telegramRoutes as $index => $route) {
        echo "   {$index}. URI: {$route['uri']}, Acción: {$route['action']}\n";
    }
    echo "   ❗ Problema detectado: Las rutas duplicadas pueden causar conflictos en la inyección de dependencias\n";
    echo "   ✅ Solución: Edite routes/web.php y comente la ruta manual, dejando solo Route::telegraph()\n\n";
} else if (count($telegramRoutes) == 1) {
    echo "✅ Solo hay una ruta configurada para el webhook de Telegram:\n";
    echo "   URI: {$telegramRoutes[0]['uri']}, Acción: {$telegramRoutes[0]['action']}\n\n";
} else {
    echo "❌ No se encontraron rutas para el webhook de Telegram\n";
    echo "   ❗ Problema detectado: El webhook no está configurado correctamente\n";
    echo "   ✅ Solución: Asegúrese de que Route::telegraph() está presente en routes/web.php\n\n";
}

// 2. Verificar la configuración de Telegraph
echo "2️⃣ Verificando configuración de Telegraph...\n";

$webhookUrl = config('telegraph.webhook.url');
$webhookHandler = config('telegraph.webhook.handler');
$botModel = config('telegraph.models.bot');
$chatModel = config('telegraph.models.chat');

echo "   URL del webhook: {$webhookUrl}\n";
echo "   Manejador: {$webhookHandler}\n";
echo "   Modelo de bot: {$botModel}\n";
echo "   Modelo de chat: {$chatModel}\n\n";

if ($webhookHandler !== 'App\\Http\\Controllers\\TelegramWebhookController') {
    echo "⚠️ El manejador configurado no coincide con TelegramWebhookController\n";
    echo "   ❗ Problema detectado: El webhook podría no estar usando el controlador correcto\n";
    echo "   ✅ Solución: Verifique config/telegraph.php y asegúrese de que 'handler' es App\\Http\\Controllers\\TelegramWebhookController\n\n";
}

// 3. Verificar que el controlador existe y extiende de WebhookHandler
echo "3️⃣ Verificando controlador del webhook...\n";

if (!class_exists('App\\Http\\Controllers\\TelegramWebhookController')) {
    echo "❌ La clase TelegramWebhookController no existe\n";
    echo "   ❗ Problema crítico: El controlador definido no existe en el sistema\n";
} else {
    echo "✅ La clase TelegramWebhookController existe\n";

    $reflection = new ReflectionClass('App\\Http\\Controllers\\TelegramWebhookController');
    $parentClass = $reflection->getParentClass();

    if ($parentClass && $parentClass->getName() === 'DefStudio\\Telegraph\\Handlers\\WebhookHandler') {
        echo "✅ TelegramWebhookController extiende correctamente de WebhookHandler\n";

        // Verificar que tenga el método handle con la firma correcta
        $handleMethod = $reflection->getMethod('handle');
        $parameters = $handleMethod->getParameters();

        if (count($parameters) === 2 && $parameters[1]->getName() === 'bot') {
            echo "✅ El método handle tiene los parámetros correctos: (Request \$request, TelegraphBot \$bot)\n";
        } else {
            echo "❌ El método handle no tiene los parámetros correctos\n";
            echo "   ❗ Problema detectado: La firma del método handle no es compatible\n";
            echo "   ✅ Solución: Asegúrese de que handle reciba (Request \$request, TelegraphBot \$bot)\n";
        }
    } else {
        echo "❌ TelegramWebhookController no extiende de WebhookHandler\n";
        echo "   ❗ Problema crítico: El controlador debe extender DefStudio\\Telegraph\\Handlers\\WebhookHandler\n";
    }
}

echo "\n";

// 4. Verificar bots en la base de datos
echo "4️⃣ Verificando bots en la base de datos...\n";

try {
    $bots = \DefStudio\Telegraph\Models\TelegraphBot::all();

    if ($bots->isEmpty()) {
        echo "❌ No hay bots registrados en la base de datos\n";
        echo "   ❗ Problema crítico: Se necesita al menos un bot para que el webhook funcione\n";
        echo "   ✅ Solución: Registre un bot en la tabla telegraph_bots o ejecute las migraciones\n\n";
    } else {
        echo "✅ Se encontraron {$bots->count()} bots registrados\n";

        foreach ($bots as $bot) {
            echo "   🤖 Bot ID: {$bot->id}, Nombre: {$bot->name}\n";
            echo "      Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n";
            echo "      Webhook URL: " . ($bot->webhook_url ?: "No configurado") . "\n\n";

            // Verificar token
            if (!preg_match('/^[0-9]{8,10}:[a-zA-Z0-9_-]{35,}$/', $bot->token)) {
                echo "   ⚠️ El formato del token del bot parece incorrecto\n";
                echo "      ❗ Problema detectado: El token debe tener el formato 12345678:ABCDefgh...\n";
                echo "      ✅ Solución: Verifique que el token del bot sea correcto\n\n";
            }
              // Verificar webhook
            if (!$bot->webhook_url) {
                echo "   ⚠️ El bot no tiene configurado un webhook URL\n";
                echo "      ❗ Problema detectado: El bot no podrá recibir actualizaciones sin webhook\n";
                echo "      ✅ Solución: Configure el webhook usando php artisan telegraph:set-webhook o php configurar-telegram-webhook.php\n";

                // Preguntar si desea configurar automáticamente
                echo "\n      ¿Desea configurar el webhook automáticamente ahora? (s/n): ";
                $handle = fopen("php://stdin", "r");
                $line = trim(fgets($handle));
                fclose($handle);

                if (strtolower($line) === 's' || strtolower($line) === 'si' || strtolower($line) === 'y' || strtolower($line) === 'yes') {
                    echo "      ✓ Configurando webhook automáticamente...\n";

                    try {
                        $baseUrl = config('app.url');
                        if (!$baseUrl) {
                            echo "      ❌ Error: No se puede obtener la URL base de la aplicación desde config('app.url').\n";
                            echo "         Configure APP_URL en el archivo .env y ejecute php configurar-telegram-webhook.php\n";
                        } else {
                            $webhookUrl = rtrim($baseUrl, '/') . '/telegraph/' . $bot->token . '/webhook';
                            $telegraph = app(\DefStudio\Telegraph\Telegraph::class)->bot($bot);
                            $response = $telegraph->setWebhook($webhookUrl)->send();

                            if (isset($response['ok']) && $response['ok'] === true) {
                                echo "      ✅ Webhook configurado con éxito: {$webhookUrl}\n";
                                $bot->webhook_url = $webhookUrl;
                                $bot->save();
                            } else {
                                echo "      ❌ Error al configurar webhook: " . json_encode($response) . "\n";
                            }
                        }
                    } catch (\Exception $e) {
                        echo "      ❌ Error: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "      ℹ️ Configuración manual requerida. Ejecute php configurar-telegram-webhook.php\n";
                }

                echo "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Error al verificar bots: {$e->getMessage()}\n";
    echo "   ❗ Problema detectado: No se puede acceder a la tabla telegraph_bots\n";
    echo "   ✅ Solución: Verifique que las migraciones se han ejecutado: php artisan migrate\n\n";
}

// 5. Verificar código del controlador para patrones incorrectos
echo "5️⃣ Verificando patrones de código en el controlador...\n";

try {
    $controllerPath = app_path('Http/Controllers/TelegramWebhookController.php');

    if (file_exists($controllerPath)) {
        $controllerContent = file_get_contents($controllerPath);

        // Verificar patrón incorrecto de configuración de bot
        if (preg_match('/\$telegraph->bot\(\$this->bot\);(?!\s*\$telegraph\s*=)/', $controllerContent)) {
            echo "❌ Se encontró un patrón incorrecto para configurar el bot\n";
            echo "   ❗ Problema detectado: No se guarda la instancia devuelta por bot()\n";
            echo "   ✅ Solución: Cambiar a \$telegraph = \$telegraph->bot(\$this->bot);\n\n";
        } else {
            echo "✅ No se encontraron patrones incorrectos de configuración del bot\n";
        }

        // Verificar uso incorrecto de $this->chat->html()
        if (preg_match('/\$this->chat->html\(/', $controllerContent)) {
            echo "❌ Se encontró uso de \$this->chat->html() que puede causar problemas\n";
            echo "   ❗ Problema detectado: Este patrón no configura correctamente el bot\n";
            echo "   ✅ Solución: Reemplazar por \$telegraph->chat(\$this->chat->chat_id)->html()\n\n";
        } else {
            echo "✅ No se encontró uso incorrecto de \$this->chat->html()\n";
        }
    } else {
        echo "❌ No se pudo encontrar el archivo del controlador\n";
    }
} catch (\Exception $e) {
    echo "❌ Error al verificar el código del controlador: {$e->getMessage()}\n";
}

// 6. Verificar configuración del webhook en la API de Telegram
echo "6️⃣ Verificando configuración del webhook en la API de Telegram...\n";

try {
    $bot = \DefStudio\Telegraph\Models\TelegraphBot::first();

    if ($bot) {
        $telegraph = app(\DefStudio\Telegraph\Telegraph::class)->bot($bot);
        $response = $telegraph->getWebhookInfo()->send();

        if (isset($response['ok']) && $response['ok'] === true && isset($response['result'])) {
            $webhookInfo = $response['result'];
            $url = $webhookInfo['url'] ?? 'No configurado';
            $pendingUpdates = $webhookInfo['pending_update_count'] ?? 0;
            $lastErrorDate = isset($webhookInfo['last_error_date']) ? date('Y-m-d H:i:s', $webhookInfo['last_error_date']) : 'No hay errores';
            $lastErrorMessage = $webhookInfo['last_error_message'] ?? 'No hay errores';

            echo "✅ Información del webhook obtenida correctamente\n";
            echo "   URL: {$url}\n";
            echo "   Actualizaciones pendientes: {$pendingUpdates}\n";
            echo "   Último error: {$lastErrorDate}\n";
            echo "   Mensaje de error: {$lastErrorMessage}\n\n";

            if ($url !== config('app.url') . '/telegram/webhook' && $url !== config('app.url') . '/telegraph/' . $bot->token . '/webhook') {
                echo "⚠️ La URL del webhook no coincide con la URL esperada\n";
                echo "   ❗ Problema detectado: La URL del webhook configurada en Telegram es incorrecta\n";
                echo "   ✅ Solución: Ejecute php artisan telegraph:set-webhook para configurarla correctamente\n\n";
            }
        } else {
            echo "❌ No se pudo obtener información del webhook: " . json_encode($response) . "\n";
        }
    } else {
        echo "❌ No hay bots disponibles para verificar\n";
    }
} catch (\Exception $e) {
    echo "❌ Error al verificar la configuración del webhook: {$e->getMessage()}\n";
}

echo "\n🏁 Verificación completa.\n";

echo "\n📋 RESUMEN DE SOLUCIONES RECOMENDADAS:\n";
echo "1. Asegúrese de que solo hay una ruta para el webhook de Telegram\n";
echo "   - Comente la ruta manual en routes/web.php\n";
echo "   - Mantenga solo Route::telegraph()\n";
echo "2. Verifique que hay al menos un bot en la tabla telegraph_bots\n";
echo "   - Si no, ejecute: php artisan telegraph:new-bot\n";
echo "3. Configure correctamente el webhook\n";
echo "   - Ejecute: php artisan telegraph:set-webhook\n";
echo "4. Si hay problemas con el código del controlador:\n";
echo "   - Asegúrese de guardar la instancia devuelta por bot(): \$telegraph = \$telegraph->bot(\$this->bot);\n";
echo "   - Nunca use \$this->chat->html() directamente\n";

echo "\nConsidere ejecutar este script tanto en desarrollo como en producción para verificar\n";
echo "que la configuración sea consistente en ambos entornos.\n";
