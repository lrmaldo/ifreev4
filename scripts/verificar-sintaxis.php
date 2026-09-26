<?php
// Script para verificar errores de sintaxis en los archivos PHP
// Ejecutar: php verificar-sintaxis.php

echo "🔍 Verificando sintaxis de archivos PHP importantes...\n\n";

$archivos = [
    dirname(__DIR__) . '/app/Http/Controllers/TelegramWebhookController.php',
    dirname(__DIR__) . '/diagnostico-respuestas-webhook-telegram.php',
    dirname(__DIR__) . '/enviar-mensaje-directo-telegram.php',
    dirname(__DIR__) . '/registrar-bot-telegram.php',
    dirname(__DIR__) . '/verificar-webhook-telegram.php'
];

foreach ($archivos as $archivo) {
    echo "📋 Verificando {$archivo}...\n";

    if (!file_exists($archivo)) {
        echo "  ❌ El archivo no existe\n\n";
        continue;
    }

    // Ejecutar PHP con la opción -l para verificar la sintaxis
    $output = [];
    $return_var = 0;
    exec('php -l "' . $archivo . '" 2>&1', $output, $return_var);

    if ($return_var === 0) {
        echo "  ✅ Sintaxis correcta\n\n";
    } else {
        echo "  ❌ Error de sintaxis encontrado:\n";
        foreach ($output as $line) {
            echo "     " . $line . "\n";
        }
        echo "\n";
    }
}

echo "🔍 Verificando configuración de Telegraph...\n";
// Intentar cargar configuración sin usar bootstrap de Laravel
$telegraphConfig = null;
try {
    if (file_exists(dirname(__DIR__) . '/config/telegraph.php')) {
        $telegraphConfig = include dirname(__DIR__) . '/config/telegraph.php';
        echo "  ✅ Archivo de configuración cargado correctamente\n";
        echo "  📋 URL webhook configurada: " . $telegraphConfig['webhook']['url'] . "\n";
        echo "  📋 Handler configurado: " . $telegraphConfig['webhook']['handler'] . "\n";
    } else {
        echo "  ❌ El archivo de configuración no existe\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error al cargar la configuración: " . $e->getMessage() . "\n";
}

echo "\n✅ Verificación completa\n";
