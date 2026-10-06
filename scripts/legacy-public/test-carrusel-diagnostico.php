<?php
/**
 * Herramienta de diagnóstico para problemas de carrusel en el portal cautivo
 * Este script analiza la configuración y muestra información útil para
 * resolver problemas con la visualización de múltiples imágenes.
 */

// Incluir el autoloader
require __DIR__ . '/../vendor/autoload.php';

// Inicializar aplicación de Laravel (modo ligero)
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Usar fachadas
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Configuración
$output = [];
$output[] = [
    'title' => '🔍 Herramienta de Diagnóstico de Carrusel',
    'content' => 'Esta herramienta verifica la configuración de carruseles en el portal cautivo'
];

// 1. Verificar archivo swiper-local.js
$swiperLocalPath = public_path('js/swiper-local.js');
if (file_exists($swiperLocalPath)) {
    $swiperContent = file_get_contents($swiperLocalPath);
    $swiperSize = round(strlen($swiperContent) / 1024, 2);
    $output[] = [
        'title' => '✅ Archivo swiper-local.js',
        'content' => "Encontrado ({$swiperSize} KB)"
    ];

    // Verificar características clave
    $features = [
        'setupTransitions' => strpos($swiperContent, 'setupTransitions') !== false,
        'goToSlide' => strpos($swiperContent, 'goToSlide') !== false,
        'startAutoplay' => strpos($swiperContent, 'startAutoplay') !== false,
        'preloadImages' => strpos($swiperContent, 'preloadImages') !== false,
    ];

    $output[] = [
        'title' => 'Características de SwiperLocal',
        'content' => json_encode($features, JSON_PRETTY_PRINT)
    ];
} else {
    $output[] = [
        'title' => '❌ Error: swiper-local.js',
        'content' => 'Archivo no encontrado en ' . $swiperLocalPath
    ];
}

// 2. Verificar tabla de campanas
try {
    $campanasTotal = DB::table('campanas')->count();
    $campanasActivas = DB::table('campanas')
        ->where('visible', 1)
        ->where('fecha_inicio', '<=', now())
        ->where('fecha_fin', '>=', now())
        ->count();

    $output[] = [
        'title' => '📊 Estadísticas de campañas',
        'content' => "Total: {$campanasTotal}, Activas: {$campanasActivas}"
    ];

    // Campañas por tipo
    $campanasPorTipo = DB::table('campanas')
        ->select('tipo', DB::raw('count(*) as total'))
        ->groupBy('tipo')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->tipo ?: 'sin_tipo' => $item->total];
        })
        ->toArray();

    $output[] = [
        'title' => '📊 Campañas por tipo',
        'content' => json_encode($campanasPorTipo, JSON_PRETTY_PRINT)
    ];
} catch (Exception $e) {
    $output[] = [
        'title' => '❌ Error al consultar campañas',
        'content' => $e->getMessage()
    ];
}

// 3. Verificar asignaciones zona-campaña
try {
    $zonasCampanasCount = DB::table('campana_zona')->count();
    $zonasConMultiplesCampanas = DB::table('campana_zona')
        ->select('zona_id', DB::raw('count(*) as total'))
        ->groupBy('zona_id')
        ->having('total', '>', 1)
        ->get();

    $output[] = [
        'title' => '🔗 Relaciones zona-campaña',
        'content' => "Total relaciones: {$zonasCampanasCount}, Zonas con múltiples campañas: {$zonasConMultiplesCampanas->count()}"
    ];

    // Mostrar detalles de algunas zonas con múltiples campañas
    if ($zonasConMultiplesCampanas->count() > 0) {
        $zonasDetalles = [];
        foreach ($zonasConMultiplesCampanas->take(5) as $relacion) {
            $zonaInfo = DB::table('zonas')->where('id', $relacion->zona_id)->first();
            $campanasInfo = DB::table('campanas')
                ->join('campana_zona', 'campanas.id', '=', 'campana_zona.campana_id')
                ->where('campana_zona.zona_id', $relacion->zona_id)
                ->select('campanas.id', 'campanas.titulo', 'campanas.tipo')
                ->get();

            $zonasDetalles[] = [
                'zona_id' => $relacion->zona_id,
                'zona_nombre' => $zonaInfo->nombre ?? 'Desconocido',
                'campanas_count' => $relacion->total,
                'campanas' => $campanasInfo->toArray()
            ];
        }

        $output[] = [
            'title' => '📋 Detalles de zonas con múltiples campañas',
            'content' => json_encode($zonasDetalles, JSON_PRETTY_PRINT)
        ];
    }
} catch (Exception $e) {
    $output[] = [
        'title' => '❌ Error al verificar relaciones',
        'content' => $e->getMessage()
    ];
}

// 4. Verificar archivos de imágenes
try {
    $imageFiles = Storage::disk('public')->files('campanas/imagenes');
    $imageCount = count($imageFiles);

    $output[] = [
        'title' => '🖼️ Archivos de imágenes',
        'content' => "Total archivos: {$imageCount}"
    ];

    // Mostrar algunas imágenes
    if ($imageCount > 0) {
        $sampleImages = array_slice($imageFiles, 0, 5);
        $imageSamples = [];

        foreach ($sampleImages as $image) {
            $size = Storage::disk('public')->size($image);
            $url = Storage::disk('public')->url($image);

            $imageSamples[] = [
                'path' => $image,
                'size' => round($size / 1024, 2) . ' KB',
                'url' => $url
            ];
        }

        $output[] = [
            'title' => '🖼️ Ejemplos de imágenes',
            'content' => json_encode($imageSamples, JSON_PRETTY_PRINT)
        ];
    }
} catch (Exception $e) {
    $output[] = [
        'title' => '❌ Error al verificar imágenes',
        'content' => $e->getMessage()
    ];
}

