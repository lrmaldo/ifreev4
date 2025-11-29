<?php

/**
 * Script de diagnóstico para verificar notificaciones de Telegram
 * Este script verifica:
 * 1. Si hay zonas registradas
 * 2. Si hay chats de Telegram registrados
 * 3. Si hay zonas asociadas a los chats
 * 4. Si el evento se está disparando correctamente
 */

// Incluir el autoloader de Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Bootear la aplicación
$app->boot();

use App\Models\Zona;
use App\Models\TelegramChat;
use Illuminate\Support\Facades\Log;

echo "================== DIAGNÓSTICO DE NOTIFICACIONES TELEGRAM ==================\n\n";

// 1. Verificar zonas
echo "1. ZONAS DISPONIBLES\n";
echo "-------------------\n";
$zonas = Zona::where('activo', true)->get();
echo "Total de zonas activas: {$zonas->count()}\n";
foreach ($zonas as $zona) {
    echo "  - ID: {$zona->id}, Nombre: {$zona->nombre}, Tipo: {$zona->tipo_registro}\n";
}
echo "\n";

// 2. Verificar chats de Telegram
echo "2. CHATS DE TELEGRAM REGISTRADOS\n";
echo "--------------------------------\n";
$chats = TelegramChat::where('activo', true)->get();
echo "Total de chats activos: {$chats->count()}\n";
foreach ($chats as $chat) {
    echo "  - Chat ID: {$chat->chat_id}, Nombre: {$chat->nombre}, Tipo: {$chat->tipo}\n";
    
    // Verificar zonas asociadas
    $zonasAsociadas = $chat->zonas()->get();
    echo "    Zonas asociadas: {$zonasAsociadas->count()}\n";
    foreach ($zonasAsociadas as $zona) {
        echo "      • {$zona->nombre} (ID: {$zona->id})\n";
    }
}
echo "\n";

// 3. Verificar asociaciones zona-chat
echo "3. ASOCIACIONES ZONA-CHAT\n";
echo "------------------------\n";
foreach ($zonas as $zona) {
    $chatsAsociados = $zona->telegramChats()->activos()->get();
    echo "Zona '{$zona->nombre}' (ID: {$zona->id}):\n";
    if ($chatsAsociados->count() > 0) {
        foreach ($chatsAsociados as $chat) {
            echo "  ✓ Chat: {$chat->nombre} (ID: {$chat->chat_id})\n";
        }
    } else {
        echo "  ✗ NO HAY CHATS ASOCIADOS\n";
    }
}
echo "\n";

// 4. Verificar configuración de Telegram
echo "4. CONFIGURACIÓN DE TELEGRAM\n";
echo "----------------------------\n";
$token = config('telegram.bots.ifree.token');
if ($token) {
    echo "✓ Token configurado\n";
    echo "  Token (primeros 20 chars): ".substr($token, 0, 20)."...\n";
} else {
    echo "✗ NO HAY TOKEN CONFIGURADO\n";
}
echo "\n";

// 5. Verificar listeners registrados
echo "5. EVENT LISTENERS\n";
echo "------------------\n";
echo "SendTelegramNotification: " . (class_exists('App\Listeners\SendTelegramNotification') ? "✓" : "✗") . "\n";
echo "SendTelegramFormMetricNotification: " . (class_exists('App\Listeners\SendTelegramFormMetricNotification') ? "✓" : "✗") . "\n";
echo "\n";

// 6. Últimas métricas creadas
echo "6. ÚLTIMAS MÉTRICAS CREADAS\n";
echo "---------------------------\n";
$metricas = \App\Models\HotspotMetric::orderBy('created_at', 'desc')->limit(5)->get();
echo "Total: {$metricas->count()}\n";
foreach ($metricas as $metrica) {
    echo "  - ID: {$metrica->id}, Zona: {$metrica->zona->nombre}, Fecha: {$metrica->created_at}\n";
}
echo "\n";

echo "================== FIN DEL DIAGNÓSTICO ==================\n";
