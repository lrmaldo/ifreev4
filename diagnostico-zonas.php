<?php

require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIAGNÓSTICO DE ZONAS ===\n";

try {
    $totalZonas = App\Models\Zona::count();
    echo "Total de zonas en la base de datos: $totalZonas\n\n";

    // Verificar zona específica ID 10
    $zona10 = App\Models\Zona::find(10);
    if ($zona10) {
        echo "✅ Zona ID 10 ENCONTRADA:\n";
        echo "   - Nombre: {$zona10->nombre}\n";
        echo "   - Activa: " . ($zona10->activo ? 'Sí' : 'No') . "\n";
        echo "   - ID personalizado: " . ($zona10->id_personalizado ?? 'N/A') . "\n";
        echo "   - Creada: {$zona10->created_at}\n";
    } else {
        echo "❌ Zona ID 10 NO EXISTE\n";
    }

    // Mostrar todas las zonas disponibles
    echo "\n=== ZONAS DISPONIBLES ===\n";
    $zonas = App\Models\Zona::orderBy('id')->get();

    if ($zonas->count() > 0) {
        foreach ($zonas as $zona) {
            $estado = $zona->activo ? '✅' : '❌';
            $idPersonalizado = $zona->id_personalizado ? " (ID personalizado: {$zona->id_personalizado})" : "";
            echo "{$estado} ID: {$zona->id} - {$zona->nombre}{$idPersonalizado}\n";
        }
    } else {
        echo "No hay zonas registradas en la base de datos.\n";
    }

    // Verificar si hay zonas con ID personalizado "10"
    echo "\n=== VERIFICAR ID PERSONALIZADO ===\n";
    $zonaPorIdPersonalizado = App\Models\Zona::where('id_personalizado', '10')->first();
    if ($zonaPorIdPersonalizado) {
        echo "✅ Zona con ID personalizado '10' encontrada:\n";
        echo "   - ID real: {$zonaPorIdPersonalizado->id}\n";
        echo "   - Nombre: {$zonaPorIdPersonalizado->nombre}\n";
        echo "   - Activa: " . ($zonaPorIdPersonalizado->activo ? 'Sí' : 'No') . "\n";
    } else {
        echo "❌ No hay zona con ID personalizado '10'\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
