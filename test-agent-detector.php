<?php

require __DIR__ . '/vendor/autoload.php';

use Karmendra\LaravelAgentDetector\AgentDetector;

$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', // Chrome en Windows
    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1', // Safari en iPhone
    'Mozilla/5.0 (Linux; Android 11; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36', // Chrome en Samsung Galaxy S21 Ultra
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36', // Chrome en Mac
    'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1', // Safari en iPad
];

echo "=========================================\n";
echo "Test de detección de dispositivos\n";
echo "=========================================\n\n";

foreach ($userAgents as $index => $ua) {
    $agent = new AgentDetector($ua);

    echo "User Agent " . ($index + 1) . ":\n";
    echo $ua . "\n\n";

    echo "Dispositivo: " . ($agent->device() ?: 'Desconocido') . "\n";
    echo "Marca: " . ($agent->deviceBrand() ?: 'Desconocida') . "\n";
    echo "Modelo: " . ($agent->deviceModel() ?: 'Desconocido') . "\n";
    echo "Sistema Operativo: " . ($agent->platform() ?: 'Desconocido') . " " . ($agent->platformVersion() ?: '') . "\n";
    echo "Navegador: " . ($agent->browser() ?: 'Desconocido') . " " . ($agent->browserVersion() ?: '') . "\n";
    echo "Es móvil: " . ($agent->isMobile() ? 'Sí' : 'No') . "\n";
    echo "Es escritorio: " . ($agent->isDesktop() ? 'Sí' : 'No') . "\n";
    echo "Es táctil: " . ($agent->isTouchEnabled() ? 'Sí' : 'No') . "\n";

    echo "\n=========================================\n\n";
}
