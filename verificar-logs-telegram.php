<?php
// Script para verificar los logs del servidor relacionados con los webhooks de Telegram
// Ejecutar: php verificar-logs-telegram.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Analizando logs de Laravel para problemas con Telegram...\n\n";

// Ruta del archivo de log
$logFile = storage_path('logs/laravel.log');

if (!file_exists($logFile)) {
    echo "❌ No se encontró el archivo de logs en {$logFile}\n";

    // Buscar otros archivos de log
    $otherLogFiles = glob(storage_path('logs/*.log'));
    if (!empty($otherLogFiles)) {
        echo "   Se encontraron otros archivos de log:\n";
        foreach ($otherLogFiles as $file) {
            echo "   - " . basename($file) . "\n";
        }

        // Usar el primer archivo encontrado
        $logFile = $otherLogFiles[0];
        echo "\n   Usando: " . basename($logFile) . "\n\n";
    } else {
        echo "   No se encontraron archivos de log en storage/logs/\n";
        exit(1);
    }
}

// Obtener el tamaño del archivo
$fileSize = filesize($logFile);
echo "📊 Tamaño del archivo de log: " . round($fileSize / 1024 / 1024, 2) . " MB\n\n";

// Si el archivo es muy grande, leer solo las últimas 1000 líneas
if ($fileSize > 5 * 1024 * 1024) { // 5MB
    echo "⚠️ El archivo de log es grande, leyendo solo las últimas 1000 líneas...\n\n";

    // Usar el comando tail en Linux
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $logContent = shell_exec("tail -n 1000 " . escapeshellarg($logFile));
    }
    // En Windows, leer el archivo y tomar las últimas 1000 líneas
    else {
        $file = new SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        $lines = new LimitIterator($file, max(0, $totalLines - 1000), 1000);
        $logContent = implode('', iterator_to_array($lines));
    }
} else {
    $logContent = file_get_contents($logFile);
}

// Buscar errores relacionados con Telegram
echo "🔍 Buscando errores relacionados con Telegram...\n";

$telegramErrors = [];
$webhookErrors = [];
$controllerErrors = [];
$apiErrors = [];
$otherErrors = [];

// Dividir el log en líneas
$lines = explode("\n", $logContent);

foreach ($lines as $line) {
    // Saltar líneas vacías
    if (empty(trim($line))) {
        continue;
    }

    // Buscar errores relacionados con Telegram
    if (stripos($line, 'telegram') !== false || stripos($line, 'telegraph') !== false || stripos($line, 'bot') !== false) {
        if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
            $telegramErrors[] = $line;

            // Clasificar el tipo de error
            if (stripos($line, 'webhook') !== false) {
                $webhookErrors[] = $line;
            } else if (stripos($line, 'controller') !== false || stripos($line, 'TelegramWebhook') !== false) {
                $controllerErrors[] = $line;
            } else if (stripos($line, 'api') !== false || stripos($line, 'api.telegram.org') !== false) {
                $apiErrors[] = $line;
            } else {
                $otherErrors[] = $line;
            }
        }
    }
}

// Mostrar resultados
if (empty($telegramErrors)) {
    echo "✅ No se encontraron errores relacionados con Telegram en los logs\n";
} else {
    echo "⚠️ Se encontraron " . count($telegramErrors) . " líneas con errores relacionados con Telegram\n\n";

    if (!empty($webhookErrors)) {
        echo "📌 Errores relacionados con webhooks (" . count($webhookErrors) . "):\n";
        foreach (array_slice($webhookErrors, -5) as $error) {
            echo "   " . trim($error) . "\n";
        }
        if (count($webhookErrors) > 5) {
            echo "   ... y " . (count($webhookErrors) - 5) . " más\n";
        }
        echo "\n";
    }

    if (!empty($controllerErrors)) {
        echo "📌 Errores relacionados con controladores (" . count($controllerErrors) . "):\n";
        foreach (array_slice($controllerErrors, -5) as $error) {
            echo "   " . trim($error) . "\n";
        }
        if (count($controllerErrors) > 5) {
            echo "   ... y " . (count($controllerErrors) - 5) . " más\n";
        }
        echo "\n";
    }

    if (!empty($apiErrors)) {
        echo "📌 Errores relacionados con la API de Telegram (" . count($apiErrors) . "):\n";
        foreach (array_slice($apiErrors, -5) as $error) {
            echo "   " . trim($error) . "\n";
        }
        if (count($apiErrors) > 5) {
            echo "   ... y " . (count($apiErrors) - 5) . " más\n";
        }
        echo "\n";
    }

    if (!empty($otherErrors)) {
        echo "📌 Otros errores relacionados con Telegram (" . count($otherErrors) . "):\n";
        foreach (array_slice($otherErrors, -5) as $error) {
            echo "   " . trim($error) . "\n";
        }
        if (count($otherErrors) > 5) {
            echo "   ... y " . (count($otherErrors) - 5) . " más\n";
        }
        echo "\n";
    }
}

// Buscar errores HTTP 500
echo "\n🔍 Buscando errores HTTP 500 recientes...\n";

$http500Errors = [];
foreach ($lines as $line) {
    if (stripos($line, '500') !== false && (stripos($line, 'error') !== false || stripos($line, 'exception') !== false)) {
        $http500Errors[] = $line;
    }
}

if (empty($http500Errors)) {
    echo "✅ No se encontraron errores HTTP 500 en los logs\n";
} else {
    echo "⚠️ Se encontraron " . count($http500Errors) . " líneas con errores HTTP 500\n\n";
    foreach (array_slice($http500Errors, -10) as $error) {
        echo "   " . trim($error) . "\n";
    }
    if (count($http500Errors) > 10) {
        echo "   ... y " . (count($http500Errors) - 10) . " más\n";
    }
}

echo "\n🔍 Verificando la configuración de rutas para el webhook de Telegram...\n";

try {
    // Comprobar las rutas definidas
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $telegramRoutes = [];
    $telegraphRoutes = [];

    foreach ($routes as $route) {
        if (strpos($route->uri(), 'telegram/webhook') !== false) {
            $telegramRoutes[] = [
                'uri' => $route->uri(),
                'method' => implode('|', $route->methods()),
                'handler' => $route->getActionName()
            ];
        }

        if (strpos($route->uri(), 'telegraph') !== false) {
            $telegraphRoutes[] = [
                'uri' => $route->uri(),
                'method' => implode('|', $route->methods()),
                'handler' => $route->getActionName()
            ];
        }
    }

    echo "📋 Rutas telegram/webhook encontradas: " . count($telegramRoutes) . "\n";
    foreach ($telegramRoutes as $route) {
        echo "   - [{$route['method']}] {$route['uri']} => {$route['handler']}\n";
    }

    echo "\n📋 Rutas telegraph encontradas: " . count($telegraphRoutes) . "\n";
    foreach ($telegraphRoutes as $route) {
        echo "   - [{$route['method']}] {$route['uri']} => {$route['handler']}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error al verificar rutas: " . $e->getMessage() . "\n";
}

echo "\n🏁 Análisis de logs completado.\n";
echo "\n📚 Recomendaciones:\n";
echo "1. Configure el webhook con la URL correcta usando: php configurar-telegram-webhook-curl.php\n";
echo "2. Asegúrese de que todas las rutas de webhook estén correctamente definidas\n";
echo "3. Verifique que el controlador del webhook extiende la clase correcta\n";
echo "4. Asegúrese de que no hay configuraciones duplicadas de webhook\n";
?>
