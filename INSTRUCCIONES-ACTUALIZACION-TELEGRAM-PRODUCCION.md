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

### 3. Error "No TelegraphBot defined for this request"

Se ha corregido un error relacionado con la configuración del bot en el método `bot()`:

```
No TelegraphBot defined for this request
```

Este error ocurre porque el método `bot()` devuelve una nueva instancia configurada, pero no estábamos guardando esta instancia, lo que hacía que se perdiera la configuración del bot.

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

> **NOTA IMPORTANTE**: En lugar de editar manualmente el archivo, puede ser más seguro reemplazarlo completamente con la versión actualizada. Si eliges esta opción, asegúrate de mantener cualquier configuración específica del entorno o personalización que pueda existir en el servidor de producción.

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

### 5.1 Actualizar el método `ayuda()`

Es crucial mejorar el método `ayuda()` para utilizar el patrón correcto de envío de mensajes:

```php
/**
 * Maneja el comando /ayuda
 */
public function ayuda(): void
{
    try {
        $telegramChat = TelegramChat::where('chat_id', $this->chat->chat_id)->first();
        $zonasAsociadas = $telegramChat ? $telegramChat->zonas->count() : 0;

        $message = "📚 <b>Ayuda del Bot I-Free</b>\n\n";
        
        // ... (contenido del mensaje)

        // Log para diagnóstico
        \Illuminate\Support\Facades\Log::info('Enviando mensaje de ayuda', [
            'chat_id' => $this->chat->chat_id,
            'mensaje' => $message
        ]);

        // REEMPLAZAR ESTA LÍNEA:
        // $response = $this->chat->html($message)->send();
        
        // POR ESTAS LÍNEAS:
        // Obtener el objeto Telegraph para asegurar que se usa la instancia correcta
        $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
        $telegraph->bot($this->bot); // Aseguramos que se use el bot correcto

        // Enviar el mensaje utilizando el cliente Telegraph
        $response = $telegraph->chat($this->chat->chat_id)
            ->html($message)
            ->send();

        // Log de respuesta para diagnóstico
        \Illuminate\Support\Facades\Log::info('Respuesta API Telegram (ayuda)', [
            'response' => $response,
            'chat_id' => $this->chat->chat_id,
            'bot_id' => $this->bot->id,
            'bot_name' => $this->bot->name
        ]);
    } catch (\Exception $e) {
        // Capturar cualquier error durante el envío con información detallada
        \Illuminate\Support\Facades\Log::error('Error enviando mensaje ayuda', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'chat_id' => $this->chat->chat_id ?? 'unknown',
            'bot_id' => $this->bot->id ?? 'unknown'
        ]);
    }
}
```

Este cambio asegura que el método `ayuda()` utilice el mismo patrón de envío de mensajes que los otros métodos, lo cual es crítico para la consistencia y correcto funcionamiento.

### 6. Corregir Cualquier Ruta de Prueba (Si Existe)

Si hay alguna ruta de prueba en `routes/web.php` que llame al método `handle` directamente, asegúrate de actualizarla o comentarla.

### 7. Corregir el uso del método bot() en Telegraph

Debes asegurarte de guardar la instancia devuelta por el método `bot()`. Busca todos los patrones como este:

```php
$telegraph = app(\DefStudio\Telegraph\Telegraph::class);
$telegraph->bot($this->bot); // INCORRECTO: No guarda la instancia retornada

$telegraph->chat($this->chat->chat_id)
    ->html($mensaje)
    ->send();
```

Y reemplázalos con:

```php
$telegraph = app(\DefStudio\Telegraph\Telegraph::class);
$telegraph = $telegraph->bot($this->bot); // CORRECTO: Guarda la instancia retornada

$telegraph->chat($this->chat->chat_id)
    ->html($mensaje)
    ->send();
```

Debes hacer esta corrección en:

- El método `start()`
- El método `zonas()` (hay dos ocurrencias)
- El método `registrar()`
- El método `ayuda()`
- El método `handleChatMessage()`

### 8. Corrección del problema "bot_id: null" en los logs

Se ha detectado que el webhook recibe correctamente los comandos pero en los logs aparece:

```
"bot_id":null,"bot_name":null
```

Este problema se debe a un conflicto entre las rutas definidas manualmente en `routes/web.php` y la ruta automática creada por Telegraph.

#### 8.1 Actualizar rutas de webhook

Abre el archivo `routes/web.php` y comenta la ruta manual del webhook:

```php
// Rutas para el webhook de Telegram
// Nota: La ruta principal del webhook es manejada por Telegraph::telegraph() más abajo en este archivo
// Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
//     ->name('telegram.webhook')
//     ->withoutMiddleware(['web', 'auth', 'verified'])
//     ->middleware(['throttle:100,1']);
```

