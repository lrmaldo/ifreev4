# Instrucciones de Migración de Telegraph

Este documento proporciona instrucciones para migrar de una versión antigua de Telegraph a una más reciente, o para solucionar problemas de incompatibilidad entre versiones.

## Problema Detectado: Columna `webhook_url` faltante

En versiones más recientes de Telegraph, se utiliza una columna `webhook_url` en la tabla `telegraph_bots` para almacenar la URL del webhook. Sin embargo, en versiones anteriores esta columna no existe.

### Solución 1: Agregar la columna manualmente

Puedes añadir la columna `webhook_url` a la tabla `telegraph_bots` ejecutando la siguiente migración:

```php
// Crear archivo database/migrations/2025_06_12_000000_add_webhook_url_to_telegraph_bots_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookUrlToTelegraphBotsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('telegraph_bots', 'webhook_url')) {
            Schema::table('telegraph_bots', function (Blueprint $table) {
                $table->string('webhook_url')->nullable()->after('token');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('telegraph_bots', 'webhook_url')) {
            Schema::table('telegraph_bots', function (Blueprint $table) {
                $table->dropColumn('webhook_url');
            });
        }
    }
}
```

Luego ejecuta:

```bash
php artisan migrate
```

### Solución 2: Actualizar Telegraph

Si prefieres actualizar Telegraph a la versión más reciente:

```bash
composer require defstudio/telegraph:^1.40.0
php artisan vendor:publish --tag=telegraph-migrations
php artisan migrate
```

## Problemas con Webhooks en Producción

### Configuración del Webhook

Para que los webhooks funcionen correctamente en producción, asegúrate de:

1. Tener una URL públicamente accesible con SSL válido
2. Configurar correctamente los webhooks ejecutando:

```bash
php configurar-telegram-webhook.php
```

3. Verificar la configuración con:

```bash
php check-telegraph-bots.php
```

### Errores comunes y soluciones

#### 1. Error de SSL al configurar webhook

Si obtienes errores SSL, asegúrate de que:

- El certificado SSL es válido (no es autofirmado)
- La URL es accesible desde internet
- El servidor web permite solicitudes POST a la ruta del webhook

#### 2. Error "No TelegraphBot defined for this request"

Este error ocurre cuando:

- La ruta del webhook está duplicada en `routes/web.php`
- No se están usando los patrones correctos para enviar mensajes

Solución:
- Comenta las rutas manuales de webhook y usa solo `Route::telegraph()`
- Usa el patrón correcto: `$telegraph = $telegraph->bot($bot);` para guardar la instancia configurada

#### 3. Error "bot_id:null" en logs

Ocurre cuando hay conflictos en las rutas que manejan webhooks.

Solución:
- Comenta las rutas manuales `/telegram/webhook` en `routes/web.php`
- Usa únicamente `Route::telegraph()` para manejar los webhooks

## Verificación de Bots y Webhooks

Para verificar el estado completo de tus bots y webhooks, ejecuta:

```bash
php verificar-y-solucionar-webhook-telegram.php
```

Este script realizará un diagnóstico completo y sugerirá soluciones específicas para cada problema detectado.
