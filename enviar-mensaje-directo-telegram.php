<?php
// Script para enviar un mensaje directamente a través de la API de Telegram (sin usar Telegraph)
// Ejecutar: php enviar-mensaje-directo-telegram.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

echo "🚀 Test de envío de mensajes directo a Telegram API\n";
echo "================================================\n\n";

// Buscar todos los bots registrados
try {
    $bots = \DefStudio\Telegraph\Models\TelegraphBot::all();

    if ($bots->isEmpty()) {
        echo "❌ No se encontraron bots registrados en la base de datos\n";
        exit(1);
    }

    echo "✅ Se encontraron " . $bots->count() . " bots registrados\n\n";

    // Seleccionar el bot a usar para la prueba
    if ($bots->count() == 1) {
        $bot = $bots->first();
        echo "🤖 Usando el único bot disponible: {$bot->name} (ID: {$bot->id})\n";
    } else {
        echo "📋 Selecciona un bot para enviar el mensaje:\n";
        foreach ($bots as $index => $bot) {
            echo "  [{$index}] {$bot->name} (ID: {$bot->id})\n";
        }

        $selection = readline("Ingresa el número del bot a usar [0]: ");
        if ($selection === "") {
            $selection = 0;
        }

        if (!is_numeric($selection) || $selection < 0 || $selection >= $bots->count()) {
            echo "❌ Selección inválida\n";
            exit(1);
        }

        $bot = $bots[$selection];
        echo "🤖 Bot seleccionado: {$bot->name} (ID: {$bot->id})\n";
    }

    // Obtener los chats disponibles para este bot
    $chats = \DefStudio\Telegraph\Models\TelegraphChat::where('telegraph_bot_id', $bot->id)->get();

    if ($chats->isEmpty()) {
        echo "❌ No se encontraron chats registrados para este bot\n";
        $chatId = readline("Ingresa el ID del chat manualmente (ej: 123456789): ");

        if (empty($chatId)) {
            echo "❌ Chat ID no válido\n";
            exit(1);
        }
    } else {
        echo "📋 Chats disponibles para este bot:\n";
        foreach ($chats as $index => $chat) {
            echo "  [{$index}] {$chat->name} (ID: {$chat->chat_id})\n";
        }

        $selection = readline("Ingresa el número del chat a usar [0]: ");
        if ($selection === "") {
            $selection = 0;
        }

        if (!is_numeric($selection) || $selection < 0 || $selection >= $chats->count()) {
            echo "❌ Selección inválida\n";
            exit(1);
        }

        $chat = $chats[$selection];
        echo "💬 Chat seleccionado: {$chat->name} (ID: {$chat->chat_id})\n";
        $chatId = $chat->chat_id;
    }

    // Mensaje a enviar
    $mensaje = readline("Ingresa el mensaje a enviar [Mensaje de prueba desde la API directa]: ");
    if (empty($mensaje)) {
        $mensaje = "Mensaje de prueba desde la API directa de Telegram - " . date('Y-m-d H:i:s');
    }

    echo "\n📤 Enviando mensaje mediante API directa...\n";
    echo "   Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n";
    echo "   Chat ID: {$chatId}\n";
    echo "   Mensaje: {$mensaje}\n\n";

    // Construir la URL de la API
    $telegramApiUrl = config('telegraph.telegram_api_url', 'https://api.telegram.org/');
    $apiUrl = rtrim($telegramApiUrl, '/') . '/bot' . $bot->token . '/sendMessage';

    // Preparar los datos para la solicitud
    $data = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'HTML'
    ];

    // Hacer la solicitud mediante curl
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    // Medición de tiempo
    $start = microtime(true);
    $response = curl_exec($ch);
    $elapsed = microtime(true) - $start;

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "⏱️ Tiempo de respuesta: " . round($elapsed * 1000, 2) . " ms\n";
    echo "📥 Código HTTP: {$httpCode}\n";

    if ($httpCode === 200) {
        $result = json_decode($response, true);

        if (isset($result['ok']) && $result['ok'] === true) {
            echo "✅ Mensaje enviado correctamente\n";
            echo "📊 Detalles del mensaje:\n";
            echo "   ID del mensaje: " . ($result['result']['message_id'] ?? 'No disponible') . "\n";
            echo "   Fecha de envío: " . date('Y-m-d H:i:s', ($result['result']['date'] ?? time())) . "\n";

            // Guardar registro del envío exitoso
            Log::info('Mensaje enviado exitosamente mediante API directa de Telegram', [
                'chat_id' => $chatId,
                'message_id' => $result['result']['message_id'] ?? null,
                'response_time_ms' => round($elapsed * 1000, 2),
                'timestamp' => now()->toIso8601String()
            ]);
        } else {
            echo "❌ Error al enviar el mensaje: " . json_encode($result) . "\n";

            // Guardar registro del error
            Log::error('Error al enviar mensaje mediante API directa de Telegram', [
                'chat_id' => $chatId,
                'error' => $result,
                'response_time_ms' => round($elapsed * 1000, 2)
            ]);
        }
    } else {
        echo "❌ Error HTTP {$httpCode} al enviar el mensaje\n";
        echo "   Respuesta: {$response}\n";

        // Guardar registro del error
        Log::error('Error HTTP al enviar mensaje mediante API directa de Telegram', [
            'chat_id' => $chatId,
            'http_code' => $httpCode,
            'response' => $response,
            'response_time_ms' => round($elapsed * 1000, 2)
        ]);
    }

    echo "\n🔄 Ahora vamos a intentar enviar el mismo mensaje usando Telegraph\n";

    try {
        // Obtener el objeto Telegraph
        $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
        $telegraph = $telegraph->bot($bot);

        echo "📤 Enviando mensaje mediante Telegraph...\n";

        // Medición de tiempo
        $start = microtime(true);
        $response = $telegraph->chat($chatId)
            ->html($mensaje)
            ->send();
        $elapsed = microtime(true) - $start;

        echo "⏱️ Tiempo de respuesta: " . round($elapsed * 1000, 2) . " ms\n";

        // Verificar respuesta
        if (is_array($response) && isset($response['ok']) && $response['ok'] === true) {
            echo "✅ Mensaje enviado correctamente mediante Telegraph\n";
        } else {
            echo "❌ Error al enviar el mensaje mediante Telegraph\n";
            echo "   Respuesta: " . json_encode($response) . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Excepción al enviar mensaje mediante Telegraph: " . $e->getMessage() . "\n";

        // Guardar registro del error
        Log::error('Excepción al enviar mensaje mediante Telegraph', [
            'chat_id' => $chatId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

} catch (\Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
    echo "   " . $e->getTraceAsString() . "\n";

    // Guardar registro del error
    Log::error('Error general en script de envío directo a Telegram', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
