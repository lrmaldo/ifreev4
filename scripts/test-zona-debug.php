<?php
// Test simple para verificar que las rutas y componentes están funcionando

echo "<h1>Test de Zona Modal</h1>";

// Verificar que las rutas están definidas
echo "<h2>Verificando rutas...</h2>";
$routes = [
    '/admin/zonas',
    '/zonas'
];

foreach ($routes as $route) {
    echo "<p>Ruta: <a href='{$route}' target='_blank'>{$route}</a></p>";
}

echo "<h2>Scripts de debugging</h2>";
echo "<pre>";
echo "Para depurar en el navegador:";
echo "\n1. Abrir la consola del navegador (F12)";
echo "\n2. Navegar a /zonas";
echo "\n3. Intentar hacer click en 'Nueva Zona'";
echo "\n4. Revisar los mensajes de console.log()";
echo "\n5. Si no funciona, probar: window.openZonaModal()";
echo "\n6. O probar: window.openNewZona()";
echo "</pre>";

echo "<h2>Verificar Livewire</h2>";
echo "<pre>";
echo "En la consola del navegador, verificar:";
echo "\n- window.Livewire (debería existir)";
echo "\n- document.querySelectorAll('[wire:id]') (debería encontrar componentes)";
echo "\n- Livewire.dispatch('openZonaModal') (debería funcionar)";
echo "</pre>";
?>
