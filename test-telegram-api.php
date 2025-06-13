<?php
// Test simple de conectividad con Telegram API

echo "Verificando conexión con Telegram API...\n";

$botToken = '7873181208:AAFR3vuwPXbGchzfw1XFwTZjjrNRxeHDqzA';
$url = "https://api.telegram.org/bot{$botToken}/getMe";

echo "URL: {$url}\n\n";

// Desactivar comprobación de SSL para entornos de desarrollo
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    echo "Error en la conexión: ({$errno}) {$error}\n";
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "Código HTTP: {$httpCode}\n";
    echo "Respuesta: {$response}\n";

    // Decodificar la respuesta
    $data = json_decode($response, true);
    if (is_array($data) && isset($data['ok'])) {
        if ($data['ok'] === true) {
            echo "\nConexión exitosa!\n";
            echo "Username del bot: " . $data['result']['username'] . "\n";
            echo "ID del bot: " . $data['result']['id'] . "\n";
        } else {
            echo "\nError en la respuesta: " . ($data['description'] ?? 'Sin descripción') . "\n";
        }
    }
}

curl_close($ch);

echo "\n--- Verificación de webhook ---\n";
$url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
echo "URL: {$url}\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    echo "Error en la conexión: ({$errno}) {$error}\n";
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "Código HTTP: {$httpCode}\n";
    echo "Respuesta: {$response}\n";

    // Decodificar la respuesta
    $data = json_decode($response, true);
    if (is_array($data) && isset($data['ok']) && $data['ok'] === true) {
        echo "\nURL actual del webhook: " . ($data['result']['url'] ?? 'No configurada') . "\n";
    }
}

curl_close($ch);