// Generar HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Carrusel</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
            background: #f7f8fa;
            color: #333;
        }

        h1 {
            color: #ff5e2c;
            border-bottom: 2px solid #ff5e2c;
            padding-bottom: 0.5rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            border-left: 4px solid #ff5e2c;
        }

        .card h2 {
            margin-top: 0;
            color: #333;
        }

        pre {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #e0e0e0;
        }

        .error-card {
            border-left-color: #e53935;
        }

        .error-card h2 {
            color: #e53935;
        }

        button {
            background: #ff5e2c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-right: 0.5rem;
        }

        button:hover {
            background: #e64a1c;
        }
    </style>
</head>
<body>
    <h1>Diagnóstico de Carrusel - Portal Cautivo</h1>
    <p>Esta herramienta analiza la configuración del carrusel de imágenes para ayudar a identificar y resolver problemas.</p>

    <div style="margin-bottom: 2rem;">
        <button onclick="window.location.reload()">Actualizar diagnóstico</button>
        <button onclick="testCarousel()">Probar carrusel</button>
        <button onclick="window.history.back()">Volver</button>
    </div>

    <?php foreach ($output as $item): ?>
        <div class="card <?= strpos($item['title'], '❌') !== false ? 'error-card' : '' ?>">
            <h2><?= htmlspecialchars($item['title']) ?></h2>
            <?php if (strpos($item['content'], '{') === 0 || strpos($item['content'], '[') === 0): ?>
                <pre><?= htmlspecialchars($item['content']) ?></pre>
            <?php else: ?>
                <p><?= htmlspecialchars($item['content']) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <!-- Carrusel de prueba -->
    <div id="test-carousel" style="display: none;">
        <div class="card">
            <h2>🧪 Prueba de carrusel</h2>
            <div class="swiper-container" style="width: 100%; height: 300px; position: relative;">
                <div class="swiper-wrapper">
                    <!-- Se llenará dinámicamente -->
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </div>

    <script src="<?= asset('js/swiper-local.js') ?>"></script>
    <script>
        function testCarousel() {
            // Mostrar el contenedor de prueba
            document.getElementById('test-carousel').style.display = 'block';

            // Preparar el carrusel de prueba
            const swiperWrapper = document.querySelector('.swiper-wrapper');
            swiperWrapper.innerHTML = '';

            // Crear slides de prueba
            const testImages = [
                '/storage/campanas/imagenes/default.jpg'
            ];

            // Intentar añadir imágenes reales si están disponibles
            <?php
            try {
                $jsImageArray = [];
                $imageFiles = Storage::disk('public')->files('campanas/imagenes');
                foreach (array_slice($imageFiles, 0, 3) as $image) {
                    $jsImageArray[] = Storage::url($image);
                }
                if (!empty($jsImageArray)) {
                    echo 'const realImages = ' . json_encode($jsImageArray) . ';';
                    echo 'if (realImages.length > 0) { testImages.length = 0; testImages.push(...realImages); }';
                }
            } catch (Exception $e) {
                // Ignorar errores
            }
            ?>

            // Crear slides
            testImages.forEach((src, index) => {
                const slide = document.createElement('div');
                slide.className = 'swiper-slide';
                slide.innerHTML = `<img src="${src}" alt="Test Image ${index + 1}" style="width: 100%; height: 100%; object-fit: contain;">`;
                swiperWrapper.appendChild(slide);
            });

            // Inicializar Swiper
            console.log('Inicializando carrusel de prueba con', testImages.length, 'imágenes');
            const swiper = new SwiperLocal('.swiper-container', {
                loop: testImages.length > 1,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false
                },
                pagination: true,
                allowTouchMove: true,
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                }
            });

            // Añadir botón para añadir más imágenes de prueba
            if (!document.getElementById('add-slide-btn')) {
                const controlsDiv = document.createElement('div');
                controlsDiv.style.marginTop = '1rem';

                const addBtn = document.createElement('button');
                addBtn.id = 'add-slide-btn';
                addBtn.textContent = 'Añadir slide';
                addBtn.onclick = function() {
                    const newIndex = swiperWrapper.children.length;
                    const slide = document.createElement('div');
                    slide.className = 'swiper-slide';
                    slide.innerHTML = `<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: hsl(${newIndex * 60}, 80%, 80%);">
                        <h2>Slide de prueba ${newIndex + 1}</h2>
                    </div>`;
                    swiperWrapper.appendChild(slide);
                    swiper.update();
                    console.log('Slide añadido, total:', swiperWrapper.children.length);
                };

                const startBtn = document.createElement('button');
                startBtn.textContent = 'Iniciar autoplay';
                startBtn.onclick = () => swiper.autoplay.start();

                const stopBtn = document.createElement('button');
                stopBtn.textContent = 'Detener autoplay';
                stopBtn.onclick = () => swiper.autoplay.stop();

                controlsDiv.appendChild(addBtn);
                controlsDiv.appendChild(startBtn);
                controlsDiv.appendChild(stopBtn);

                document.querySelector('#test-carousel .card').appendChild(controlsDiv);
            }
        }
    </script>
</body>
</html>
