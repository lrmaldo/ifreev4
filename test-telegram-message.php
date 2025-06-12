<?php

// Script de prueba para enviar un mensaje directo a Telegram
// Este script prueba la comunicación directa con la API de Telegram
// Ejecutar: php test-telegram-message.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "✉️ Prueba de envío de mensajes a Telegram...\n\n";

// Obtener el bot
$bot = \DefStudio\Telegraph\Models\TelegraphBot::first();

if (!$bot) {
    echo "❌ Error: No se encontró ningún bot configurado en la base de datos.\n";
    exit(1);
}

echo "ℹ️ Bot encontrado: {$bot->name}\n";
echo "ℹ️ Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n\n";

// Verificar conexión con Telegram
echo "🔄 Verificando conexión con la API de Telegram...\n";
try {
    // Usar el cliente Telegraph para obtener la información del bot
    $telegraph = app(\DefStudio\Telegraph\Telegraph::class);

    // Registramos el bot en el contenedor de Laravel para uso posterior
    app()->instance('telegraph.bot', $bot);

    // Configuramos el bot de manera explícita y devolvemos la misma instancia
    $telegraph = $telegraph->bot($bot);
    $response = $telegraph->botInfo()->send();

    if (isset($response['ok']) && $response['ok'] === true && isset($response['result'])) {
        $botInfo = $response['result'];
        echo "✅ Conexión exitosa! Bot ID: {$botInfo['id']}, Username: @{$botInfo['username']}\n\n";
    } else {
        echo "❌ Error: No se pudo obtener información del bot\n";
        echo "Respuesta: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Obtener chats disponibles - intentamos tanto con TelegraphChat como con la tabla TelegramChat
try {
    $telegraphChats = \DefStudio\Telegraph\Models\TelegraphChat::all();
    $telegramChats = \App\Models\TelegramChat::all();

    $chatsCount = $telegraphChats->count() + $telegramChats->count();

    if ($chatsCount === 0) {
        echo "❌ No hay chats registrados para enviar mensajes.\n";

        // Pedir un chat_id para enviar mensaje de prueba
        echo "\n📝 Ingresa un chat_id para enviar un mensaje de prueba: ";
        $chatId = trim(fgets(STDIN));

        if (empty($chatId)) {
            echo "❌ No se ingresó un chat_id válido.\n";
            exit(1);
        }

        echo "  → Se usará el chat ID: {$chatId} para la prueba.\n\n";
        $chat = null; // No hay objeto chat, pero tenemos el chatId
    } else {
        echo "✅ Se encontraron {$chatsCount} chats registrados.\n";

        // Mostrar los chats disponibles de TelegraphChat
        $index = 1;
        if ($telegraphChats->count() > 0) {
            echo "\n  📋 Chats de TelegraphChat:\n";
            foreach ($telegraphChats as $chat) {
                echo "  {$index}. Chat ID: {$chat->chat_id}, Nombre: " .
                     ($chat->name ?? 'Sin nombre') . "\n";
                $index++;
            }
        }

        // Mostrar los chats disponibles de TelegramChat
        if ($telegramChats->count() > 0) {
            echo "\n  📋 Chats de TelegramChat:\n";
            foreach ($telegramChats as $chat) {
                echo "  {$index}. Chat ID: {$chat->chat_id}, Nombre: {$chat->nombre}\n";
                $index++;
            }
        }

        // Seleccionamos un chat para la prueba (preferimos TelegramChat)
        if ($telegramChats->count() > 0) {
            $chat = $telegramChats->first();
            echo "\n🔹 Usando el chat #{$chat->id} ({$chat->nombre}) para la prueba.\n\n";
            $chatId = $chat->chat_id;
        } else {
            $chat = $telegraphChats->first();
            echo "\n🔹 Usando el chat #{$chat->id} (" . ($chat->name ?? 'Sin nombre') . ") para la prueba.\n\n";
            $chatId = $chat->chat_id;
        }
    }
} catch (\Exception $e) {
    echo "❌ Error al obtener chats: " . $e->getMessage() . "\n";

    // Pedir un chat_id manualmente como fallback
    echo "\n📝 Ingresa un chat_id para enviar mensaje de prueba: ";
    $chatId = trim(fgets(STDIN));

    if (empty($chatId)) {
        echo "❌ No se ingresó un chat_id válido.\n";
        exit(1);
    }

    echo "  → Se usará el chat ID: {$chatId} para la prueba.\n\n";
    $chat = null; // No hay objeto chat, pero tenemos el chatId
}

// Enviar mensaje
echo "🚀 Enviando mensaje de prueba...\n";

try {
    // Usar método 1: Con la instancia de Telegraph
    $telegraph = app(\DefStudio\Telegraph\Telegraph::class);

    // Almacenamos el bot en el contenedor de servicios para que Telegraph lo use
    app()->instance('telegraph.bot', $bot);

    // Ahora configuramos el bot de manera explícita
    $telegraph = $telegraph->bot($bot);

    $chatId = isset($chat->chat_id) ? $chat->chat_id : $chatId;
    echo "  → Enviando mensaje al chat ID: {$chatId}\n";

    // Configurar el chat y enviar el mensaje
    $response = $telegraph->chat($chatId)
        ->message('🧪 Este es un mensaje de prueba enviado a las ' . date('H:i:s'))
        ->send();

    echo "✅ Mensaje enviado correctamente (Método 1)\n";
    echo "📊 Respuesta: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
} catch (\Exception $e) {
    echo "❌ Error al enviar mensaje (Método 1): " . $e->getMessage() . "\n";
    echo "🔍 Clase de error: " . get_class($e) . "\n";
    echo "📝 Traza:\n" . $e->getTraceAsString() . "\n";

    // Diagnóstico adicional para resolver problemas con Telegraph
    echo "\n🔎 Diagnóstico de estados:\n";
    echo "  - ¿Bot configurado?: " . (app()->bound('telegraph.bot') ? "✅ SÍ" : "❌ NO") . "\n";
    echo "  - Clase de bot: " . get_class($bot) . "\n";

    // Intentar recuperar la configuración del bot para verificar
    try {
        $botConfig = config('telegraph.models.bot');
        echo "  - Clase de bot configurada: {$botConfig}\n";
    } catch (\Exception $configException) {
        echo "  - No se pudo obtener la configuración del bot: " . $configException->getMessage() . "\n";
    }
    echo "\n";
}

try {
    // Usar método 2: Directamente con la API de Telegram
    $telegramToken = $bot->token;
    $telegramApiUrl = 'https://api.telegram.org/bot' . $telegramToken . '/sendMessage';

    $chatIdToUse = isset($chatId) ? $chatId : (isset($chat->chat_id) ? $chat->chat_id : null);

    if (!$chatIdToUse) {
        echo "❌ No se pudo determinar un chat_id para enviar el mensaje.\n";
        exit(1);
    }

    $payload = [
        'chat_id' => $chatIdToUse,
        'text' => '📲 Mensaje directo a la API a las ' . date('H:i:s'),
        'parse_mode' => 'HTML',
    ];

    echo "  → Enviando mensaje al chat ID: {$chatIdToUse} (API Directa)\n";

    $ch = curl_init($telegramApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // Añadir opciones para diagnóstico
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para pruebas, no usar en producción

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        echo "❌ Error CURL: $error\n";
    } else if ($statusCode != 200) {
        echo "❌ Error API (Código $statusCode): $response\n";
    } else {
        echo "✅ Mensaje enviado correctamente (Método 2)\n";
        echo "📊 Respuesta: $response\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Error al enviar mensaje (Método 2): " . $e->getMessage() . "\n";
    echo "🔍 Traza: " . $e->getTraceAsString() . "\n";
}

echo "\n👉 Si no recibes los mensajes, verifica:\n";
echo "  1. Que el token del bot sea correcto\n";
echo "  2. Que el chat_id sea válido y corresponda a una conversación con el bot\n";
echo "  3. Que no haya restricciones de firewall o proxy\n";
echo "  4. Que el bot tenga permisos para enviar mensajes\n";
echo "  5. Que el usuario no haya bloqueado al bot\n";

echo "\n🏁 Prueba completada.\n";
