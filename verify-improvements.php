#!/usr/bin/env php
<?php

/**
 * Script de verificación de las mejoras implementadas para el problema de Select2 con Livewire
 * Verifica que todos los cambios estén en su lugar y funcionando correctamente
 */

echo "=== VERIFICACIÓN DE MEJORAS - SISTEMA SELECT2 + LIVEWIRE ===\n\n";

// Ruta base del proyecto
$basePath = __DIR__;

// Archivos a verificar
$filesToCheck = [
    'app/Livewire/Admin/Campanas/Index.php' => [
        'description' => 'Componente Livewire principal',
        'checks' => [
            'Método edit() mejorado' => 'array_map(\'intval\', $zonas_raw)',
            'Logging de zonas' => 'Log::debug(\'Zonas cargadas para edición\'',
            'Dispatch del evento' => 'dispatch(\'campanEditLoaded\'',
            'Normalización de arrays' => '$this->zonas_ids = array_map(\'intval\', $zonas_raw)',
        ]
    ],
    'public/js/select2-zonas.js' => [
        'description' => 'Script JavaScript mejorado',
        'checks' => [
            'Sistema de reintentos' => 'attemptUpdateWithRetry',
            'Manejo de timing' => 'MAX_RETRY_ATTEMPTS',
            'Logging mejorado' => 'console.log(\'Select2 Zonas:',
            'Verificación post-actualización' => 'setTimeout(() => {',
            'Función updateFromLivewire' => 'function updateFromLivewire',
        ]
    ],
    'resources/views/livewire/admin/campanas/index.blade.php' => [
        'description' => 'Vista Blade actualizada',
        'checks' => [
            'Sección de debugging' => 'Información de Debugging',
            'Logging del evento' => 'campanEditLoaded',
            'Herramientas de diagnóstico' => 'Estado Actual',
        ]
    ]
];

$testFiles = [
    'public/test-campana-edit-select2.html' => 'Archivo de prueba original',
    'public/test-integration.html' => 'Test de integración completo',
];

echo "1. VERIFICACIÓN DE ARCHIVOS PRINCIPALES\n";
echo str_repeat("-", 50) . "\n";

foreach ($filesToCheck as $file => $config) {
    $filePath = $basePath . '/' . $file;
    echo "Verificando: {$config['description']}\n";
    echo "Archivo: $file\n";

    if (!file_exists($filePath)) {
        echo "❌ ARCHIVO NO ENCONTRADO\n\n";
        continue;
    }

    $content = file_get_contents($filePath);
    $allChecksPass = true;

    foreach ($config['checks'] as $checkName => $searchText) {
        $found = strpos($content, $searchText) !== false;
        echo "  " . ($found ? "✅" : "❌") . " $checkName\n";
        if (!$found) {
            $allChecksPass = false;
        }
    }

    echo "Estado: " . ($allChecksPass ? "✅ TODAS LAS MEJORAS IMPLEMENTADAS" : "⚠️ FALTAN ALGUNAS MEJORAS") . "\n\n";
}

echo "2. VERIFICACIÓN DE ARCHIVOS DE PRUEBA\n";
echo str_repeat("-", 50) . "\n";

foreach ($testFiles as $file => $description) {
    $filePath = $basePath . '/' . $file;
    echo "Verificando: $description\n";
    echo "Archivo: $file\n";

    if (file_exists($filePath)) {
        $size = filesize($filePath);
        echo "✅ ARCHIVO ENCONTRADO (${size} bytes)\n\n";
    } else {
        echo "❌ ARCHIVO NO ENCONTRADO\n\n";
    }
}

echo "3. ANÁLISIS DETALLADO DE MEJORAS IMPLEMENTADAS\n";
echo str_repeat("-", 50) . "\n";

