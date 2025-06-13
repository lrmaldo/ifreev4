<?php
// Script para diagnosticar problemas con las migraciones
// Ejecutar: php diagnostico-migraciones.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Iniciando diagnóstico de migraciones...\n\n";

// Listar todas las migraciones
echo "📋 Lista de archivos de migración:\n";
$migrationFiles = glob(database_path('migrations/*.php'));
usort($migrationFiles, function($a, $b) {
    return basename($a) <=> basename($b);
});

foreach ($migrationFiles as $file) {
    $filename = basename($file);
    echo "  - {$filename}\n";
}

echo "\n📊 Estado de las migraciones según la base de datos:\n";
try {
    $migrations = DB::table('migrations')->orderBy('batch')->get();

    if ($migrations->isEmpty()) {
        echo "  ❌ No hay migraciones registradas en la base de datos\n";
    } else {
        echo "  ✅ Se encontraron " . $migrations->count() . " migraciones registradas\n\n";

        echo "  | Migración | Batch |\n";
        echo "  |-----------|-------|\n";
        foreach ($migrations as $migration) {
            echo "  | {$migration->migration} | {$migration->batch} |\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error al acceder a la tabla 'migrations': " . $e->getMessage() . "\n";

    if (strpos($e->getMessage(), "Table 'migrations' doesn't exist") !== false) {
        echo "  ⚠️ La tabla 'migrations' no existe. Es posible que nunca se hayan ejecutado las migraciones.\n";
    }
}

// Verificar si todos los archivos de migración están aplicados
echo "\n🔄 Verificando archivos de migración pendientes:\n";
try {
    $appliedMigrations = DB::table('migrations')->pluck('migration')->toArray();
    $pendingMigrations = [];

    foreach ($migrationFiles as $file) {
        $filename = basename($file, '.php');
        if (!in_array($filename, $appliedMigrations)) {
            $pendingMigrations[] = $filename;
        }
    }

    if (empty($pendingMigrations)) {
        echo "  ✅ No hay migraciones pendientes\n";
    } else {
        echo "  ⚠️ Se encontraron " . count($pendingMigrations) . " migraciones pendientes:\n";
        foreach ($pendingMigrations as $pending) {
            echo "  - {$pending}\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error al verificar migraciones pendientes: " . $e->getMessage() . "\n";
}

// Verificar la estructura de las tablas Telegraph
echo "\n🔍 Verificando tablas Telegraph:\n";
$telegraphTables = ['telegraph_bots', 'telegraph_chats'];

foreach ($telegraphTables as $table) {
    echo "  📌 Tabla '{$table}':\n";

    try {
        if (!Schema::hasTable($table)) {
            echo "  ⚠️ La tabla '{$table}' no existe\n";
            continue;
        }

        echo "  ✅ La tabla '{$table}' existe\n";

        // Obtener las columnas de la tabla
        $columns = Schema::getColumnListing($table);

        echo "  📋 Columnas encontradas:\n";
        foreach ($columns as $column) {
            echo "  - {$column}\n";
        }

        // Verificar columna webhook_url específicamente
        if ($table === 'telegraph_bots') {
            if (in_array('webhook_url', $columns)) {
                echo "  ✅ La columna 'webhook_url' existe en la tabla 'telegraph_bots'\n";
            } else {
                echo "  ⚠️ La columna 'webhook_url' NO existe en la tabla 'telegraph_bots'\n";
                echo "  ℹ️ Esta columna es importante para guardar la URL del webhook configurado.\n";
                echo "  ℹ️ Puedes agregarla usando la migración: 2025_06_13_000000_add_webhook_url_to_telegraph_bots_table.php\n";
            }
        }
    } catch (\Exception $e) {
        echo "  ❌ Error al verificar la tabla '{$table}': " . $e->getMessage() . "\n";
    }
}

echo "\n📝 RECOMENDACIONES:\n";
echo "1. Si hay migraciones pendientes, ejecute: php artisan migrate\n";
echo "2. Para reiniciar todas las migraciones y semillas: php artisan migrate:fresh --seed\n";
echo "3. Si hay problemas con migraciones específicas, revise el código de esas migraciones\n";
echo "4. Para la columna 'webhook_url', asegúrese de que la migración 2025_06_13_000000_add_webhook_url_to_telegraph_bots_table.php se ejecute\n";

echo "\n🏁 Diagnóstico de migraciones completado.\n";
