<?php
/**
 * Prueba del Portal Cautivo con Swiper Local
 * Verifica que los assets locales funcionen correctamente
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Zona;
use App\Models\Campana;
use Illuminate\Support\Facades\Storage;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRUEBA DE ASSETS LOCALES PARA PORTAL CAUTIVO ===\n\n";

// Verificar que los archivos CSS y JS existan
$assetsToCheck = [
    'public/css/swiper-local.css',
    'public/js/swiper-local.js',
    'public/js/md5.js'
];

echo "--- Verificando archivos de assets ---\n";
foreach ($assetsToCheck as $asset) {
    $path = __DIR__ . '/' . $asset;
    if (file_exists($path)) {
        $size = round(filesize($path) / 1024, 2);
        echo "✅ {$asset} existe ({$size} KB)\n";
    } else {
        echo "❌ {$asset} NO encontrado\n";
    }
}

echo "\n--- Verificando zona de prueba ---\n";

// Buscar una zona existente
$zona = Zona::first();
if (!$zona) {
    echo "❌ No hay zonas en la base de datos\n";
    exit(1);
}

echo "✅ Zona encontrada: {$zona->nombre} (ID: {$zona->id})\n";

// Verificar campañas con imágenes
echo "\n--- Verificando campañas con contenido visual ---\n";
$campanasConImagenes = Campana::where('tipo', 'imagen')
    ->whereNotNull('archivo_path')
    ->get();

$campanasConVideo = Campana::where('tipo', 'video')
    ->whereNotNull('archivo_path')
    ->get();

echo "📸 Campañas con imágenes: " . $campanasConImagenes->count() . "\n";
echo "🎥 Campañas con videos: " . $campanasConVideo->count() . "\n";

if ($campanasConImagenes->count() > 0) {
    echo "\n--- Verificando archivos de imágenes ---\n";
    foreach ($campanasConImagenes->take(3) as $campana) {
        $imagePath = storage_path('app/public/' . $campana->archivo_path);
        if (file_exists($imagePath)) {
            $size = round(filesize($imagePath) / 1024, 2);
            echo "✅ {$campana->nombre}: {$campana->archivo_path} ({$size} KB)\n";
        } else {
            echo "❌ {$campana->nombre}: Archivo no encontrado - {$campana->archivo_path}\n";
        }
    }
}

// Generar URL de prueba del portal
echo "\n--- URL de prueba ---\n";
$testUrl = "http://localhost:8000/portal/{$zona->id}";
echo "🌐 URL del portal: {$testUrl}\n";

// Simular datos de Mikrotik
$mikrotikData = [
    'mac' => '00:11:22:33:44:55',
    'ip' => '192.168.1.100',
    'link-orig' => 'http://example.com',
    'link-login-only' => 'http://mikrotik.local/login'
];

echo "\n--- Datos simulados de Mikrotik ---\n";
foreach ($mikrotikData as $key => $value) {
    echo "• {$key}: {$value}\n";
}

// Verificar que no hay dependencias externas en el HTML
echo "\n--- Verificando eliminación de CDNs externos ---\n";

$portalPath = __DIR__ . '/resources/views/portal/formulario-cautivo.blade.php';
if (file_exists($portalPath)) {
    $content = file_get_contents($portalPath);

    // Buscar CDNs que deberían haber sido removidos
    $externalCDNs = [
        'cdn.jsdelivr.net/npm/swiper',
        'unpkg.com/swiper',
        'cdnjs.cloudflare.com/ajax/libs/Swiper'
    ];

    $foundCDNs = [];
    foreach ($externalCDNs as $cdn) {
        if (strpos($content, $cdn) !== false) {
            $foundCDNs[] = $cdn;
        }
    }

    if (empty($foundCDNs)) {
        echo "✅ No se encontraron CDNs externos de Swiper\n";
    } else {
        echo "❌ Aún hay CDNs externos: " . implode(', ', $foundCDNs) . "\n";
    }

    // Verificar que usa assets locales
    if (strpos($content, 'swiper-local.css') !== false && strpos($content, 'swiper-local.js') !== false) {
        echo "✅ Portal configurado para usar assets locales\n";
    } else {
        echo "❌ Portal no está usando los assets locales\n";
    }
} else {
    echo "❌ Archivo del portal no encontrado\n";
}

echo "\n--- Verificación de funcionamiento ---\n";

// Comprobar que las rutas del portal estén definidas
try {
    $route = route('zona.portal', ['id' => $zona->id]);
    echo "✅ Ruta del portal generada: {$route}\n";
} catch (Exception $e) {
    echo "❌ Error generando ruta: " . $e->getMessage() . "\n";
}

echo "\n🎉 ¡Verificación completa!\n";
echo "\n--- INSTRUCCIONES DE PRUEBA ---\n";
echo "1. Ejecuta: php artisan serve\n";
echo "2. Visita: {$testUrl}?mac=" . $mikrotikData['mac'] . "\n";
echo "3. Verifica que el carrusel funcione sin errores de consola\n";
echo "4. Confirma que no hay errores 404 para archivos CSS/JS\n";
echo "5. Prueba en un entorno sin internet para confirmar funcionamiento offline\n";
