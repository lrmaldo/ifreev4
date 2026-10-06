<?php
// Este script prueba la visualización de respuestas del formulario

// Obtener la primera zona activa
$zona = \App\Models\Zona::first();

if (!$zona) {
    die("No hay zonas disponibles para probar");
}

// Obtener respuestas de esta zona
$respuestas = \App\Models\FormResponse::where('zona_id', $zona->id)
    ->with(['zona', 'zona.campos', 'zona.campos.opciones'])
    ->get();

echo "<h1>Prueba de visualización de respuestas</h1>";
echo "<h2>Zona: {$zona->nombre}</h2>";

if ($respuestas->isEmpty()) {
    echo "<p>No hay respuestas registradas en esta zona.</p>";
    die;
}

foreach ($respuestas as $respuesta) {
    echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 15px;'>";
    echo "<h3>Respuesta ID: {$respuesta->id}</h3>";
    echo "<p>MAC: {$respuesta->mac_address}</p>";
    echo "<p>Fecha: {$respuesta->created_at->format('d/m/Y H:i')}</p>";
    
    echo "<h4>Respuestas JSON brutas:</h4>";
    echo "<pre>" . json_encode($respuesta->respuestas, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h4>Respuestas formateadas:</h4>";
    $formateadas = $respuesta->respuestas_formateadas;
    
    if (!empty($formateadas)) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        
        foreach ($formateadas as $campo) {
            echo "<tr>";
            echo "<td>{$campo['etiqueta']}</td>";
            echo "<td>" . ($campo['valor'] ?: 'Sin respuesta') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No hay respuestas formateadas disponibles</p>";
    }
    
    echo "</div>";
}
