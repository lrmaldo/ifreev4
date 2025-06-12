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
    $botInfo = $bot->getMe();
    echo "✅ Conexión exitosa! Bot ID: {$botInfo->id}, Username: @{$botInfo->username}\n\n";
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// Obtener chats disponibles
$chats = \DefStudio\Telegraph\Models\TelegraphChat::all();

if ($chats->isEmpty()) {
    echo "❌ No hay chats registrados para enviar mensajes.\n";

    // Pedir un chat_id para enviar mensaje de prueba
    echo "\n📝 Ingresa un chat_id para enviar un mensaje de prueba: ";
    $chatId = trim(fgets(STDIN));

    if (empty($chatId)) {
        echo "❌ No se ingresó un chat_id válido.\n";
        exit(1);
    }

    // Crear un chat temporal solo para la prueba
    $chat = new \DefStudio\Telegraph\DTO\Chat();
    $chat->id = $chatId;
} else {
    echo "✅ Se encontraron " . $chats->count() . " chats registrados.\n";

    // Mostrar los chats disponibles
    $index = 1;
    foreach ($chats as $chat) {
        echo "  {$index}. Chat ID: {$chat->chat_id}, Nombre: {$chat->nombre}\n";
        $index++;
    }

    // Seleccionar el primer chat para la prueba
    $chat = $chats->first();
    echo "\n🔹 Usando el chat #{$chat->id} ({$chat->nombre}) para la prueba.\n\n";
}

// Enviar mensaje
echo "🚀 Enviando mensaje de prueba...\n";

try {
    // Usar método 1: Con la instancia de Telegraph
    $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
    $telegraph->chat($chat->chat_id);
    $response = $telegraph->message('🧪 Este es un mensaje de prueba enviado a las ' . date('H:i:s'))
        ->send();

    echo "✅ Mensaje enviado correctamente (Método 1)\n";
    echo "📊 Respuesta: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
} catch (\Exception $e) {
    echo "❌ Error al enviar mensaje (Método 1): " . $e->getMessage() . "\n";
    echo "🔍 Clase de error: " . get_class($e) . "\n";
    echo "📝 Traza:\n" . $e->getTraceAsString() . "\n\n";
}

try {
    // Usar método 2: Directamente con la API de Telegram
    $telegramToken = $bot->token;
    $telegramApiUrl = 'https://api.telegram.org/bot' . $telegramToken . '/sendMessage';

    $payload = [
        'chat_id' => $chat->chat_id,
        'text' => '📲 Mensaje directo a la API a las ' . date('H:i:s'),
        'parse_mode' => 'HTML',
    ];

    $ch = curl_init($telegramApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

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
}

echo "\n👉 Si no recibes los mensajes, verifica:\n";
echo "  1. Que el token del bot sea correcto\n";
echo "  2. Que el chat_id sea válido y corresponda a una conversación con el bot\n";
echo "  3. Que no haya restricciones de firewall o proxy\n";
echo "  4. Que el bot tenga permisos para enviar mensajes\n";
echo "  5. Que el usuario no haya bloqueado al bot\n";

echo "\n🏁 Prueba completada.\n";
