<?php

// Este script ayuda a diagnosticar problemas con las respuestas de Telegram
// Ejecutar: php diagnostico-telegram-respuestas.php

use Illuminate\Support\Facades\Log;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\Telegraph;

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Iniciando diagnóstico de respuestas de Telegram...\n\n";

// 1. Verificar configuración del bot
echo "1️⃣ Verificando configuración del bot...\n";
try {
    $bot = TelegraphBot::first();

    if (!$bot) {
        echo "   ❌ No se encontró ningún bot configurado en la base de datos.\n";
        exit(1);
    }

    echo "   ✅ Bot encontrado: {$bot->name}\n";
    echo "   👤 Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n";
    echo "   🆔 ID: {$bot->id}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error al buscar bot: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar configuración de chats
echo "2️⃣ Verificando configuración de chats...\n";
try {
    $chats = TelegraphChat::all();

    if ($chats->isEmpty()) {
        echo "   ⚠️ No se encontraron chats registrados en la base de datos.\n\n";
    } else {
        echo "   ✅ Chats encontrados: " . $chats->count() . "\n\n";
        $chats->each(function ($chat) {
            echo "      - Chat ID: {$chat->chat_id}, Nombre: {$chat->nombre}, Tipo: {$chat->tipo}\n";
        });
        echo "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error al buscar chats: " . $e->getMessage() . "\n";
}

// 3. Probar envío de mensaje
echo "3️⃣ Probando envío de mensaje directo...\n";

if ($chats->isEmpty()) {
    echo "   ⚠️ No hay chats donde probar el envío de mensajes.\n";
    echo "   👉 Primero ejecuta /start en el bot para registrar un chat.\n\n";
} else {
    $testChat = $chats->first();

    try {
        // Método 1: Usar la clase Telegraph directamente
        echo "   🔹 Método 1: Utilizando la clase Telegraph directamente...\n";
        $result1 = app(Telegraph::class)
            ->chat($testChat->chat_id)
            ->message("🧪 Prueba de diagnóstico (Método 1): " . date('Y-m-d H:i:s'))
            ->send();

        echo "   ✅ Mensaje enviado correctamente (Método 1)\n";
        echo "   📊 Respuesta: " . json_encode($result1) . "\n\n";
    } catch (\Exception $e) {
        echo "   ❌ Error al enviar mensaje (Método 1): " . $e->getMessage() . "\n";
        echo "   🔍 Detalles: " . get_class($e) . "\n";
        echo "   📝 Traza:\n" . $e->getTraceAsString() . "\n\n";
    }

    try {
        // Método 2: Usar el modelo TelegraphChat
        echo "   🔹 Método 2: Utilizando el modelo TelegraphChat...\n";
        $result2 = $testChat->message("🧪 Prueba de diagnóstico (Método 2): " . date('Y-m-d H:i:s'))
            ->send();

        echo "   ✅ Mensaje enviado correctamente (Método 2)\n";
        echo "   📊 Respuesta: " . json_encode($result2) . "\n\n";
    } catch (\Exception $e) {
        echo "   ❌ Error al enviar mensaje (Método 2): " . $e->getMessage() . "\n";
        echo "   🔍 Detalles: " . get_class($e) . "\n";
        echo "   📝 Traza:\n" . $e->getTraceAsString() . "\n\n";
    }
}

// 4. Verificar configuración del webhook
echo "4️⃣ Verificando configuración del webhook...\n";
try {
    $webhookInfo = $bot->getWebhookInfo();
    echo "   🔗 URL actual del webhook: {$webhookInfo->url}\n";
    echo "   📊 Solicitudes pendientes: {$webhookInfo->pending_update_count}\n";
    if ($webhookInfo->last_error_message) {
        echo "   ⚠️ Último error: {$webhookInfo->last_error_message} ({$webhookInfo->last_error_date})\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "   ❌ Error al obtener información del webhook: " . $e->getMessage() . "\n\n";
}

// 5. Verificar permisos de los métodos
echo "5️⃣ Verificando permisos y métodos del controlador...\n";
try {
    $controllerClass = config('telegraph.webhook.handler');
    $controller = app($controllerClass);

    $requiredMethods = [
        'handle' => 'public',
        'getChatName' => 'protected',
        'getChatType' => 'protected',
        'handleChatMessage' => 'public',
    ];

    $reflection = new ReflectionClass($controller);

    foreach ($requiredMethods as $method => $expectedVisibility) {
        if (!$reflection->hasMethod($method)) {
            echo "   ❌ Método {$method} no encontrado en el controlador\n";
            continue;
        }

        $reflectionMethod = $reflection->getMethod($method);
        $actualVisibility = $reflectionMethod->isPublic() ? 'public' : ($reflectionMethod->isProtected() ? 'protected' : 'private');

        if ($actualVisibility !== $expectedVisibility) {
            echo "   ⚠️ El método {$method} tiene visibilidad {$actualVisibility}, pero se esperaba {$expectedVisibility}\n";
        } else {
            echo "   ✅ Método {$method} tiene la visibilidad correcta ({$actualVisibility})\n";
        }

        // Verificar firma de métodos específicos
        if ($method === 'handle') {
            $params = $reflectionMethod->getParameters();
            if (count($params) < 2) {
                echo "   ❌ El método handle debería tener al menos 2 parámetros\n";
            } else {
                echo "   ✅ Firma del método handle correcta\n";
            }
        } else if ($method === 'handleChatMessage') {
            $params = $reflectionMethod->getParameters();
            if (count($params) < 1 || $params[0]->getType()->getName() !== 'Illuminate\Support\Stringable') {
                echo "   ❌ El método handleChatMessage debería recibir un parámetro de tipo Illuminate\Support\Stringable\n";
            } else {
                echo "   ✅ Firma del método handleChatMessage correcta\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error al verificar el controlador: " . $e->getMessage() . "\n";
}

echo "\n6️⃣ Sugerencias y próximos pasos:\n";
echo "   • Verificar permisos del archivo .env: telegram API debe tener acceso\n";
echo "   • Revisar logs de Laravel en storage/logs/laravel.log\n";
echo "   • Comprobar que la URL del webhook sea accesible externamente\n";
echo "   • Para restablecer webhook: php artisan telegraph:webhook refresh\n\n";

echo "🏁 Diagnóstico completado.\n";