Asegúrate de que se mantenga la siguiente parte al final del archivo:

```php
// Usamos la macro de Telegraph para registrar su ruta (si está disponible)
if (method_exists(\Illuminate\Support\Facades\Route::class, 'telegraph')) {
    \Illuminate\Support\Facades\Route::telegraph();
}
```

Esta corrección permite que Telegraph gestione correctamente la inyección de dependencias del bot, evitando el problema de `bot_id: null` en los logs.

#### 8.2 Verificar la configuración de los bots

Para verificar que los bots están correctamente configurados en la base de datos, ejecuta:

```bash
php check-telegraph-bots.php
```

Este script mostrará información útil sobre los bots registrados y los chats asociados, además de verificar la configuración general de Telegraph.

### 9. Limpiar la Caché de Laravel

```bash
php artisan optimize:clear
```

### 10. Verificar la Configuración del Webhook

```bash
php artisan telegram:test-webhook --verify
```

### 11. Resetear el Webhook (Si es Necesario)

```bash
php artisan telegram:test-webhook --reset
```

### 12. Verificar los Logs

```bash
tail -f storage/logs/laravel.log
```

### 13. Probar los Comandos

Envía los siguientes comandos al bot y verifica los logs para confirmar que se están procesando correctamente:

```bash
# Monitorear logs mientras pruebas
tail -f storage/logs/laravel.log | grep Telegram
```

1. `/start` - Debe mostrar el mensaje de bienvenida
2. `/zonas` - Debe listar las zonas disponibles  
3. `/ayuda` - Debe mostrar la ayuda detallada (verifica especialmente este comando ya que se actualizó)
4. Mensaje normal "hola" - Debe responder con un mensaje de ayuda

## Verificación de Despliegue

Después de aplicar los cambios, verifica que:

1. No hay errores en los logs del servidor.
2. El bot responde a comandos como `/start`, `/zonas`, etc.
3. Las notificaciones automáticas funcionan cuando se crean nuevas métricas de hotspot.

## Casos de prueba específicos

Para verificar que el bot funciona correctamente, puedes usar los scripts de diagnóstico creados:

### Probar envío de mensajes directo

```bash
php test-telegram-message.php
```

Este script probará el envío de mensajes utilizando tanto el paquete Telegraph como directamente con la API de Telegram.

### Probar simulación de webhook

```bash
php test-telegram-webhook.php
```

Este script te permitirá simular solicitudes de webhook para diferentes comandos y verificar cómo responde el controlador.

### Ejecutar diagnóstico completo

```bash
php diagnostico-telegram-respuestas.php
```

Este script realizará un diagnóstico completo de la configuración de Telegram, verificando bots, chats, y la capacidad de enviar mensajes.

## Solución de Problemas

Si después de aplicar los cambios siguen ocurriendo problemas:

### Diagnóstico General

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

### Problema: Comandos se procesan pero no hay respuesta

Si los logs muestran que los comandos se reciben correctamente pero el bot no envía respuestas:

1. Ejecuta el script de diagnóstico de respuestas:
   ```
   php diagnostico-telegram-respuestas.php
   ```

2. Verifica la conectividad desde el servidor hacia la API de Telegram:
   ```
   curl -v https://api.telegram.org/bot<TOKEN>/getMe
   ```

3. Comprueba los permisos del bot en Telegram (debe tener habilitado el envío de mensajes).

4. Verifica que no haya restricciones de firewall que bloqueen las solicitudes salientes a api.telegram.org.

5. Reinicia el servicio web:
   ```
   sudo systemctl restart apache2   # Si usas Apache
   # O
   sudo systemctl restart nginx php-fpm   # Si usas Nginx + PHP-FPM
   ```

### Problema: Error 500 en las solicitudes webhook

Si el diagnóstico muestra "Webhook respondió con error: HTTP 500":

1. Verifica los logs de PHP/Apache/Nginx para errores específicos:
   ```
   sudo tail -f /var/log/apache2/error.log   # Para Apache
   # O
   sudo tail -f /var/log/nginx/error.log     # Para Nginx
   ```

2. Comprueba los permisos de los archivos:
   ```
   sudo chown -R www-data:www-data /var/www/v3.ifree.com.mx/storage
   sudo chmod -R 755 /var/www/v3.ifree.com.mx/storage
   ```

3. Verifica la configuración de PHP para asegurarte de que hay suficiente memoria y tiempo de ejecución:
   ```
   grep -E 'memory_limit|max_execution_time' /etc/php/*/apache2/php.ini
   # O
   grep -E 'memory_limit|max_execution_time' /etc/php/*/fpm/php.ini
   ```
