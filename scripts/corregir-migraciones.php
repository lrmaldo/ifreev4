<?php
// Script para solucionar problemas comunes de migraciones
// Ejecutar: php corregir-migraciones.php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Iniciando corrección de problemas de migraciones...\n\n";

// Función para ejecutar comandos Artisan y capturar la salida
function runArtisanCommand($command) {
    echo "  📌 Ejecutando: php artisan {$command}\n";
    ob_start();
    $exitCode = Artisan::call($command);
    $output = ob_get_clean();
    echo rtrim($output) . "\n";
    if ($exitCode === 0) {
        echo "  ✅ Comando ejecutado correctamente\n";
    } else {
        echo "  ❌ Error al ejecutar el comando (código {$exitCode})\n";
    }
    return $exitCode === 0;
}

// Verificar si la tabla migrations existe
try {
    $migrationsExist = Schema::hasTable('migrations');
    if (!$migrationsExist) {
        echo "❌ La tabla 'migrations' no existe. Es posible que nunca se hayan ejecutado las migraciones.\n";
        echo "   Intentando inicializar la base de datos desde cero...\n\n";

        // Inicializar la base de datos
        runArtisanCommand('migrate:install');
    } else {
        echo "✅ La tabla 'migrations' existe\n";
    }
} catch (\Exception $e) {
    echo "❌ Error al verificar la tabla 'migrations': " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar y arreglar la estructura de las tablas de Telegraph
echo "\n🔍 Verificando tablas de Telegraph...\n";
$telegraphTablesExist = true;

// Verificar si las tablas existen
foreach (['telegraph_bots', 'telegraph_chats'] as $table) {
    if (!Schema::hasTable($table)) {
        echo "⚠️ La tabla '{$table}' no existe\n";
        $telegraphTablesExist = false;
    } else {
        echo "✅ La tabla '{$table}' existe\n";
    }
}

// Si las tablas no existen, publicar y ejecutar migraciones de Telegraph
if (!$telegraphTablesExist) {
    echo "\n📋 Las tablas de Telegraph no existen. Intentando crearlas...\n";

    // Publicar migraciones de Telegraph
    runArtisanCommand('vendor:publish --tag=telegraph-migrations');

    // Ejecutar migraciones
    runArtisanCommand('migrate');
}

// Verificar si el campo webhook_url existe en telegraph_bots
if (Schema::hasTable('telegraph_bots')) {
    echo "\n🔍 Verificando campo webhook_url en telegraph_bots...\n";

    $hasWebhookUrl = Schema::hasColumn('telegraph_bots', 'webhook_url');
    if (!$hasWebhookUrl) {
        echo "⚠️ El campo 'webhook_url' no existe en la tabla 'telegraph_bots'\n";

        // Verificar si la migración existe
        $webhookMigrationFile = database_path('migrations/2025_06_13_000000_add_webhook_url_to_telegraph_bots_table.php');
        if (!file_exists($webhookMigrationFile)) {
            echo "❌ No se encontró la migración para agregar 'webhook_url'\n";
            echo "   Creando una nueva migración...\n";

            // Crear la migración
            $migrationContent = '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookUrlToTelegraphBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Verificar si la tabla telegraph_bots existe
        if (Schema::hasTable(\'telegraph_bots\')) {
            // Verificar si la columna webhook_url NO existe antes de agregarla
            if (!Schema::hasColumn(\'telegraph_bots\', \'webhook_url\')) {
                Schema::table(\'telegraph_bots\', function (Blueprint $table) {
                    $table->string(\'webhook_url\')->nullable()->after(\'token\');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable(\'telegraph_bots\')) {
            if (Schema::hasColumn(\'telegraph_bots\', \'webhook_url\')) {
                Schema::table(\'telegraph_bots\', function (Blueprint $table) {
                    $table->dropColumn(\'webhook_url\');
                });
            }
        }
    }
}';

            file_put_contents($webhookMigrationFile, $migrationContent);
            echo "✅ Migración creada: " . basename($webhookMigrationFile) . "\n";
        }

        // Ejecutar la migración
        echo "📌 Ejecutando migración para agregar campo 'webhook_url'...\n";
        runArtisanCommand('migrate');
    } else {
        echo "✅ El campo 'webhook_url' existe en la tabla 'telegraph_bots'\n";
    }
}

// Verificar migraciones pendientes
echo "\n🔄 Verificando migraciones pendientes...\n";
ob_start();
$pendingMigrations = Artisan::call('migrate:status');
$migrateStatus = ob_get_clean();

if (strpos($migrateStatus, '| Pending |') !== false) {
    echo "⚠️ Hay migraciones pendientes:\n";
    echo $migrateStatus . "\n";

    echo "📌 Ejecutando migraciones pendientes...\n";
    runArtisanCommand('migrate');
} else {
    echo "✅ No hay migraciones pendientes\n";
}

// Verificar integridad de la migración
echo "\n🔍 Verificando integridad de la base de datos...\n";
try {
    // Contar las migraciones en la tabla migrations vs archivos reales
    $migrationFiles = glob(database_path('migrations/*.php'));
    $migrationRecords = DB::table('migrations')->count();

    echo "📊 Archivos de migración: " . count($migrationFiles) . "\n";
    echo "📊 Registros en tabla migrations: " . $migrationRecords . "\n";

    if (count($migrationFiles) > $migrationRecords) {
        echo "⚠️ Hay más archivos de migración que registros en la tabla migrations.\n";
        echo "   Es posible que haya migraciones no aplicadas.\n";
        echo "📌 Ejecutando comando para sincronizar estado de migraciones...\n";
        runArtisanCommand('migrate');
    }
} catch (\Exception $e) {
    echo "❌ Error al verificar integridad: " . $e->getMessage() . "\n";
}

echo "\n🏁 Proceso de corrección de migraciones completado.\n";
echo "\n📝 RECOMENDACIONES FINALES:\n";
echo "1. Ejecute 'php diagnostico-migraciones.php' para verificar el estado final\n";
echo "2. Si desea reiniciar completamente la base de datos: php artisan migrate:fresh --seed\n";
echo "3. Para configurar los webhooks de Telegram: php configurar-telegram-webhook.php\n";
echo "\n";
