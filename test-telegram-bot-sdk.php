<?php
/**
 * Script para probar el envío de mensajes con Telegram Bot SDK
 *
 * Este script permite enviar un mensaje de prueba a un chat específico.
 *
 * Uso: php test-telegram-bot-sdk.php <chat_id> <mensaje>
 */

// Cargar el entorno de Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Telegram\Bot\Api;

// Imprimir encabezado
echo "\n";
echo "===================================================\n";
echo "         PRUEBA DE MENSAJE CON TELEGRAM BOT SDK     \n";
echo "===================================================\n\n";

// Verificar argumentos
if ($argc < 3) {
    echo "❌ Error: Faltan argumentos.\n";
    echo "   Uso: php test-telegram-bot-sdk.php <chat_id> <mensaje>\n\n";
    echo "   Ejemplo: php test-telegram-bot-sdk.php 123456789 \"Mensaje de prueba\"\n\n";
    exit(1);
}

$chatId = $argv[1];
$mensaje = $argv[2];

try {
    // Obtener el token del archivo .env
    $token = env('TELEGRAM_BOT_TOKEN', '');

    if (empty($token)) {
        echo "❌ ERROR: No se encontró el token del bot en el archivo .env.\n";
        echo "   Por favor, configure la variable TELEGRAM_BOT_TOKEN en el archivo .env.\n\n";
        exit(1);
    }

    echo "ℹ️ Usando token: " . substr($token, 0, 5) . "..." . substr($token, -5) . "\n";
    echo "ℹ️ Chat ID destino: $chatId\n";
    echo "ℹ️ Mensaje a enviar: \"$mensaje\"\n\n";

    // Inicializar la API de Telegram
    $telegram = new Api($token);

    echo "ℹ️ Enviando mensaje...\n";

    // Cronometrar el tiempo de envío
    $start = microtime(true);

    // Enviar mensaje
    $response = $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'HTML'
    ]);

    $end = microtime(true);
    $time = round(($end - $start) * 1000, 2);

    echo "✅ Mensaje enviado correctamente en $time ms!\n";
    echo "ℹ️ Detalles del mensaje:\n";
    echo "   - Message ID: " . $response->getMessageId() . "\n";

    if ($response->getChat()) {
        echo "   - Chat: " . $response->getChat()->getTitle() . " (ID: " . $response->getChat()->getId() . ")\n";
    }

    echo "   - Fecha: " . date('Y-m-d H:i:s', $response->getDate()) . "\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
}

echo "\n===================================================\n";
