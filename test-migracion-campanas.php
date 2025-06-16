<?php

// Script para verificar la estructura de la tabla zonas
require __DIR__ . '/vendor/autoload.php';

// Cargar el framework Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obtener información del esquema
echo "Columnas en la tabla zonas:\n";
print_r(Schema::getColumnListing('zonas'));

echo "\n\nTipo de la columna seleccion_campanas:\n";
try {
    $type = DB::getSchemaBuilder()->getColumnType('zonas', 'seleccion_campanas');
    echo $type . "\n";
} catch (Exception $e) {
    echo "Error al obtener tipo: " . $e->getMessage() . "\n";
}

// Intentar guardar una zona con 'video' como seleccion_campanas
echo "\n\nIntentando guardar una zona con seleccion_campanas='video':\n";
try {
    $zona = new \App\Models\Zona();
    $zona->nombre = 'Zona de prueba migración';
    $zona->id_personalizado = 'test-migracion-' . time();
    $zona->user_id = 1; // Asegúrate que este ID existe
    $zona->tipo_registro = 'sin_registro';
    $zona->seleccion_campanas = 'video';  // ¡Aquí es donde probamos!
    $zona->tiempo_visualizacion = 15;

    if($zona->save()) {
        echo "¡Éxito! Se ha guardado la zona con seleccion_campanas='video'\n";
        echo "ID de la zona: " . $zona->id . "\n";

        // Verificar que se guardó correctamente
        $zonaGuardada = \App\Models\Zona::find($zona->id);
        echo "Valor guardado de seleccion_campanas: " . $zonaGuardada->seleccion_campanas . "\n";

        // Limpiar datos de prueba
        $zonaGuardada->delete();
        echo "Zona de prueba eliminada\n";
    } else {
        echo "No se pudo guardar la zona\n";
    }
} catch (Exception $e) {
    echo "Error al guardar zona: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
