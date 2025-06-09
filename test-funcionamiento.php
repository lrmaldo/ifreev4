<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h1>Test de Funcionamiento - Form Responses</h1>";

try {
    // Verificar datos
    $zona = \App\Models\Zona::find(1);
    $respuestas = \App\Models\FormResponse::where('zona_id', 1)->with(['zona', 'zona.campos'])->get();

    echo "<h2>✅ Zona 1 encontrada:</h2>";
    echo "<p><strong>Nombre:</strong> " . $zona->nombre . "</p>";
    echo "<p><strong>ID:</strong> " . $zona->id . "</p>";

    echo "<h2>✅ Respuestas encontradas: " . $respuestas->count() . "</h2>";

    if ($respuestas->count() > 0) {
        echo "<h3>📋 Ejemplo de respuesta:</h3>";
        $primera = $respuestas->first();
        echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>MAC:</strong> " . $primera->mac_address . "<br>";
        echo "<strong>Fecha:</strong> " . $primera->created_at->format('d/m/Y H:i') . "<br>";
        echo "<strong>Dispositivo:</strong> " . substr($primera->dispositivo, 0, 50) . "...<br>";
        echo "<strong>Completado:</strong> " . ($primera->formulario_completado ? 'Sí' : 'No') . "<br>";
        echo "<strong>Respuestas JSON:</strong><br>";
        echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>" .
             json_encode($primera->respuestas, JSON_PRETTY_PRINT) . "</pre>";

        $formateadas = $primera->respuestas_formateadas;
        echo "<strong>Respuestas formateadas:</strong> " . count($formateadas) . " campos<br>";
        if (count($formateadas) > 0) {
            echo "<ul>";
            foreach($formateadas as $campo) {
                echo "<li><strong>" . $campo['etiqueta'] . ":</strong> " . $campo['valor'] . "</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
    }

    echo "<h2>🔗 Enlaces para probar:</h2>";
    echo "<p><a href='http://127.0.0.1:8000/login' target='_blank'>→ Página de Login</a></p>";
    echo "<p><a href='http://127.0.0.1:8000/admin/zonas/1/form-responses' target='_blank'>→ Ver respuestas (requiere autenticación)</a></p>";

} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><hr><br>";
echo "<p><em>Generado el: " . date('Y-m-d H:i:s') . "</em></p>";
