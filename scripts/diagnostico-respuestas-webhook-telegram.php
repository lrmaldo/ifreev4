<?php
// Script para diagnosticar problemas con las respuestas del webhook de Telegram
// Ejecutar: php diagnostico-respuestas-webhook-telegram.php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

echo "🔍 Diagnóstico de respuestas de webhook de Telegram\n";
echo "=================================================\n\n";

// Verificar la configuración de Telegraph
echo "📋 Verificando configuración de Telegraph...\n";
$telegraphConfig = config('telegraph');
echo "  - URL base de API: " . $telegraphConfig['telegram_api_url'] . "\n";
echo "  - Parse mode: " . $telegraphConfig['default_parse_mode'] . "\n";
echo "  - URL de webhook: " . $telegraphConfig['webhook']['url'] . "\n";
echo "  - Handler: " . $telegraphConfig['webhook']['handler'] . "\n";
echo "  - Debug habilitado: " . ($telegraphConfig['webhook']['debug'] ? "SI" : "NO") . "\n";

// Verificar la existencia del controlador
$handlerClass = $telegraphConfig['webhook']['handler'];
echo "\n📋 Verificando controlador del webhook...\n";
if (class_exists($handlerClass)) {
    echo "  ✅ El controlador existe: " . $handlerClass . "\n";

    // Verificar que extiende de WebhookHandler
    $parentClass = get_parent_class($handlerClass);
    if ($parentClass === 'DefStudio\Telegraph\Handlers\WebhookHandler') {
        echo "  ✅ El controlador extiende correctamente de WebhookHandler\n";
    } else {
        echo "  ❌ El controlador no extiende de WebhookHandler. Clase base: " . $parentClass . "\n";
    }

    // Verificar los métodos disponibles
    echo "  📊 Métodos disponibles en el controlador:\n";
    $methods = get_class_methods($handlerClass);
    $commands = [];
    foreach ($methods as $method) {
        if (!str_starts_with($method, '__') && !in_array($method, ['handle', 'registerChat', 'getChatName', 'getChatType', 'debugWebhook', 'shouldDebug'])) {
            $commands[] = $method;
        }
    }

    if (count($commands) > 0) {
        echo "  ✅ Comandos implementados: " . implode(", ", $commands) . "\n";
    } else {
        echo "  ⚠️ No se encontraron comandos implementados en el controlador\n";
    }
} else {
    echo "  ❌ El controlador no existe: " . $handlerClass . "\n";
}

// Verificar base de datos y tablas
echo "\n📋 Verificando tablas de Telegraph en la base de datos...\n";

try {
    $tablesExist = DB::select("SHOW TABLES LIKE 'telegraph_bots'");

    if (count($tablesExist) > 0) {
        echo "  ✅ Tabla telegraph_bots encontrada\n";

        // Verificar estructura
        $columns = DB::select("SHOW COLUMNS FROM telegraph_bots");
        $columnNames = array_map(function($col) {
            return $col->Field;
        }, $columns);

        echo "  📊 Columnas en la tabla telegraph_bots: " . implode(", ", $columnNames) . "\n";

        // Verificar si la columna webhook_url está presente
        if (in_array('webhook_url', $columnNames)) {
            echo "  ✅ Columna webhook_url existe en la tabla\n";
        } else {
            echo "  ❌ Columna webhook_url NO existe en la tabla\n";
            echo "     Esto puede causar problemas en la configuración del webhook\n";
        }

        // Obtener los bots
        $bots = DB::table('telegraph_bots')->get();
        echo "\n  📊 Bots registrados: " . $bots->count() . "\n";

        foreach ($bots as $bot) {
            echo "  🤖 Bot ID: " . $bot->id . ", Nombre: " . $bot->name . "\n";
            echo "     Token: " . substr($bot->token, 0, 6) . "..." . substr($bot->token, -5) . "\n";

            if (isset($bot->webhook_url)) {
                echo "     Webhook URL: " . $bot->webhook_url . "\n";
            } else {
                echo "     Webhook URL: No configurada\n";
            }
            echo "\n";
        }
    } else {
        echo "  ❌ Tabla telegraph_bots NO encontrada\n";
    }

    $tablesExist = DB::select("SHOW TABLES LIKE 'telegraph_chats'");
    if (count($tablesExist) > 0) {
        echo "  ✅ Tabla telegraph_chats encontrada\n";

        // Contar chats
        $chatCount = DB::table('telegraph_chats')->count();
        echo "  📊 Chats registrados: " . $chatCount . "\n";
    } else {
        echo "  ❌ Tabla telegraph_chats NO encontrada\n";
    }

    // Verificar tablas de migraciones
    $migrations = DB::table('migrations')
        ->where('migration', 'like', '%telegraph%')
        ->get();

    echo "\n  📊 Migraciones de Telegraph ejecutadas: " . $migrations->count() . "\n";
    foreach ($migrations as $migration) {
        echo "     - " . $migration->migration . " (Batch: " . $migration->batch . ")\n";
    }

} catch (\Exception $e) {
    echo "  ❌ Error al verificar tablas: " . $e->getMessage() . "\n";
}

