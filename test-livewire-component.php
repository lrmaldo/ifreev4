<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Probar el componente Livewire
$zona = \App\Models\Zona::find(1);
echo "Zona encontrada: " . ($zona ? $zona->nombre : 'No encontrada') . "\n";

$component = new \App\Livewire\FormResponsesList();
$component->mount($zona);

// Ejecutar render y obtener datos
$viewData = $component->render()->getData();
$respuestas = $viewData['respuestas'];

echo "Total de respuestas en componente: " . $respuestas->total() . "\n";
echo "Respuestas en página actual: " . $respuestas->count() . "\n";

if ($respuestas->count() > 0) {
    echo "\nPrimera respuesta:\n";
    $primera = $respuestas->first();
    echo "ID: " . $primera->id . "\n";
    echo "MAC: " . $primera->mac_address . "\n";
    echo "Fecha: " . $primera->created_at . "\n";
    echo "Respuestas formateadas: " . count($primera->respuestas_formateadas) . " campos\n";
}
