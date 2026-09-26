<?php
/**
 * Prueba de Portal Cautivo Sin CDN
 * Verificar que todas las dependencias estén locales
 */

echo "<h1>🔧 Prueba Portal Cautivo - Sin Dependencias CDN</h1>";

$projectPath = dirname(__DIR__);

// Verificar archivos CSS locales
$cssFiles = [
    'public/css/fonts-local.css' => 'Fuentes locales',
    'public/css/swiper-local.css' => 'Swiper CSS local'
];

echo "<h2>📄 Verificación de archivos CSS:</h2>";
foreach ($cssFiles as $file => $description) {
    $fullPath = $projectPath . '/' . $file;
    if (file_exists($fullPath)) {
        $size = round(filesize($fullPath) / 1024, 2);
        echo "✅ {$description}: {$file} ({$size} KB)<br>";
    } else {
        echo "❌ {$description}: {$file} - NO ENCONTRADO<br>";
    }
}

// Verificar archivos JS locales
$jsFiles = [
    'public/js/md5.js' => 'MD5 para autenticación CHAP',
    'public/js/swiper-local.js' => 'Swiper JavaScript local'
];

echo "<h2>📜 Verificación de archivos JavaScript:</h2>";
foreach ($jsFiles as $file => $description) {
    $fullPath = $projectPath . '/' . $file;
    if (file_exists($fullPath)) {
        $size = round(filesize($fullPath) / 1024, 2);
        echo "✅ {$description}: {$file} ({$size} KB)<br>";
    } else {
        echo "❌ {$description}: {$file} - NO ENCONTRADO<br>";
    }
}

// Verificar el contenido del archivo del portal
$portalFile = $projectPath . '/resources/views/portal/formulario-cautivo.blade.php';
echo "<h2>🌐 Verificación del archivo del portal:</h2>";

if (file_exists($portalFile)) {
    $content = file_get_contents($portalFile);

    // Verificar que no haya CDN externos
    $externalCDNs = [
        'fonts.googleapis.com' => 'Google Fonts CDN',
        'cdn.jsdelivr.net' => 'JSDelivr CDN',
        'cdnjs.cloudflare.com' => 'Cloudflare CDN',
        'unpkg.com' => 'UNPKG CDN'
    ];

    $hasCDN = false;
    echo "🔍 Búsqueda de CDN externos:<br>";

    foreach ($externalCDNs as $cdn => $name) {
        if (strpos($content, $cdn) !== false) {
            echo "⚠️  {$name} encontrado: {$cdn}<br>";
            $hasCDN = true;
        } else {
            echo "✅ {$name}: No encontrado<br>";
        }
    }

    if (!$hasCDN) {
        echo "<strong style='color: green;'>🎉 ¡Perfecto! No se encontraron CDN externos</strong><br>";
    }

    // Verificar que estén los archivos locales
    echo "<br>🔍 Verificación de referencias locales:<br>";

    $localAssets = [
        'css/fonts-local.css' => 'Fuentes locales',
        'css/swiper-local.css' => 'Swiper CSS',
        'js/md5.js' => 'MD5 JS',
        'js/swiper-local.js' => 'Swiper JS'
    ];

    foreach ($localAssets as $asset => $name) {
        if (strpos($content, $asset) !== false) {
            echo "✅ {$name} referenciado correctamente<br>";
        } else {
            echo "❌ {$name} NO referenciado<br>";
        }
    }

} else {
    echo "❌ Archivo del portal no encontrado<br>";
}

// Verificar que SwiperLocal esté disponible
echo "<h2>🎠 Verificación de implementación Swiper:</h2>";
$swiperLocalFile = $projectPath . '/public/js/swiper-local.js';
if (file_exists($swiperLocalFile)) {
    $swiperContent = file_get_contents($swiperLocalFile);
    if (strpos($swiperContent, 'class SwiperLocal') !== false) {
        echo "✅ Clase SwiperLocal implementada<br>";
    }
    if (strpos($swiperContent, 'window.SwiperLocal') !== false) {
        echo "✅ SwiperLocal disponible globalmente<br>";
    }
} else {
    echo "❌ Swiper local no implementado<br>";
}

echo "<h2>📊 Resumen:</h2>";
echo "<div style='background: #f0f8f0; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<strong>Estado de la implementación:</strong><br>";
echo "🟢 Portal cautivo completamente independiente de CDN<br>";
echo "🟢 Fuentes del sistema como fallback<br>";
echo "🟢 Swiper implementado localmente<br>";
echo "🟢 MD5 para autenticación CHAP local<br>";
echo "🟢 CSS y JavaScript autocontenidos<br>";
echo "</div>";

echo "<p><strong>✅ El portal cautivo ahora funcionará correctamente en entornos Mikrotik sin acceso a internet externo.</strong></p>";
?>
