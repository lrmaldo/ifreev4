<?php

// Script para simular una solicitud webhook de Telegram y probar los controladores
// Ejecutar: php test-telegram-webhook.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔄 Simulador de Webhook de Telegram\n";
echo "====================================\n\n";

// Obtener bot para simular webhook
$bot = \DefStudio\Telegraph\Models\TelegraphBot::first();
if (!$bot) {
    echo "❌ Error: No se encontró ningún bot configurado.\n";
    exit(1);
}

echo "ℹ️ Bot encontrado: {$bot->name}\n";
echo "ℹ️ Token: " . substr($bot->token, 0, 5) . "..." . substr($bot->token, -5) . "\n\n";

// Pedir un chat_id
echo "📝 Ingresa un chat_id para simular el webhook: ";
$chatId = trim(fgets(STDIN));

if (empty($chatId)) {
    echo "❌ No se ingresó un chat_id válido. Usando 123456789 como ejemplo.\n";
    $chatId = "123456789";
}

// Seleccionar qué tipo de webhook simular
echo "\nSelecciona el tipo de webhook a simular:\n";
echo "1. Comando /start\n";
echo "2. Comando /zonas\n";
echo "3. Comando /ayuda\n";
echo "4. Mensaje normal (sin comando)\n";
echo "Opción: ";
$option = trim(fgets(STDIN));

// Construir el mensaje webhook según la opción
switch ($option) {
    case "1":
        $command = "/start";
        $type = "Comando start";
        break;
    case "2":
        $command = "/zonas";
        $type = "Comando zonas";
        break;
    case "3":
        $command = "/ayuda";
        $type = "Comando ayuda";
        break;
    default:
        $command = "Hola, necesito ayuda";
        $type = "Mensaje normal";
        break;
}

$webhookData = [
    'update_id' => rand(10000000, 99999999),
    'message' => [
        'message_id' => rand(1000, 9999),
        'from' => [
            'id' => $chatId,
            'is_bot' => false,
            'first_name' => 'Usuario',
            'last_name' => 'Prueba',
            'username' => 'usuario_prueba',
        ],
        'chat' => [
            'id' => $chatId,
            'first_name' => 'Usuario',
            'last_name' => 'Prueba',
            'username' => 'usuario_prueba',
            'type' => 'private',
        ],
        'date' => time(),
        'text' => $command,
    ]
];

echo "\n📩 Simulando webhook de tipo: {$type}\n";
echo "📄 Datos del webhook:\n";
echo json_encode($webhookData, JSON_PRETTY_PRINT) . "\n\n";

// Crear una solicitud HTTP simulada
$request = \Illuminate\Http\Request::create(
    '/telegram/webhook',
    'POST',
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/json'],
    json_encode($webhookData)
);

// Registrar todos los mensajes de log para la prueba
$logs = [];
\Illuminate\Support\Facades\Log::listen(function ($level, $message, $context) use (&$logs) {
    $logs[] = [
        'level' => $level,
        'message' => $message,
        'context' => $context
    ];
});

echo "🚀 Enviando solicitud al controlador...\n";

// Obtener la instancia del controlador
$controllerClass = config('telegraph.webhook.handler');

try {
    // Instanciar el controlador manualmente
    $controller = app($controllerClass);
    echo "✅ Controlador instanciado correctamente: " . get_class($controller) . "\n\n";

    // Llamar al método handle directamente (puede requerir ajustes según tu controlador)
    $controller->handle($request, $bot);

    echo "✅ Método handle ejecutado correctamente\n\n";
} catch (\Exception $e) {
    echo "❌ Error ejecutando el controlador: " . $e->getMessage() . "\n";
    echo "📚 Traza de error:\n" . $e->getTraceAsString() . "\n\n";
}

// Mostrar logs registrados
echo "📋 Logs generados durante la prueba:\n";
echo "==================================\n";
foreach ($logs as $index => $log) {
    echo "[{$log['level']}] {$log['message']}\n";
    if (!empty($log['context'])) {
        echo "  Contexto: " . json_encode($log['context'], JSON_PRETTY_PRINT) . "\n";
    }
    echo "---\n";
}

echo "\n✨ Prueba completada.\n";
echo "Verifica si se ejecutó correctamente el método del controlador y si se enviaron respuestas.\n";
