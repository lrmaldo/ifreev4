# Instrucciones para Actualizar el Bot de Telegram en Producción

Estas instrucciones detallan los pasos para migrar de la biblioteca `defstudio/telegraph` a `irazasyed/telegram-bot-sdk` en el servidor de producción.

## Motivo de la Migración

La migración se realiza para solucionar problemas de compatibilidad con Laravel 12 y PHP 8.2, específicamente:

### 1. Error en la firma del método `getChatName`

Se ha detectado un error fatal relacionado con la visibilidad y firma del método `getChatName` en el controlador `TelegramWebhookController`:

```
Access level to App\Http\Controllers\TelegramWebhookController::getChatName() must be protected (as in class DefStudio\Telegraph\Handlers\WebhookHandler) or weaker
```

### 2. Problemas de Compatibilidad con Laravel 12

La biblioteca `defstudio/telegraph` tiene problemas de compatibilidad con las nuevas versiones de Laravel, lo que podría causar fallos en actualizaciones futuras.

### 3. Mayor Estabilidad y Funcionalidad

La migración a `irazasyed/telegram-bot-sdk` proporciona una API más estable, documentada y con mayor cantidad de funcionalidades para la gestión del bot de Telegram.

## Pasos para Actualizar en Producción

### 1. Conectarse al Servidor de Producción

```bash
ssh usuario@v3.i-free.com.mx
```

### 2. Navegar al Directorio del Proyecto

```bash
cd /var/www/v3.ifree.com.mx
```

### 3. Hacer Backup del Proyecto

Antes de realizar cualquier cambio, es importante hacer un backup completo:

```bash
# Respaldar la base de datos
mysqldump -u [usuario] -p[contraseña] [nombre_base_datos] > respaldo_ifree_$(date +%Y%m%d).sql

# Respaldar archivos del proyecto
cp -r /var/www/v3.ifree.com.mx /var/www/v3.ifree.com.mx.bak_$(date +%Y%m%d)
```

### 4. Eliminar la Biblioteca Telegraph y Instalar Telegram Bot SDK

```bash
# Eliminar Telegraph
composer remove defstudio/telegraph

# Instalar Telegram Bot SDK
composer require irazasyed/telegram-bot-sdk
```

### 5. Publicar la Configuración del SDK

```bash
php artisan vendor:publish --provider="Telegram\Bot\Laravel\TelegramServiceProvider"
```

### 6. Configurar el Archivo .env

Actualiza las variables de entorno en el archivo `.env`:

```
# Token del bot de Telegram (mantener el mismo valor)
TELEGRAM_BOT_TOKEN=tu_token_actual

# Actualizar la URL del webhook
TELEGRAM_WEBHOOK_URL=https://v3.i-free.com.mx/telegram/webhook
```

### 7. Actualizar el Archivo de Configuración

Edita el archivo `config/telegram.php` para asegurarte de que tenga la configuración correcta:

```bash
nano config/telegram.php
```

Asegúrate de que contiene lo siguiente:

```php
'bots' => [
    'ifree' => [
        'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
        'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH', ''),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL', 'https://v3.i-free.com.mx/telegram/webhook'),
        'allowed_updates' => ['message', 'callback_query'],
    ],
],
```

### 8. Reemplazar el Controlador

```bash
# Hacer backup del controlador antiguo
mv app/Http/Controllers/TelegramWebhookController.php app/Http/Controllers/TelegramWebhookController.php.bak

# Crear nuevo controlador
nano app/Http/Controllers/TelegramController.php
```

Copia el contenido del nuevo controlador (disponible en el repositorio) al nuevo archivo.

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

        ### 9. Actualizar las Rutas

Edita el archivo `routes/web.php`:

```bash
nano routes/web.php
```

Reemplaza el código relacionado con Telegraph:

```php
// Eliminar o comentar esta sección si existe
// Usamos la macro de Telegraph para registrar su ruta (si está disponible)
if (method_exists(\Illuminate\Support\Facades\Route::class, 'telegraph')) {
    \Illuminate\Support\Facades\Route::telegraph();
}
```

Y agrega las nuevas rutas:

```php
// Rutas para Telegram (nueva implementación con Telegram Bot SDK)
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])
    ->name('telegram.webhook')
    ->withoutMiddleware(['web'])  // No requerimos CSRF para el webhook
    ->middleware(['throttle:60,1']); // Protección contra abusos

Route::post('/telegram/enviar-notificacion', [TelegramController::class, 'enviarNotificacion'])
    ->name('telegram.notificacion')
    ->middleware(['auth:sanctum']);
```

Asegúrate de que también se incluya el import correcto al inicio del archivo:

```php
use App\Http\Controllers\TelegramController;
```

### 10. Limpiar las Cachés

```bash
php artisan optimize:clear
```

### 11. Configurar el Webhook en Telegram

Ejecuta el script para configurar el webhook:

```bash
php configurar-telegram-webhook-sdk.php
```

### 12. Verificar la Instalación

#### 12.1 Comprobar las rutas

```bash
php artisan route:list | grep telegram
```

Deberías ver las rutas `/telegram/webhook` y `/telegram/enviar-notificacion` listadas.

#### 12.2 Probar el envío de mensajes

```bash
php test-telegram-bot-sdk.php 123456789 "Migración a Telegram Bot SDK completada exitosamente"
```

#### 12.3 Verificar los logs

```bash
tail -f storage/logs/laravel.log
```

## Solución de Problemas

### Error de SSL Certificate

Si encuentras errores relacionados con certificados SSL durante las pruebas:

```
cURL error 60: SSL certificate problem: self-signed certificate in certificate chain
```

Puedes solucionarlo temporalmente en un entorno de desarrollo modificando el archivo `configurar-telegram-webhook-sdk.php` para deshabilitar la verificación SSL:

```php
// Configurar Guzzle para ignorar verificación SSL (solo para desarrollo)
$httpClient = new \GuzzleHttp\Client(['verify' => false]);
$telegram->setHttpClientHandler(new \Telegram\Bot\HttpClients\GuzzleHttpClient($httpClient));
```

**IMPORTANTE**: Esta modificación NO debe aplicarse en el entorno de producción, ya que compromete la seguridad de las comunicaciones.
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

## Plan de Rollback

En caso de problemas durante la migración, sigue estos pasos para volver a la implementación anterior:

### 1. Restaurar el Código Original

```bash
# Restaurar el controlador original
cp app/Http/Controllers/TelegramWebhookController.php.bak app/Http/Controllers/TelegramWebhookController.php

# Eliminar el nuevo controlador
rm app/Http/Controllers/TelegramController.php
```

### 2. Restaurar las Dependencias

```bash
# Eliminar Telegram Bot SDK
composer remove irazasyed/telegram-bot-sdk

# Reinstalar Telegraph
composer require defstudio/telegraph
```

### 3. Restaurar Rutas

Edita `routes/web.php` y restaura el código original:

```php
// Usamos la macro de Telegraph para registrar su ruta (si está disponible)
if (method_exists(\Illuminate\Support\Facades\Route::class, 'telegraph')) {
    \Illuminate\Support\Facades\Route::telegraph();
}
```

### 4. Limpiar Caché

```bash
php artisan optimize:clear
```

### 5. Verificar Funcionamiento

```bash
# Enviar mensaje de prueba
php artisan telegraph:send "Prueba de restauración"
```

## Conclusión

Esta migración soluciona los problemas de compatibilidad con el bot de Telegram y proporciona una base más estable para futuras actualizaciones. Si tienes alguna duda o encuentras algún problema durante la implementación, contacta al equipo de desarrollo.

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
