<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VERIFICACIÓN DE TABLAS EN MYSQL ===\n";

try {
    // Verificar conexión
    $connection = DB::connection();
    echo "✅ Conexión a MySQL exitosa\n";
    echo "Driver: " . $connection->getDriverName() . "\n";
    echo "Base de datos: " . $connection->getDatabaseName() . "\n\n";

    // Listar tablas
    $tables = DB::select('SHOW TABLES');
    echo "📋 Tablas disponibles:\n";
    foreach ($tables as $table) {
        $tableName = array_values((array) $table)[0];
        echo "  - $tableName\n";
    }

    echo "\n=== VERIFICACIÓN DE ESTRUCTURA DE TABLAS ===\n";

    // Verificar tabla zonas
    if (Schema::hasTable('zonas')) {
        echo "✅ Tabla 'zonas' existe\n";
        $zonas = DB::table('zonas')->count();
        echo "  Registros: $zonas\n";

        // Mostrar estructura de tipo_autenticacion_mikrotik
        $columns = DB::select("SHOW COLUMNS FROM zonas LIKE 'tipo_autenticacion_mikrotik'");
        if (!empty($columns)) {
            echo "  Columna tipo_autenticacion_mikrotik: " . $columns[0]->Type . "\n";
        }
    }

    // Verificar tabla hotspot_metrics
    if (Schema::hasTable('hotspot_metrics')) {
        echo "✅ Tabla 'hotspot_metrics' existe\n";
        $metrics = DB::table('hotspot_metrics')->count();
        echo "  Registros: $metrics\n";

        // Mostrar estructura de tipo_visual
        $columns = DB::select("SHOW COLUMNS FROM hotspot_metrics LIKE 'tipo_visual'");
        if (!empty($columns)) {
            echo "  Columna tipo_visual: " . $columns[0]->Type . "\n";
        }
    }

    // Verificar tabla form_responses
    if (Schema::hasTable('form_responses')) {
        echo "✅ Tabla 'form_responses' existe\n";
        $responses = DB::table('form_responses')->count();
        echo "  Registros: $responses\n";
    }

    echo "\n✅ Verificación completada exitosamente\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
