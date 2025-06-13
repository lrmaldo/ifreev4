<?php
/**
 * Script para configurar el webhook de Telegram
 *
 * Este script utiliza la biblioteca irazasyed/telegram-bot-sdk para
 * configurar el webhook de Telegram en el servidor.
 *
 * Uso: php configurar-telegram-webhook-nuevo.php
 */

// Cargar el entorno de Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Telegram\Bot\Api;

// Imprimir encabezado
echo "\n";
echo "===================================================\n";
echo "    CONFIGURACIÓN DE WEBHOOK DE TELEGRAM BOT SDK    \n";
echo "===================================================\n\n";

try {
    // Obtener el token del archivo .env o como parámetro
    $token = env('TELEGRAM_BOT_TOKEN', '');

    if (empty($token)) {
        echo "❌ ERROR: No se encontró el token del bot en el archivo .env.\n";
        echo "   Por favor, configure la variable TELEGRAM_BOT_TOKEN en el archivo .env.\n\n";
        exit(1);
    }

    echo "ℹ️ Usando token: " . substr($token, 0, 5) . "..." . substr($token, -5) . "\n\n";

    // Inicializar la API de Telegram
    $telegram = new Api($token);

    // Configurar Guzzle para ignorar verificación SSL (solo para desarrollo)
    $httpClient = new \GuzzleHttp\Client(['verify' => false]);
    $telegram->setHttpClientHandler(new \Telegram\Bot\HttpClients\GuzzleHttpClient($httpClient));

    // Obtener la información del bot
    $botInfo = $telegram->getMe();
    echo "✅ Bot encontrado: @" . $botInfo->getUsername() . " (ID: " . $botInfo->getId() . ")\n";
    echo "   Nombre: " . $botInfo->getFirstName() . "\n\n";

    // Obtener la URL del webhook configurada en el archivo de configuración
    $webhookUrl = config('telegram.bots.ifree.webhook_url');

    if (empty($webhookUrl) || $webhookUrl == 'https://v3.i-free.com.mx/telegram/webhook') {
        // URL predeterminada o configurada
        $webhookUrl = 'https://v3.i-free.com.mx/telegram/webhook';
        echo "ℹ️ Usando URL de webhook predeterminada: $webhookUrl\n";
    } else {
        echo "ℹ️ Usando URL de webhook configurada: $webhookUrl\n";
    }

    echo "\nℹ️ Verificando la configuración actual del webhook...\n";

    // Obtener la información del webhook actual
    $webhookInfo = $telegram->getWebhookInfo();

    echo "   URL actual: " . ($webhookInfo['url'] ?? 'No configurada') . "\n";

    if (isset($webhookInfo['pending_update_count'])) {
        echo "   Actualizaciones pendientes: " . $webhookInfo['pending_update_count'] . "\n";
    }

    if (isset($webhookInfo['last_error_message']) && !empty($webhookInfo['last_error_message'])) {
        echo "   ⚠️ Último error: " . $webhookInfo['last_error_message'] . "\n";
        if (isset($webhookInfo['last_error_date'])) {
            $errorDate = new DateTime('@' . $webhookInfo['last_error_date']);
            $errorDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
            echo "   Fecha del error: " . $errorDate->format('Y-m-d H:i:s') . "\n";
        }
    }

    echo "\n";

    // Configuración de actualizaciones permitidas
    $allowedUpdates = config('telegram.bots.ifree.allowed_updates', null);

    // Preguntar si se quiere continuar
    echo "ℹ️ Se configurará el webhook con la siguiente URL:\n";
    echo "   $webhookUrl\n\n";
    echo "   Actualizaciones permitidas: " . (is_array($allowedUpdates) ? implode(', ', $allowedUpdates) : 'todas') . "\n\n";

    echo "¿Desea continuar? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));

    if (strtolower($line) !== 's') {
        echo "\n⚠️ Operación cancelada por el usuario.\n\n";
        exit;
    }

    echo "\nConfigurando webhook...\n";

    // Configurar el webhook
    $response = $telegram->setWebhook([
        'url' => $webhookUrl,
        'allowed_updates' => $allowedUpdates,
        'drop_pending_updates' => true
    ]);

    if ($response) {
        echo "✅ Webhook configurado correctamente!\n\n";

        // Verificar de nuevo
        $webhookInfo = $telegram->getWebhookInfo();
        echo "ℹ️ Nueva configuración del webhook:\n";
        echo "   URL: " . ($webhookInfo['url'] ?? 'No configurada') . "\n";

        if (isset($webhookInfo['has_custom_certificate'])) {
            echo "   Certificado personalizado: " . ($webhookInfo['has_custom_certificate'] ? "Sí" : "No") . "\n";
        }

        if (isset($webhookInfo['allowed_updates']) && is_array($webhookInfo['allowed_updates'])) {
            echo "   Actualizaciones permitidas: " . implode(', ', $webhookInfo['allowed_updates']) . "\n";
        } else {
            echo "   Actualizaciones permitidas: todas\n";
        }
    } else {
        echo "❌ Error al configurar el webhook.\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
}

echo "\n===================================================\n";
