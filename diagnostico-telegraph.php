<?php
// Diagnóstico específico para problemas con Telegraph
// Este script verifica en detalle la configuración y funcionamiento de Telegraph
// Ejecutar: php diagnostico-telegraph.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔬 Iniciando diagnóstico de Telegraph...\n\n";

// 1. Verificar la configuración de Telegraph
echo "📋 Configuración de Telegraph:\n";
try {
    $config = config('telegraph');
    echo "  ✅ Archivo de configuración cargado correctamente\n";
    echo "  - URL API: " . $config['telegram_api_url'] . "\n";
    echo "  - Parse mode: " . $config['default_parse_mode'] . "\n";
    echo "  - Modelo Bot: " . $config['models']['bot'] . "\n";
    echo "  - Modelo Chat: " . $config['models']['chat'] . "\n";
    echo "  - WebhookHandler: " . $config['webhook']['handler'] . "\n";
} catch (\Exception $e) {
    echo "  ❌ Error al cargar la configuración: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Verificar el registro del ServiceProvider
echo "📦 Verificando ServiceProviders:\n";
$providers = $app->getLoadedProviders();
$telegraphProviderName = 'DefStudio\Telegraph\TelegraphServiceProvider';

if (isset($providers[$telegraphProviderName])) {
    echo "  ✅ TelegraphServiceProvider está registrado\n";
} else {
    echo "  ❌ TelegraphServiceProvider NO está registrado\n";

    // Verificar si está en los providers de config/app.php
    $appProviders = config('app.providers', []);
    if (in_array($telegraphProviderName, $appProviders)) {
        echo "  ⚠️ El proveedor está en la configuración pero no está cargado\n";
    } else {
        echo "  ⚠️ El proveedor no está en la configuración de app.providers\n";
    }
}

echo "\n";

// 3. Verificar la disponibilidad del servicio Telegraph
echo "🔧 Verificando servicio Telegraph:\n";
try {
    $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
    echo "  ✅ Servicio Telegraph disponible correctamente\n";

    // Verificar el método bot() y si mantiene el estado
    echo "  🔍 Verificando comportamiento del método bot():\n";

    // Obtener el primer bot disponible
    $bot = \DefStudio\Telegraph\Models\TelegraphBot::first();

    if (!$bot) {
        echo "  ⚠️ No se encontró ningún bot para probar\n";
    } else {
        echo "  ℹ️ Usando bot: {$bot->name} (ID: {$bot->id})\n";

        // Probar configuración incorrecta (no guardar la instancia)
        echo "  🧪 PRUEBA 1 - Configuración incorrecta (no guardar instancia):\n";
        $telegraph1 = app(\DefStudio\Telegraph\Telegraph::class);
        $telegraph1->bot($bot);

        try {
            $response = $telegraph1->botInfo()->send();
            echo "    ✓ Funcionó a pesar de no guardar la instancia (comportamiento no esperado)\n";
        } catch (\Exception $e) {
            echo "    ✓ Error esperado: " . $e->getMessage() . "\n";
            echo "    ✓ Confirmado el problema: Es necesario guardar la instancia que devuelve bot()\n";
        }

        // Probar configuración correcta (guardar la instancia)
        echo "  🧪 PRUEBA 2 - Configuración correcta (guardar instancia):\n";
        $telegraph2 = app(\DefStudio\Telegraph\Telegraph::class);
        $telegraph2 = $telegraph2->bot($bot); // Guardar la instancia devuelta

        try {
            $response = $telegraph2->botInfo()->send();
            echo "    ✓ Funcionó correctamente al guardar la instancia\n";
            echo "    ✓ Resultado: " . json_encode(isset($response['result']) ? ['ok' => $response['ok'], 'result_sample' => true] : $response) . "\n";
        } catch (\Exception $e) {
            echo "    ❌ Error inesperado: " . $e->getMessage() . "\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error al obtener el servicio Telegraph: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Verificar integración del controlador con Telegraph
echo "🔄 Verificando integración del controlador:\n";
try {
    $controllerClass = config('telegraph.webhook.handler');
    echo "  ℹ️ Clase controlador configurada: {$controllerClass}\n";

    if (class_exists($controllerClass)) {
        echo "  ✅ La clase del controlador existe\n";

        // Verificar si extiende de WebhookHandler
        $reflectionClass = new ReflectionClass($controllerClass);
        if ($reflectionClass->isSubclassOf(\DefStudio\Telegraph\Handlers\WebhookHandler::class)) {
            echo "  ✅ El controlador extiende correctamente de WebhookHandler\n";
        } else {
            echo "  ❌ El controlador NO extiende de WebhookHandler\n";
        }

        // Verificar métodos clave
        $requiredMethods = [
            'handle' => 'public',
            'handleChatMessage' => 'public',
            'getChatName' => 'protected'
        ];

        foreach ($requiredMethods as $method => $visibility) {
            if ($reflectionClass->hasMethod($method)) {
                $methodReflection = $reflectionClass->getMethod($method);
                $actualVisibility = $methodReflection->isPublic() ? 'public' : ($methodReflection->isProtected() ? 'protected' : 'private');

                if ($actualVisibility === $visibility) {
                    echo "  ✅ Método {$method}: Visibilidad correcta ({$visibility})\n";
                } else {
                    echo "  ❌ Método {$method}: Visibilidad incorrecta (es {$actualVisibility}, debería ser {$visibility})\n";
                }

                // Verificar el patrón de uso de bot() en el código fuente
                $methodBody = file_get_contents($reflectionClass->getFileName());
                if (strpos($methodBody, '$telegraph->bot($this->bot);') !== false) {
                    echo "  ⚠️ Patrón incorrecto detectado en el código: \$telegraph->bot(\$this->bot);\n";
                    echo "     Corrección recomendada: \$telegraph = \$telegraph->bot(\$this->bot);\n";
                }

                // Verificar el uso incorrecto de $this->chat->html()
                if (strpos($methodBody, '$this->chat->html(') !== false) {
                    echo "  ⚠️ Patrón incorrecto detectado en el código: \$this->chat->html();\n";
                    echo "     Corrección recomendada: Usar \$telegraph->chat(\$this->chat->chat_id)->html();\n";
                }
            } else {
                echo "  ❌ No se encontró el método {$method}\n";
            }
        }
    } else {
        echo "  ❌ La clase del controlador NO existe\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error al verificar el controlador: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Comprobar si hay instancias de Telegraph en el container
echo "🧰 Verificando instancias en el contenedor:\n";
try {
    $instances = ['telegraph', 'telegraph.bot'];
    foreach ($instances as $instance) {
        if ($app->bound($instance)) {
            echo "  ✅ La instancia '{$instance}' está registrada en el contenedor\n";
            $value = $app->make($instance);
            echo "     Clase: " . get_class($value) . "\n";
        } else {
            echo "  ⚠️ La instancia '{$instance}' NO está registrada en el contenedor\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error al verificar instancias: " . $e->getMessage() . "\n";
}

echo "\n🏁 Diagnóstico completado.\n";

echo "\n📝 RECOMENDACIONES:\n";
echo "1. Siempre usar: \$telegraph = \$telegraph->bot(\$bot); para mantener el contexto\n";
echo "2. Verificar que todas las llamadas a bot() guarden el resultado\n";
echo "3. NUNCA usar \$this->chat->html() directamente, usar siempre el patrón completo con \$telegraph\n";
echo "4. Considerar usar app()->instance('telegraph.bot', \$bot) para registrar el bot globalmente\n";
