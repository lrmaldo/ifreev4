<?php
// Verificar sintaxis del controlador TelegramWebhookController.php
$controlador = __DIR__ . '/app/Http/Controllers/TelegramWebhookController.php';

echo "Verificando sintaxis de {$controlador}...\n";

$command = PHP_OS === 'WINNT' 
    ? "php -l \"{$controlador}\"" 
    : "php -l '{$controlador}'";

system($command);

echo "\nFin de la verificación.\n";
