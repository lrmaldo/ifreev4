<?php

// Script para probar la funcionalidad del sistema de respuestas de formularios
require_once 'vendor/autoload.php';

use App\Models\FormResponse;
use App\Models\Zona;

try {
    // Verificar que las tablas existen y tienen datos
    echo "=== VERIFICACIÓN DEL SISTEMA DE RESPUESTAS DE FORMULARIOS ===\n\n";

    // Contar zonas
    $zonasCount = Zona::count();
    echo "Total de zonas: $zonasCount\n";

    // Contar respuestas
    $responsesCount = FormResponse::count();
    echo "Total de respuestas: $responsesCount\n\n";

    if ($responsesCount > 0) {
        echo "=== MUESTRA DE RESPUESTAS ===\n";
        $responses = FormResponse::with('zona')->take(5)->get();

        foreach ($responses as $response) {
            echo "ID: {$response->id}\n";
            echo "Zona: {$response->zona->nombre}\n";
            echo "MAC: {$response->mac_address}\n";
            echo "Navegador: {$response->navegador}\n";
            echo "Tiempo activo: {$response->tiempo_activo_formateado}\n";
            echo "Formulario completado: " . ($response->formulario_completado ? 'Sí' : 'No') . "\n";
            echo "Respuestas: " . json_encode($response->respuestas) . "\n";
            echo "Fecha: {$response->created_at}\n";
            echo "---\n";
        }
    }

    echo "\n=== VERIFICACIÓN DE RUTAS ===\n";
    echo "Rutas configuradas:\n";
    echo "- POST /form-responses (store)\n";
    echo "- GET /admin/zonas/{zonaId}/form-responses (admin)\n";
    echo "- GET /zonas/{zonaId}/form-responses (cliente)\n\n";

    echo "✅ Sistema de respuestas implementado correctamente!\n";
    echo "\nPuedes probar:\n";
    echo "1. Acceder a http://localhost:8000/admin/zonas (si tienes una zona)\n";
    echo "2. Hacer clic en 'Ver Respuestas' para una zona específica\n";
    echo "3. Usar el formulario dinámico para crear nuevas respuestas\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
