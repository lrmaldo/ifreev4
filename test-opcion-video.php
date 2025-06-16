<?php

// Ejecutar este script dentro del contexto de Laravel para probar
// si nuestra migración ha funcionado correctamente.

// Intentar crear una zona con 'video' como selección de campañas
$zona = new \App\Models\Zona();
$zona->nombre = 'Zona Test - Opción Video';
$zona->user_id = 1; // Asegúrate de que este ID existe
$zona->id_personalizado = 'test-video-' . time();
$zona->seleccion_campanas = 'video';  // Esto debería funcionar si la migración tuvo éxito
$zona->tiempo_visualizacion = 15;

if($zona->save()) {
    echo "¡Éxito! Zona creada con ID: " . $zona->id . "\n";
    echo "seleccion_campanas = " . $zona->seleccion_campanas . "\n";

    // Opcional: eliminar la zona de prueba
    $zona->delete();
    echo "Zona de prueba eliminada.\n";
} else {
    echo "No se pudo guardar la zona. Revisa los errores.\n";
}
