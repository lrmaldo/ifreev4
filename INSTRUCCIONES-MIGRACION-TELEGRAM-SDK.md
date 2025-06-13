# Migración a Telegram Bot SDK

Este documento detalla el proceso de migración desde la biblioteca `defstudio/telegraph` a `irazasyed/telegram-bot-sdk` para manejar el bot de Telegram.

## Motivo del cambio

La biblioteca `defstudio/telegraph` estaba causando problemas de compatibilidad con Laravel 12 y PHP 8.2, específicamente un error con la firma del método `getChatName()`. Para evitar estos problemas, se ha decidido migrar a una biblioteca más establecida y compatible.

## Cambios realizados

1. **Eliminación de Telegraph**:
   - Se eliminó la biblioteca `defstudio/telegraph` con `composer remove defstudio/telegraph`
   - Se eliminó el controlador `TelegramWebhookController.php`
   - Se eliminó la configuración `config/telegraph.php`

2. **Instalación de Telegram Bot SDK**:
   - Se instaló la biblioteca `irazasyed/telegram-bot-sdk` con `composer require irazasyed/telegram-bot-sdk`
   - Se publicó la configuración con `php artisan vendor:publish --provider="Telegram\Bot\Laravel\TelegramServiceProvider"`

3. **Nuevo Controlador**:
   - Se creó el controlador `TelegramController.php` que utiliza la nueva biblioteca
   - Se implementaron todos los métodos necesarios para manejar los comandos y mensajes

4. **Configuración de rutas**:
   - Se actualizaron las rutas para usar el nuevo controlador
   - Nuevo endpoint para webhooks: `/telegram/webhook`
   - Endpoint protegido para enviar notificaciones: `/telegram/enviar-notificacion`
   - Las rutas se configuraron en `routes/web.php` para mayor compatibilidad

5. **Scripts de utilidad**:
   - `configurar-telegram-webhook-sdk.php`: Para configurar el webhook en Telegram
   - `test-telegram-bot-sdk.php`: Para probar el envío de mensajes

## Características implementadas

El nuevo controlador `TelegramController` incluye las siguientes funcionalidades:

1. **Manejo de webhooks** para procesar actualizaciones de Telegram
2. **Registro automático de chats** en la base de datos
3. **Comandos implementados**:
   - `/start` - Mensaje de bienvenida
   - `/zonas` - Muestra zonas disponibles con botones inline
   - `/registrar [zona_id]` - Asocia el chat con una zona
   - `/ayuda` - Muestra información detallada
4. **Manejo de mensajes normales** que no son comandos
5. **Procesamiento de botones inline** (callback queries)
6. **API para enviar notificaciones** a chats suscritos a una zona

## Cómo configurar

1. **Configurar el archivo .env**:
   ```
   TELEGRAM_BOT_TOKEN=123456789:ABCDEFGHIJKLMNOPQRSTUVWXYZ
   TELEGRAM_WEBHOOK_URL=https://v3.i-free.com.mx/telegram/webhook
   ```

2. **Configurar el webhook**:
   Ejecutar el script `php configurar-telegram-webhook-sdk.php` y seguir las instrucciones.

3. **Probar el envío de mensajes**:
   ```
   php test-telegram-bot-sdk.php <chat_id> "Mensaje de prueba"
   ```

## Modelo TelegramChat

Se mantiene el mismo modelo `TelegramChat` existente y su relación con el modelo `Zona`. No se requieren cambios en la estructura de la base de datos.

## Envío de notificaciones desde el código

Para enviar notificaciones a los chats suscritos a una zona desde cualquier parte del código:

```php
// Mediante el endpoint de API (autenticado)
$response = Http::withToken($token)->post('https://v3.i-free.com.mx/telegram/enviar-notificacion', [
    'zona_id' => 1,
    'titulo' => 'Título de la notificación',
    'mensaje' => 'Contenido de la notificación'
]);

// O usando directamente la clase Telegram\Bot\Api
$telegram = app(Telegram\Bot\Api::class);
$telegram->sendMessage([
    'chat_id' => $chatId,
    'text' => $mensaje,
    'parse_mode' => 'HTML'
]);
```

## Solución de problemas

Si surgen problemas con el webhook, verificar:

1. El token configurado en el archivo `.env`
2. La URL del webhook configurada correctamente (ahora es `/telegram/webhook`, no `/api/telegram/webhook`)
3. Los logs de Laravel en `storage/logs/laravel.log` para diagnóstico detallado
4. La conexión al servidor de Telegram con `php test-telegram-bot-sdk.php`
5. Si las rutas no aparecen al ejecutar `php artisan route:list`, limpiar la caché con `php artisan optimize:clear`

## Nota sobre pruebas

Para probar el bot, simplemente envía un mensaje o comando a través de Telegram. El bot debería responder adecuadamente según el tipo de mensaje recibido.
