<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "=== CREACIÓN DE DATOS DE PRUEBA ===\n";

try {
    // Obtener el usuario admin
    $admin = User::where('email', 'admin@ifree.com')->first();

    if (!$admin) {
        echo "❌ Usuario admin no encontrado\n";
        exit(1);
    }

    echo "✅ Usuario admin encontrado: {$admin->name}\n";

    // Crear una zona de prueba
    $zona = DB::table('zonas')->insertGetId([
        'nombre' => 'Zona de Prueba MySQL',
        'user_id' => $admin->id,
        'segundos' => 30,
        'tipo_registro' => 'formulario',
        'login_sin_registro' => true,
        'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
        'id_personalizado' => 'test-mysql-zone',
        'seleccion_campanas' => 'prioridad',
        'tiempo_visualizacion' => 15,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    echo "✅ Zona creada con ID: $zona\n";

    // Crear algunas respuestas de formulario de prueba
    $macAddresses = [
        '00:11:22:33:44:55',
        'AA:BB:CC:DD:EE:FF',
        '12:34:56:78:90:AB'
    ];

    foreach ($macAddresses as $i => $mac) {
        $responseId = DB::table('form_responses')->insertGetId([
            'zona_id' => $zona,
            'mac_address' => $mac,
            'nombre' => "Usuario Prueba " . ($i + 1),
            'email' => "usuario{$i}@ejemplo.com",
            'telefono' => "123456789{$i}",
            'created_at' => now()->subMinutes(rand(1, 60)),
            'updated_at' => now()->subMinutes(rand(1, 60))
        ]);

        echo "✅ Respuesta de formulario creada para MAC: $mac (ID: $responseId)\n";

        // Crear métricas para cada usuario
        DB::table('hotspot_metrics')->insert([
            'zona_id' => $zona,
            'mac_address' => $mac,
            'formulario_id' => $responseId,
            'dispositivo' => ['Android', 'iOS', 'Windows'][rand(0, 2)],
            'navegador' => ['Chrome', 'Firefox', 'Safari'][rand(0, 2)],
            'tipo_visual' => ['formulario', 'carrusel', 'portal_cautivo'][rand(0, 2)],
            'duracion_visual' => rand(10, 60),
            'clic_boton' => rand(0, 1),
            'veces_entradas' => rand(1, 5),
            'created_at' => now()->subMinutes(rand(1, 60)),
            'updated_at' => now()->subMinutes(rand(1, 60))
        ]);

        echo "✅ Métrica creada para MAC: $mac\n";
    }

    echo "\n=== RESUMEN ===\n";
    echo "Zonas: " . DB::table('zonas')->count() . "\n";
    echo "Respuestas de formulario: " . DB::table('form_responses')->count() . "\n";
    echo "Métricas: " . DB::table('hotspot_metrics')->count() . "\n";
    echo "\n✅ Datos de prueba creados exitosamente\n";
    echo "\nPuedes probar el portal en: http://localhost/hotspot/zona-login?zone={$zona}&mac=NEW-MAC-ADDRESS\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
