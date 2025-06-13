<?php
// Script para verificar la conectividad con la API de Telegram
// Ejecutar: php verificar-conectividad-telegram.php

echo "🔍 Verificando conectividad con la API de Telegram...\n\n";

// Probar conectividad con api.telegram.org
echo "📡 Probando conexión a api.telegram.org...\n";

$ch = curl_init("https://api.telegram.org/bot123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11/getMe");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
    echo "❌ Error de conectividad: ({$errno}) {$error}\n";
    
    if ($errno == 6) {
        echo "   No se pudo resolver el host api.telegram.org\n";
        echo "   Posible problema de DNS\n\n";
        
        // Intentar resolver el host manualmente
        echo "   Intentando resolver api.telegram.org manualmente...\n";
        $ip = gethostbyname('api.telegram.org');
        if ($ip != 'api.telegram.org') {
            echo "   ✅ Resuelto a: {$ip}\n";
        } else {
            echo "   ❌ No se pudo resolver\n";
        }
    } elseif ($errno == 7) {
        echo "   No se pudo conectar al host\n";
        echo "   Posible problema de firewall o proxy\n";
    } elseif ($errno == 28) {
        echo "   Tiempo de espera agotado\n";
        echo "   Posible problema de red o servidor no disponible\n";
    } elseif ($errno == 60 || $errno == 77) {
        echo "   Problema con el certificado SSL\n";
        echo "   Intente configurar correctamente los certificados o deshabilitar la verificación SSL\n";
    }
} else {
    echo "✅ Conexión establecida con api.telegram.org\n";
    echo "   Código HTTP: {$httpCode}\n";
    
    if ($httpCode == 404) {
        // Este es un comportamiento esperado ya que el token es inválido
        echo "   Respuesta 404 esperada ya que se usó un token inválido para la prueba\n";
    } elseif ($httpCode == 401) {
        echo "   Respuesta 401 esperada ya que se usó un token inválido para la prueba\n";
    } else {
        echo "   Respuesta inesperada: {$response}\n";
    }
}

echo "\n🔍 Verificando proxy y configuración de red...\n";

$curlVersion = curl_version();
echo "📋 Versión de cURL: " . $curlVersion['version'] . "\n";
echo "   Protocolos soportados: " . $curlVersion['protocols'] . "\n";

// Verificar configuración de proxy en PHP
echo "\n📋 Configuración de proxy en PHP:\n";
$httpProxy = getenv('HTTP_PROXY');
$httpsProxy = getenv('HTTPS_PROXY');
$noProxy = getenv('NO_PROXY');

if (empty($httpProxy) && empty($httpsProxy)) {
    echo "   No hay proxies configurados en las variables de entorno\n";
} else {
    echo "   HTTP_PROXY: " . ($httpProxy ?: 'no configurado') . "\n";
    echo "   HTTPS_PROXY: " . ($httpsProxy ?: 'no configurado') . "\n";
    echo "   NO_PROXY: " . ($noProxy ?: 'no configurado') . "\n";
}

echo "\n🔍 Verificando conexión con un token real...\n";
$botToken = '7873181208:AAFR3vuwPXbGchzfw1XFwTZjjrNRxeHDqzA'; // Token real

echo "📡 Probando conexión a api.telegram.org con token real...\n";

$ch = curl_init("https://api.telegram.org/bot{$botToken}/getMe");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno) {
    echo "❌ Error de conectividad: ({$errno}) {$error}\n";
} else {
    echo "✅ Conexión establecida con api.telegram.org usando token real\n";
    echo "   Código HTTP: {$httpCode}\n";
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['ok']) && $result['ok'] === true) {
            echo "   ✅ Bot verificado: " . $result['result']['username'] . "\n";
            echo "   ✅ ID del bot: " . $result['result']['id'] . "\n";
            echo "   ✅ ¿Es bot?: " . ($result['result']['is_bot'] ? 'Sí' : 'No') . "\n";
            
            // Verificar el estado del webhook
            echo "\n📡 Verificando el estado del webhook...\n";
            
            $ch = curl_init("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                $webhookInfo = json_decode($response, true);
                
                if (isset($webhookInfo['ok']) && $webhookInfo['ok'] === true) {
                    $info = $webhookInfo['result'];
                    
                    echo "   URL del webhook: " . ($info['url'] ?? 'No configurada') . "\n";
                    echo "   Actualizaciones pendientes: " . ($info['pending_update_count'] ?? 0) . "\n";
                    
                    if (isset($info['last_error_date']) && isset($info['last_error_message'])) {
                        $errorDate = date('Y-m-d H:i:s', $info['last_error_date']);
                        echo "   ⚠️ Último error: {$info['last_error_message']} ({$errorDate})\n";
                    } else {
                        echo "   ✅ Sin errores reportados\n";
                    }
                    
                    if (isset($info['url']) && !empty($info['url'])) {
                        echo "\n📡 Intentando eliminar el webhook para realizar pruebas...\n";
                        
                        $ch = curl_init("https://api.telegram.org/bot{$botToken}/deleteWebhook");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode == 200) {
                            $result = json_decode($response, true);
                            
                            if (isset($result['ok']) && $result['ok'] === true) {
                                echo "   ✅ Webhook eliminado correctamente\n";
                                
                                echo "\n📡 Intentando configurar el webhook nuevamente...\n";
                                $webhookUrl = 'https://v3.i-free.com.mx/telegraph/' . $botToken . '/webhook';
                                echo "   URL: {$webhookUrl}\n";
                                
                                $ch = curl_init("https://api.telegram.org/bot{$botToken}/setWebhook");
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, [
                                    'url' => $webhookUrl,
                                    'max_connections' => 40
                                ]);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                                $response = curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);
                                
                                if ($httpCode == 200) {
                                    $result = json_decode($response, true);
                                    
                                    if (isset($result['ok']) && $result['ok'] === true) {
                                        echo "   ✅ Webhook configurado correctamente\n";
                                        echo "   Respuesta: " . $result['description'] . "\n";
                                    } else {
                                        echo "   ❌ Error al configurar el webhook: " . json_encode($result) . "\n";
                                    }
                                } else {
                                    echo "   ❌ Error HTTP {$httpCode} al configurar el webhook\n";
                                    echo "      Respuesta: {$response}\n";
                                }
                            } else {
                                echo "   ❌ Error al eliminar el webhook: " . json_encode($result) . "\n";
                            }
                        } else {
                            echo "   ❌ Error HTTP {$httpCode} al eliminar el webhook\n";
                            echo "      Respuesta: {$response}\n";
                        }
                    }
                }
            }
        } else {
            echo "   ❌ Error de verificación: " . json_encode($result) . "\n";
        }
    } else {
        echo "   ❌ Error HTTP {$httpCode}\n";
        echo "      Respuesta: {$response}\n";
    }
}

echo "\n✅ Verificación completa.\n";
