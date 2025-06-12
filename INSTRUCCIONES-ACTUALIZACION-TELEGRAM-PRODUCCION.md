# Instrucciones para Actualizar el Webhook de Telegram en Producción

Estas instrucciones detallan los pasos para aplicar la corrección del webhook de Telegram en el servidor de producción.

## Problemas Resueltos

### 1. Error en la firma del método `handle`

Se ha corregido un error fatal relacionado con la firma del método `handle` en el controlador `TelegramWebhookController`:

```
Declaration of App\Http\Controllers\TelegramWebhookController::handle(Illuminate\Http\Request $request) must be compatible with DefStudio\Telegraph\Handlers\WebhookHandler::handle(Illuminate\Http\Request $request, DefStudio\Telegraph\Models\TelegraphBot $bot): void
```

### 2. Error en la visibilidad de métodos

Se ha corregido un error relacionado con la visibilidad de varios métodos:

```
Access level to App\Http\Controllers\TelegramWebhookController::getChatName() must be protected (as in class DefStudio\Telegraph\Handlers\WebhookHandler) or weaker
```

## Pasos para Actualizar en Producción

### 1. Conectarse al Servidor de Producción

```bash
ssh usuario@v3.i-free.com.mx
```

### 2. Navegar al Directorio del Proyecto

```bash
cd /var/www/v3.ifree.com.mx
```

### 3. Hacer Backup del Controlador Actual

```bash
cp app/Http/Controllers/TelegramWebhookController.php app/Http/Controllers/TelegramWebhookController.php.bak
```

### 4. Actualizar el Código del Controlador

Edita el archivo con tu editor preferido (nano, vim, etc.):

```bash
nano app/Http/Controllers/TelegramWebhookController.php
```

#### 4.1 Corregir el método `handle`

Busca la definición del método `handle` y reemplázala por:

```php
/**
 * Maneja las solicitudes webhook entrantes
 * Este método recibe el webhook y delega a los métodos correspondientes
 *
 * @param Request $request
 * @param DefStudio\Telegraph\Models\TelegraphBot $bot
 * @return void
 */
public function handle(Request $request, \DefStudio\Telegraph\Models\TelegraphBot $bot): void
{
    // Registrar recepción del webhook para diagnóstico
    Log::info('Webhook recibido', [
        'content' => $request->getContent(),
        'headers' => $request->headers->all(),
        'bot_id' => $bot->id,
        'bot_name' => $bot->name,
    ]);

    // Si es una solicitud de diagnóstico especial, responder directamente
    if ($request->has('diagnostic') && $request->get('diagnostic') === 'true') {
        // No podemos retornar una respuesta directamente debido a la firma del método
        // Guardaremos un registro para diagnóstico
        Log::info('Solicitud de diagnóstico recibida', [
            'status' => 'ok',
            'message' => 'Webhook endpoint funcional',
            'timestamp' => now()->toIso8601String(),
            'handler' => get_class($this)
        ]);
        
        // Detener el procesamiento adicional pero sin retornar respuesta
        return;
    }

    if ($this->shouldDebug()) {
        $this->debugWebhook($request);
    }

    // Delegar al manejador base de Telegraph
    parent::handle($request, $bot);
}
```

#### 4.2 Corregir la visibilidad y parámetros de los métodos

Debes cambiar la visibilidad de varios métodos auxiliares de `private` a `protected` y ajustar sus parámetros:

1. Actualiza la firma completa del método `getChatName`:
```php
/**
 * Obtiene el nombre del chat
 * 
 * @param \DefStudio\Telegraph\DTO\Chat $chat
 * @return string
 */
protected function getChatName(\DefStudio\Telegraph\DTO\Chat $chat): string
```

2. Actualiza la firma completa del método `getChatType`:
```php
/**
 * Obtiene el tipo de chat
 * 
 * @param \DefStudio\Telegraph\DTO\Chat $chat
 * @return string
 */
protected function getChatType(\DefStudio\Telegraph\DTO\Chat $chat): string
```

3. Cambia de `private function registerChat()` a `protected function registerChat()`
4. Cambia de `private function shouldDebug()` a `protected function shouldDebug()`
5. Cambia de `private function debugWebhook()` a `protected function debugWebhook()`
```

### 5. Actualizar las llamadas a los métodos modificados

Debes actualizar todas las llamadas a los métodos `getChatName` y `getChatType` para que incluyan el parámetro `$this->chat`:

```php
// Buscar en registerChat() y cambiar:
$chatData = [
    'chat_id' => $this->chat->chat_id,
    'nombre' => $this->getChatName(),
    'tipo' => $this->getChatType(),
    'activo' => true
];

// Por:
$chatData = [
    'chat_id' => $this->chat->chat_id,
    'nombre' => $this->getChatName($this->chat),
    'tipo' => $this->getChatType($this->chat),
    'activo' => true
];
```

Busca también en el método `ayuda()` y actualiza cualquier otra llamada a estos métodos.

### 6. Corregir Cualquier Ruta de Prueba (Si Existe)

Si hay alguna ruta de prueba en `routes/web.php` que llame al método `handle` directamente, asegúrate de actualizarla o comentarla.

### 7. Limpiar la Caché de Laravel

```bash
php artisan optimize:clear
```

### 8. Verificar la Configuración del Webhook

```bash
php artisan telegram:test-webhook --verify
```

### 9. Resetear el Webhook (Si es Necesario)

```bash
php artisan telegram:test-webhook --reset
```

### 10. Verificar los Logs

```bash
tail -f storage/logs/laravel.log
```

### 11. Probar los Comandos

Envía un comando `/start` al bot y verifica los logs para confirmar que se está procesando correctamente.

## Verificación de Despliegue

Después de aplicar los cambios, verifica que:

1. No hay errores en los logs del servidor.
2. El bot responde a comandos como `/start`, `/zonas`, etc.
3. Las notificaciones automáticas funcionan cuando se crean nuevas métricas de hotspot.

## Solución de Problemas

Si después de aplicar los cambios siguen ocurriendo problemas:

1. Verifica la configuración del webhook en Telegram utilizando la API:
   ```
   curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo
   ```

2. Asegúrate de que la URL del webhook apunte a `https://v3.i-free.com.mx/telegram/webhook`

3. Revisa los logs del servidor en `storage/logs/laravel.log` para errores específicos.

4. Ejecuta el comando de diagnóstico completo:
   ```
   php artisan telegram:test-webhook --diagnose
   ```