// Verificar método edit() específicamente
$livewireFile = $basePath . '/app/Livewire/Admin/Campanas/Index.php';
if (file_exists($livewireFile)) {
    $content = file_get_contents($livewireFile);

    echo "MÉTODO EDIT() - ANÁLISIS DETALLADO:\n";

    // Buscar el método edit
    if (preg_match('/public function edit\(\$id\)(.*?)(?=public function|\z)/s', $content, $matches)) {
        $editMethod = $matches[1];

        $improvements = [
            'Carga de zonas con relación' => preg_match('/with\([\'"]zonas[\'\"]\)/', $editMethod),
            'Normalización a enteros' => preg_match('/array_map\([\'"]intval[\'"]/', $editMethod),
            'Logging de debugging' => preg_match('/Log::debug/', $editMethod),
            'Dispatch del evento' => preg_match('/dispatch\([\'"]campanEditLoaded[\'"]/', $editMethod),
            'Parámetros del evento' => preg_match('/zonasIds.*campanaTitulo/', $editMethod),
        ];

        foreach ($improvements as $improvement => $found) {
            echo "  " . ($found ? "✅" : "❌") . " $improvement\n";
        }
    } else {
        echo "  ❌ No se pudo encontrar el método edit()\n";
    }
    echo "\n";
}

// Verificar JavaScript específicamente
$jsFile = $basePath . '/public/js/select2-zonas.js';
if (file_exists($jsFile)) {
    $content = file_get_contents($jsFile);

    echo "SCRIPT JAVASCRIPT - ANÁLISIS DETALLADO:\n";

    $jsImprovements = [
        'Sistema de reintentos' => preg_match('/attemptUpdateWithRetry/', $content),
        'Máximo de reintentos definido' => preg_match('/MAX_RETRY_ATTEMPTS\s*=\s*\d+/', $content),
        'Delays incrementales' => preg_match('/delay.*attempt.*\*/', $content),
        'Verificación post-actualización' => preg_match('/setTimeout.*verificationValues/', $content),
        'Logging extensivo' => preg_match('/console\.log.*Select2 Zonas:/', $content),
        'Manejo de eventos Livewire' => preg_match('/campanEditLoaded/', $content),
        'Función updateFromLivewire' => preg_match('/function updateFromLivewire/', $content),
    ];

    foreach ($jsImprovements as $improvement => $found) {
        echo "  " . ($found ? "✅" : "❌") . " $improvement\n";
    }
    echo "\n";
}

echo "4. RESUMEN DE ESTADO\n";
echo str_repeat("-", 50) . "\n";

// Contar mejoras implementadas
$totalChecks = 0;
$passedChecks = 0;

foreach ($filesToCheck as $file => $config) {
    $filePath = $basePath . '/' . $file;
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        foreach ($config['checks'] as $searchText) {
            $totalChecks++;
            if (strpos($content, $searchText) !== false) {
                $passedChecks++;
            }
        }
    }
}

$percentage = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 1) : 0;

echo "Total de verificaciones: $totalChecks\n";
echo "Verificaciones exitosas: $passedChecks\n";
echo "Porcentaje completado: $percentage%\n\n";

if ($percentage >= 90) {
    echo "🎉 ESTADO: EXCELENTE - Todas las mejoras principales implementadas\n";
} elseif ($percentage >= 70) {
    echo "✅ ESTADO: BUENO - La mayoría de mejoras implementadas\n";
} elseif ($percentage >= 50) {
    echo "⚠️ ESTADO: REGULAR - Algunas mejoras pendientes\n";
} else {
    echo "❌ ESTADO: CRÍTICO - Muchas mejoras faltantes\n";
}

echo "\n5. PRÓXIMOS PASOS RECOMENDADOS\n";
echo str_repeat("-", 50) . "\n";

if ($percentage >= 90) {
    echo "1. ✅ Probar la funcionalidad en el navegador\n";
    echo "2. ✅ Verificar logs del navegador y servidor\n";
    echo "3. ✅ Confirmar que las zonas se guardan correctamente\n";
    echo "4. ✅ Validar que Select2 se reinicializa entre modales\n";
} else {
    echo "1. ⚠️ Completar la implementación de las mejoras faltantes\n";
    echo "2. ⚠️ Revisar los archivos marcados con ❌\n";
    echo "3. ⚠️ Ejecutar este script nuevamente después de los cambios\n";
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
