<?php
// Script para probar el envío de notificaciones Telegram al crear métricas en zonas con formulario

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Events\HotspotMetricCreated;
use App\Models\HotspotMetric;
use App\Models\Zona;
use App\Models\FormResponse;
use Illuminate\Support\Facades\Log;

echo "🧪 Iniciando prueba de notificación Telegram para métricas de zonas con formulario\n\n";

// Verificar que el token de Telegram esté configurado
$telegramToken = config('telegram.bots.ifree.token');
if (empty($telegramToken)) {
    echo "❌ ERROR: No se encontró el token de Telegram en la configuración.\n";
    echo "   Por favor configure el token en config/telegram.php\n";
    exit(1);
}

echo "✅ Token de Telegram configurado: " . substr($telegramToken, 0, 6) . "...\n\n";

// Buscar una zona que tenga campos de formulario
$zonaId = null;

// Verificar si se pasó un ID de zona como parámetro
$options = getopt('', ['zona_id::']);
if (isset($options['zona_id'])) {
    $zonaId = (int) $options['zona_id'];
    echo "🔍 Usando zona ID especificada: $zonaId\n";
} else {
    // Buscar una zona con campos de formulario
    $zona = Zona::where('tipo_registro', 'formulario')
        ->whereHas('campos')
        ->first();

    if ($zona) {
        $zonaId = $zona->id;
        echo "🔍 Encontrada zona con formulario: {$zona->nombre} (ID: {$zonaId})\n";
    }
}

if (!$zonaId) {
    echo "❌ No se encontró ninguna zona con formulario. Por favor cree una primero.\n";
    exit(1);
}

// Verificar si la zona tiene chats de Telegram asociados
$zona = Zona::with('telegramChats')->findOrFail($zonaId);
$chatsActivos = $zona->telegramChats()->activos()->get();

if ($chatsActivos->isEmpty()) {
    echo "⚠️ La zona seleccionada no tiene chats de Telegram activos asociados.\n";
    echo "  Las notificaciones no se enviarán a ningún chat.\n";
}

echo "📊 Información de la zona:\n";
echo "   - Nombre: {$zona->nombre}\n";
echo "   - Tipo de registro: {$zona->tipo_registro}\n";
echo "   - Campos de formulario: " . $zona->campos->count() . "\n";
echo "   - Chats de Telegram asociados: " . $chatsActivos->count() . "\n\n";

// Crear una respuesta de formulario simulada
echo "📝 Creando respuesta de formulario simulada...\n";

$macAddress = '00:' . substr(md5(uniqid()), 0, 10);
$formResponse = new FormResponse();
$formResponse->zona_id = $zonaId;
$formResponse->mac_address = $macAddress;
$formResponse->dispositivo = 'Dispositivo de prueba';
$formResponse->navegador = 'Navegador de prueba';
$formResponse->tiempo_activo = 30;
$formResponse->formulario_completado = true;
$formResponse->respuestas = [
    'nombre' => 'Usuario de Prueba',
    'email' => 'test@example.com',
    'telefono' => '1234567890'
];
$formResponse->save();

echo "✅ Respuesta de formulario creada con ID: {$formResponse->id}\n\n";

// Crear una métrica simulada
echo "📊 Creando métrica simulada...\n";

$metricaData = [
    'zona_id' => $zonaId,
    'mac_address' => $macAddress,
    'dispositivo' => 'Dispositivo de prueba',
    'navegador' => 'Navegador de prueba',
    'tipo_visual' => 'formulario',
    'duracion_visual' => 30,
    'clic_boton' => true,
    'formulario_id' => $formResponse->id
];

$metrica = HotspotMetric::create($metricaData);

echo "✅ Métrica creada con ID: {$metrica->id}\n\n";

// Despachar el evento manualmente
echo "🚀 Despachando evento HotspotMetricCreated...\n";

try {
    event(new HotspotMetricCreated($metrica));
    echo "✅ Evento despachado correctamente\n\n";
} catch (\Exception $e) {
    echo "❌ Error al despachar el evento: " . $e->getMessage() . "\n";
    exit(1);
}

echo "📬 Notificación procesada, verificando logs...\n";
echo "  Los mensajes deben haberse enviado a los chats activos asociados a la zona.\n\n";

echo "ℹ️ Resumen de la operación:\n";
echo "   - Zona utilizada: {$zona->nombre} (ID: {$zonaId})\n";
echo "   - Respuesta de formulario creada con ID: {$formResponse->id}\n";
echo "   - Métrica creada con ID: {$metrica->id}\n";
echo "   - Chats a los que se debió enviar la notificación: " . $chatsActivos->count() . "\n";

echo "\n✅ Prueba completada. Revise los logs en storage/logs/laravel.log para más detalles.\n";
