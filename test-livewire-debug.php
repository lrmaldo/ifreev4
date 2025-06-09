<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simular una petición al componente Livewire
echo "=== TEST LIVEWIRE COMPONENT ===\n";

$zona = \App\Models\Zona::find(1);
echo "Zona encontrada: " . ($zona ? "Sí - {$zona->nombre}" : "No") . "\n";

// Instanciar el componente Livewire
$component = new \App\Livewire\FormResponsesList();
$component->mount($zona);

// Llamar al método render
try {
    $view = $component->render();
    $data = $view->getData();

    echo "Vista renderizada correctamente\n";
    echo "Datos en la vista:\n";

    if(isset($data['respuestas'])) {
        $respuestas = $data['respuestas'];
        echo "- Respuestas count: " . $respuestas->count() . "\n";
        echo "- Respuestas total: " . $respuestas->total() . "\n";
        echo "- Current page: " . $respuestas->currentPage() . "\n";
        echo "- Per page: " . $respuestas->perPage() . "\n";
        echo "- Has pages: " . ($respuestas->hasPages() ? 'Sí' : 'No') . "\n";

        if($respuestas->count() > 0) {
            echo "- Primera respuesta ID: " . $respuestas->first()->id . "\n";
            echo "- Primera respuesta MAC: " . $respuestas->first()->mac_address . "\n";
        }
    } else {
        echo "- No hay variable 'respuestas' en los datos de la vista\n";
    }

} catch(Exception $e) {
    echo "Error al renderizar: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN TEST ===\n";