// Verificar logs recientes
echo "\n📋 Buscando entradas recientes en los logs...\n";

$logFile = storage_path('logs/laravel.log');
if (!file_exists($logFile)) {
    echo "  ❌ El archivo de logs no existe: " . $logFile . "\n";

    // Buscar otros archivos de log
    $otherLogFiles = glob(storage_path('logs/*.log'));
    if (!empty($otherLogFiles)) {
        echo "  Se encontraron otros archivos de log:\n";
        foreach ($otherLogFiles as $file) {
            echo "  - " . basename($file) . "\n";
        }
        $logFile = $otherLogFiles[0];
    } else {
        echo "  No se encontraron archivos de log\n";
        $logFile = null;
    }
}

if ($logFile) {
    try {
        // Leer las últimas 200 líneas del log
        $command = PHP_OS === 'WINNT'
            ? "powershell -Command \"Get-Content -Path '$logFile' -Tail 200\""
            : "tail -n 200 '$logFile'";

        $lastLines = shell_exec($command);

        // Buscar entradas relacionadas con Telegram/Telegraph
        $lines = explode("\n", $lastLines);
        $telegramEntries = [];

        foreach ($lines as $line) {
            if (stripos($line, 'telegram') !== false ||
                stripos($line, 'telegraph') !== false ||
                stripos($line, 'webhook') !== false) {
                $telegramEntries[] = $line;
            }
        }

        if (count($telegramEntries) > 0) {
            echo "  ✅ Se encontraron " . count($telegramEntries) . " entradas relacionadas con Telegram\n";
            echo "  📊 Últimas 5 entradas:\n";

            $lastFive = array_slice($telegramEntries, -5);
            foreach ($lastFive as $entry) {
                echo "  - " . substr($entry, 0, 150) . "...\n";
            }
        } else {
            echo "  ❌ No se encontraron entradas relacionadas con Telegram en los logs recientes\n";
        }

        // Buscar errores específicamente
        $errors = [];
        foreach ($lines as $line) {
            if ((stripos($line, 'telegram') !== false ||
                 stripos($line, 'telegraph') !== false ||
                 stripos($line, 'webhook') !== false) &&
                (stripos($line, 'error') !== false ||
                 stripos($line, 'exception') !== false)) {
                $errors[] = $line;
            }
        }

        if (count($errors) > 0) {
            echo "\n  ⚠️ Se encontraron " . count($errors) . " errores relacionados con Telegram\n";
            echo "  📊 Últimos 3 errores:\n";

            $lastThree = array_slice($errors, -3);
            foreach ($lastThree as $error) {
                echo "  - " . substr($error, 0, 150) . "...\n";
            }
        } else {
            echo "\n  ✅ No se encontraron errores relacionados con Telegram en los logs recientes\n";
        }

    } catch (\Exception $e) {
        echo "  ❌ Error al leer logs: " . $e->getMessage() . "\n";
    }
}

// Probar cuál URL se está usando actualmente para el webhook
echo "\n📋 Verificando las URLs de webhooks actualmente registradas...\n";

$botTokens = $bots->pluck('token')->toArray();

foreach ($botTokens as $token) {
    echo "\n  🔍 Verificando bot con token: " . substr($token, 0, 6) . "..." . substr($token, -5) . "\n";

    try {
        // Usar curl para obtener la información del webhook
        $ch = curl_init("https://api.telegram.org/bot{$token}/getWebhookInfo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo "  ❌ Error HTTP {$httpCode} al obtener información del webhook\n";
            echo "     Respuesta: {$response}\n";
            continue;
        }

        $webhookInfo = json_decode($response, true);

        if (!isset($webhookInfo['ok']) || $webhookInfo['ok'] !== true) {
            echo "  ❌ Error al obtener información del webhook: " . json_encode($webhookInfo) . "\n";
            continue;
        }

        $info = $webhookInfo['result'];

        echo "  ✅ Información del webhook obtenida:\n";
        echo "     URL: " . ($info['url'] ?? 'No configurada') . "\n";
        echo "     Pendiente: " . ($info['pending_update_count'] ?? 0) . " actualizaciones\n";

        if (isset($info['last_error_date']) && isset($info['last_error_message'])) {
            $errorDate = date('Y-m-d H:i:s', $info['last_error_date']);
            echo "     ⚠️ Último error: {$info['last_error_message']} ({$errorDate})\n";
        } else {
            echo "     ✅ Sin errores reportados\n";
        }

        // Comparar con la URL que debería tener
        $expectedUrl = rtrim(config('app.url'), '/') . '/telegraph/' . $token . '/webhook';
        if (isset($info['url']) && $info['url'] === $expectedUrl) {
            echo "     ✅ La URL del webhook coincide con la esperada\n";
        } elseif (isset($info['url'])) {
            echo "     ⚠️ La URL actual ({$info['url']}) no coincide con la esperada ({$expectedUrl})\n";
            echo "     Esto puede causar problemas en la recepción de mensajes\n";
        }

    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Diagnóstico finalizado\n";
echo "Para más detalles, revisa los logs completos o ejecuta 'php verificar-logs-telegram.php'\n";
