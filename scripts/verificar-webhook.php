<?php
/**
 * Script para verificar la configuración del webhook en Telegram
 *
 * Este script comprueba la configuración actual del webhook en Telegram
 * y verifica que esté correctamente establecida.
 *
 * Uso: php verificar-webhook.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Support\Facades\Http;

// Encabezado
echo "\n";
echo "===========================================================\n";
echo "       VERIFICADOR DE CONFIGURACIÓN DE WEBHOOK TELEGRAM     \n";
echo "===========================================================\n\n";

// Obtener todos los bots
$bots = TelegraphBot::all();

if ($bots->isEmpty()) {
    echo "❌ No se encontraron bots registrados en la base de datos.\n";
    exit(1);
}

echo "Se encontraron " . $bots->count() . " bots registrados:\n\n";

foreach ($bots as $index => $bot) {
    echo "📱 BOT #" . ($index + 1) . ": " . $bot->name . " (ID: " . $bot->id . ")\n";
    echo "   Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n";

    // Verificar URL del webhook configurada en la base de datos
    echo "   Webhook URL en DB: " . ($bot->webhook_url ?? "No configurada") . "\n";

    // Verificar configuración real en Telegram
    $baseUrl = config('telegraph.telegram_api_url', 'https://api.telegram.org/');
    $url = rtrim($baseUrl, '/') . '/bot' . $bot->token . '/getWebhookInfo';

    try {
        $response = Http::get($url);
        $data = $response->json();

        if ($response->successful() && isset($data['ok']) && $data['ok'] === true) {
            echo "\n   ✅ Configuración actual en Telegram:\n";
            echo "   ----------------------------------------\n";

            if (isset($data['result']['url']) && !empty($data['result']['url'])) {
                echo "   URL: " . $data['result']['url'] . "\n";
            } else {
                echo "   URL: No configurada\n";
            }

            if (isset($data['result']['has_custom_certificate'])) {
                echo "   Certificado personalizado: " . ($data['result']['has_custom_certificate'] ? "Sí" : "No") . "\n";
            }

            if (isset($data['result']['pending_update_count'])) {
                echo "   Actualizaciones pendientes: " . $data['result']['pending_update_count'] . "\n";
            }

            if (isset($data['result']['max_connections'])) {
                echo "   Conexiones máximas: " . $data['result']['max_connections'] . "\n";
            }

            if (isset($data['result']['last_error_date']) && $data['result']['last_error_date'] > 0) {
                $errorDate = new DateTime('@' . $data['result']['last_error_date']);
                $errorDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
                echo "   Último error: " . $errorDate->format('Y-m-d H:i:s') . "\n";

                if (isset($data['result']['last_error_message'])) {
                    echo "   Mensaje de error: " . $data['result']['last_error_message'] . "\n";
                }
            } else {
                echo "   Sin errores recientes\n";
            }

            if (isset($data['result']['allowed_updates']) && is_array($data['result']['allowed_updates'])) {
                echo "   Tipos de actualizaciones permitidas: " . implode(', ', $data['result']['allowed_updates']) . "\n";
            }

            // Analizar si hay problemas con la configuración
            $appUrl = config('app.url');
            $expectedWebhookUrl = rtrim($appUrl, '/') . '/telegraph/' . $bot->token . '/webhook';
            $actualWebhookUrl = $data['result']['url'] ?? '';

            // Verificar si coincide con la estructura esperada
            if (empty($actualWebhookUrl)) {
                echo "\n   ❌ PROBLEMA: No hay webhook configurado en Telegram.\n";

                echo "\n   💡 SOLUCIÓN RECOMENDADA:\n";
                echo "   Ejecuta el siguiente comando para configurar el webhook:\n\n";
                echo "   php artisan telegraph:set-webhook " . $bot->name . "\n";
            }
            elseif ($actualWebhookUrl != $expectedWebhookUrl) {
                echo "\n   ⚠️ ADVERTENCIA: La URL del webhook configurada en Telegram no coincide con la esperada.\n";
                echo "   URL actual:   " . $actualWebhookUrl . "\n";
                echo "   URL esperada: " . $expectedWebhookUrl . "\n";

                echo "\n   💡 SOLUCIÓN RECOMENDADA:\n";
                echo "   Ejecuta el siguiente comando para actualizar el webhook:\n\n";
                echo "   php artisan telegraph:set-webhook " . $bot->name . "\n";
            }
            else {
                echo "\n   ✅ La configuración del webhook parece correcta.\n";
            }
        } else {
            echo "\n   ❌ Error al consultar la información del webhook: " . ($data['description'] ?? 'Sin detalles') . "\n";
        }
    } catch (\Exception $e) {
        echo "\n   ❌ Excepción al consultar el webhook: " . $e->getMessage() . "\n";
    }

    echo "\n";

    // Verificar si el webhook en la DB es correcto
    $appUrl = config('app.url');
    $correctWebhookUrl = rtrim($appUrl, '/') . '/telegraph/' . $bot->token . '/webhook';
    $currentWebhookUrl = $bot->webhook_url;

    if ($currentWebhookUrl != $correctWebhookUrl) {
        echo "   ⚠️ ADVERTENCIA: La URL del webhook en la base de datos no tiene el formato correcto.\n";
        echo "   URL actual en DB: " . $currentWebhookUrl . "\n";
        echo "   URL correcta:     " . $correctWebhookUrl . "\n";

        echo "\n   💡 SOLUCIÓN RECOMENDADA:\n";
        echo "   Actualiza la URL del webhook en la base de datos:\n\n";
        echo "   php artisan tinker\n";
        echo "   \$bot = \\DefStudio\\Telegraph\\Models\\TelegraphBot::find(" . $bot->id . ");\n";
        echo "   \$bot->webhook_url = '" . $correctWebhookUrl . "';\n";
        echo "   \$bot->save();\n";
        echo "   exit;\n";
    }

    echo "===========================================================\n\n";
}

echo "✨ Verificación completa.\n\n";
