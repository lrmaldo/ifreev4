<?php
// Script para verificar la configuración de los bots de Telegraph en la base de datos
// Ejecutar: php check-telegraph-bots.php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Verificando bots de Telegraph en la base de datos...\n\n";

// Verificar tabla telegraph_bots
try {
    echo "📊 Tabla telegraph_bots:\n";
    $bots = \DefStudio\Telegraph\Models\TelegraphBot::all();

    if ($bots->isEmpty()) {
        echo "❌ No se encontraron bots registrados en la tabla telegraph_bots\n";
        echo "   Posible causa del problema 'bot_id: null' en los registros del webhook\n\n";
    } else {
        echo "✅ Se encontraron " . $bots->count() . " bots registrados:\n\n";

        foreach ($bots as $bot) {
            echo "📡 Bot ID: {$bot->id}\n";
            echo "   Nombre: {$bot->name}\n";
            echo "   Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n";

            // Verificar asociación con chats
            $chats = $bot->chats()->count();
            echo "   Chats asociados: {$chats}\n";

            // Verificar si hay webhooks configurados
            echo "   Webhook activo: " . ($bot->webhook_url ? "✅ SÍ" : "❌ NO") . "\n";
            if ($bot->webhook_url) {
                echo "   URL del webhook: {$bot->webhook_url}\n";
            }

            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error al acceder a la tabla telegraph_bots: " . $e->getMessage() . "\n";
    echo "   Es posible que la tabla no exista o no se pueda acceder.\n";

    // Verificar si la tabla existe
    try {
        $tableExists = \Illuminate\Support\Facades\Schema::hasTable('telegraph_bots');
        echo "   La tabla telegraph_bots " . ($tableExists ? "existe" : "NO existe") . " en la base de datos.\n";

        if (!$tableExists) {
            echo "   Necesitas ejecutar las migraciones de Telegraph: php artisan migrate\n";
        }
    } catch (\Exception $schemaException) {
        echo "   No se pudo verificar la existencia de la tabla: " . $schemaException->getMessage() . "\n";
    }
}

echo "\n";

// Verificar tabla personalizada TelegramChat del proyecto
try {
    echo "📊 Tabla telegram_chats (personalizada):\n";
    $telegramChats = \App\Models\TelegramChat::all();

    if ($telegramChats->isEmpty()) {
        echo "❌ No se encontraron chats registrados en la tabla telegram_chats\n";
    } else {
        echo "✅ Se encontraron " . $telegramChats->count() . " chats registrados:\n\n";

        foreach ($telegramChats->take(5) as $chat) {
            echo "💬 Chat ID: {$chat->chat_id}\n";
            echo "   Nombre: {$chat->nombre}\n";
            echo "   Tipo: {$chat->tipo}\n";

            // Verificar asociación con zonas
            $zonasCount = $chat->zonas()->count();
            echo "   Zonas asociadas: {$zonasCount}\n";

            echo "\n";
        }

        if ($telegramChats->count() > 5) {
            echo "...y " . ($telegramChats->count() - 5) . " más\n\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error al acceder a la tabla telegram_chats: " . $e->getMessage() . "\n";
}

echo "\n";

// Verificar configuración de Telegraph en el sistema
echo "⚙️ Configuración de Telegraph:\n";
echo "   URL Webhook configurada: " . config('telegraph.webhook.url') . "\n";
echo "   Handler configurado: " . config('telegraph.webhook.handler') . "\n";
echo "   Modelo Bot: " . config('telegraph.models.bot') . "\n";

// Verificar si hay instancias en el contenedor
echo "\n🧰 Instancias en el contenedor:\n";
try {
    $instances = ['telegraph', 'telegraph.bot'];
    foreach ($instances as $instance) {
        if (app()->bound($instance)) {
            echo "  ✅ La instancia '{$instance}' está registrada en el contenedor\n";
            $value = app()->make($instance);
            echo "     Clase: " . get_class($value) . "\n";
        } else {
            echo "  ⚠️ La instancia '{$instance}' NO está registrada en el contenedor\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error al verificar instancias: " . $e->getMessage() . "\n";
}

echo "\n🏁 Verificación completa.\n";
echo "\n📝 RECOMENDACIONES:\n";
echo "1. Asegúrate de que hay al menos un bot en la tabla 'telegraph_bots'\n";
echo "2. El token del bot debe ser válido y el webhook debe estar configurado correctamente\n";
echo "3. La ruta debe ser única (no duplicada entre web.php y Telegraph::telegraph())\n";
echo "4. Para resolver el problema 'bot_id: null', verifica la inyección de dependencias en la ruta del webhook\n";
