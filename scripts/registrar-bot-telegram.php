<?php
// Script para registrar un bot de Telegram en la base de datos
// Ejecutar: php registrar-bot-telegram.php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📝 Registrando bot de Telegram en la base de datos\n";
echo "===============================================\n\n";

// Verificar si la tabla existe
try {
    $tablesExist = DB::select("SHOW TABLES LIKE 'telegraph_bots'");

    if (empty($tablesExist)) {
        echo "❌ La tabla telegraph_bots no existe en la base de datos\n";
        echo "   Ejecuta las migraciones primero: php artisan migrate\n";
        exit(1);
    }

    // Verificar si la columna webhook_url existe
    $columns = DB::select("SHOW COLUMNS FROM telegraph_bots");
    $columnNames = array_map(function($col) {
        return $col->Field;
    }, $columns);

    if (!in_array('webhook_url', $columnNames)) {
        echo "⚠️ La columna 'webhook_url' no existe en la tabla telegraph_bots\n";
        echo "   Ejecuta la migración: php artisan migrate --path=/database/migrations/2025_06_13_000000_add_webhook_url_to_telegraph_bots_table.php\n\n";

        $addColumn = readline("¿Quieres agregar la columna ahora? (s/n): ");
        if (strtolower($addColumn) === 's') {
            DB::statement("ALTER TABLE telegraph_bots ADD COLUMN webhook_url VARCHAR(255) AFTER token");
            echo "✅ Columna 'webhook_url' agregada exitosamente\n\n";
        }
    }

    // Solicitar los datos del bot
    echo "📋 Ingresa los datos del bot:\n";
    $nombre = readline("Nombre del bot [iFreeBotV3.0]: ");
    if (empty($nombre)) {
        $nombre = "iFreeBotV3.0";
    }

    $token = readline("Token del bot (obtenido de @BotFather): ");
    if (empty($token)) {
        echo "❌ El token no puede estar vacío\n";
        exit(1);
    }

    // Verificar si el bot ya existe
    $botExists = DB::table('telegraph_bots')->where('token', $token)->first();

    if ($botExists) {
        echo "⚠️ Ya existe un bot con este token: {$botExists->name} (ID: {$botExists->id})\n";
        $update = readline("¿Quieres actualizarlo? (s/n): ");

        if (strtolower($update) === 's') {
            DB::table('telegraph_bots')
                ->where('id', $botExists->id)
                ->update([
                    'name' => $nombre
                ]);

            echo "✅ Bot actualizado exitosamente\n";
            $botId = $botExists->id;
        } else {
            echo "❌ Operación cancelada\n";
            exit(1);
        }
    } else {
        // Registrar el bot
        $botId = DB::table('telegraph_bots')->insertGetId([
            'name' => $nombre,
            'token' => $token,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if ($botId) {
            echo "✅ Bot registrado exitosamente con ID: {$botId}\n";
        } else {
            echo "❌ Error al registrar el bot\n";
            exit(1);
        }
    }

    // Ahora intentar obtener la información del bot desde la API de Telegram
    echo "\n🔍 Verificando información del bot en la API de Telegram...\n";

    $ch = curl_init("https://api.telegram.org/bot{$token}/getMe");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "⚠️ Error HTTP {$httpCode} al verificar el bot\n";
        echo "   Respuesta: {$response}\n";
        echo "   El bot está registrado en la base de datos, pero puede haber problemas con el token\n";
    } else {
        $botInfo = json_decode($response, true);

        if (!isset($botInfo['ok']) || $botInfo['ok'] !== true) {
            echo "⚠️ Error al verificar el bot: " . json_encode($botInfo) . "\n";
        } else {
            echo "✅ Bot verificado correctamente:\n";
            echo "   ID: " . $botInfo['result']['id'] . "\n";
            echo "   Nombre: " . $botInfo['result']['first_name'] . "\n";
            echo "   Username: @" . $botInfo['result']['username'] . "\n";
        }
    }

    // Configurar el webhook
    echo "\n⚙️ ¿Quieres configurar el webhook ahora? (s/n): ";
    $configureWebhook = readline();

    if (strtolower($configureWebhook) === 's') {
        $baseUrl = config('app.url');

        if (empty($baseUrl)) {
            echo "⚠️ La URL base no está configurada en config/app.php\n";
            $baseUrl = readline("Ingresa la URL base (ej: https://example.com): ");

            if (empty($baseUrl)) {
                echo "❌ La URL base no puede estar vacía\n";
                exit(1);
            }
        }

        echo "📋 URL base de la aplicación: {$baseUrl}\n";

        // Construir la URL del webhook
        $webhookUrl = rtrim($baseUrl, '/') . '/telegraph/' . $token . '/webhook';
        echo "📋 URL del webhook: {$webhookUrl}\n";

        $confirm = readline("¿Confirmar la configuración del webhook? (s/n): ");

        if (strtolower($confirm) === 's') {
            echo "⚙️ Configurando webhook...\n";

            // Configurar el webhook usando curl
            $ch = curl_init("https://api.telegram.org/bot{$token}/setWebhook");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'url' => $webhookUrl,
                'max_connections' => 40
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                echo "❌ Error HTTP {$httpCode} al configurar webhook\n";
                echo "   Respuesta: {$response}\n";
            } else {
                $webhookResponse = json_decode($response, true);

                if (isset($webhookResponse['ok']) && $webhookResponse['ok'] === true) {
                    echo "✅ Webhook configurado con éxito\n";

                    // Actualizar la URL del webhook en la base de datos si la columna existe
                    if (in_array('webhook_url', $columnNames)) {
                        DB::table('telegraph_bots')
                            ->where('id', $botId)
                            ->update([
                                'webhook_url' => $webhookUrl
                            ]);

                        echo "✅ URL del webhook guardada en la base de datos\n";
                    }
                } else {
                    echo "❌ Error al configurar webhook: " . json_encode($webhookResponse) . "\n";
                }
            }
        }
    }

    echo "\n✅ Proceso completado\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
