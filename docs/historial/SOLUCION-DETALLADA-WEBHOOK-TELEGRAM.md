# Solución de Problemas con Webhook de Telegram

Este documento describe los pasos para diagnosticar y solucionar los problemas más comunes con el webhook de Telegram en la aplicación i-Free.

## 1. Problema actual

El bot de Telegram recibe comandos pero no envía respuestas. Las posibles causas pueden ser:

- URL del webhook incorrectamente configurada
- Problemas con la columna `webhook_url` en la base de datos
- Errores internos en el controlador del webhook
- Problemas de conexión o autorización con la API de Telegram

## 2. Herramientas de diagnóstico

Hemos creado varias herramientas para diagnosticar y solucionar los problemas:

- **verificar-webhook-telegram.php**: Comprueba el estado del webhook en Telegram
- **configurar-telegram-webhook-curl.php**: Configura el webhook usando curl (evita problemas con Telegraph)
- **verificar-logs-telegram.php**: Analiza los logs en busca de errores relacionados con Telegram
- **diagnostico-respuestas-webhook-telegram.php**: Analiza específicamente problemas con las respuestas del webhook
- **enviar-mensaje-directo-telegram.php**: Prueba el envío de mensajes directo a la API de Telegram (sin usar Telegraph)

## 3. Pasos para diagnóstico

### 3.1. Verificar la configuración del webhook

```bash
php verificar-webhook-telegram.php
```

Este script mostrará si el webhook está configurado correctamente y si hay errores reportados por Telegram.

### 3.2. Analizar los logs

```bash
php verificar-logs-telegram.php
```

Este script analizará los logs en busca de errores relacionados con Telegram y los clasificará.

### 3.3. Diagnosticar problemas con las respuestas del webhook

```bash
php diagnostico-respuestas-webhook-telegram.php
```

Este script realizará un diagnóstico detallado enfocado específicamente en las respuestas del webhook.

### 3.4. Probar envío directo de mensajes

```bash
php enviar-mensaje-directo-telegram.php
```

Este script permite enviar mensajes directamente a través de la API de Telegram sin usar Telegraph, lo que ayuda a identificar si el problema está en la conexión con Telegram o en la biblioteca Telegraph.

## 4. Soluciones comunes

### 4.1. Corregir URL del webhook

Si la URL del webhook no es correcta, ejecutar:

```bash
php configurar-telegram-webhook-curl.php
```

La URL correcta debe ser:
```
https://v3.i-free.com.mx/telegraph/TOKEN/webhook
```

NO debe ser:
```
https://v3.i-free.com.mx/telegram/webhook?TOKEN
```

### 4.2. Problemas con la base de datos

Si la columna `webhook_url` no existe en la tabla `telegraph_bots`, ejecutar:

```bash
php artisan migrate
```

O específicamente:

```bash
php artisan migrate --path=/database/migrations/2025_06_13_000000_add_webhook_url_to_telegraph_bots_table.php
```

### 4.3. Problemas con el controlador

Si hay errores en el controlador, verificar:

1. Que el método `handle` tenga la firma correcta
2. Que todos los comandos (start, zonas, etc.) manejen correctamente las excepciones
3. Que los logs estén configurados para capturar información detallada

## 5. Comprobaciones específicas

### 5.1. Verificar que el webhook está recibiendo solicitudes

Para verificar si el endpoint del webhook está recibiendo solicitudes, revisa los logs:

```
php artisan log:tail --grep=webhook
```

También puedes enviarle un mensaje directamente al bot y ver si aparecen registros en los logs.

### 5.2. Verificar que la respuesta se está enviando

El sistema ahora tiene logs detallados para diagnosticar cada paso del proceso de envío de respuestas:

1. Recepción del webhook
2. Procesamiento del comando
3. Preparación del mensaje
4. Envío a través de Telegraph
5. Respuesta de la API de Telegram

Si hay algún error en cualquiera de estos pasos, estará registrado en los logs.

## 6. Solución de errores específicos

### 6.1. Error "No TelegraphBot defined for this request"

Este error puede ocurrir por:

1. La URL del webhook no incluye correctamente el token del bot
2. El token en la URL no coincide con ningún bot en la base de datos
3. La ruta del webhook no está bien definida en la configuración

Solución: Ejecutar `configurar-telegram-webhook-curl.php` para corregir la URL del webhook.

### 6.2. HTTP 500 al recibir webhook

Este error puede deberse a:

1. Errores en el controlador del webhook
2. Problemas con la conexión a la base de datos
3. Falta de permisos o configuración incorrecta del servidor

Solución: Revisar los logs detallados para identificar el error específico y aplicar la corrección correspondiente.

## 7. Logging mejorado

El controlador ahora incluye logging detallado en varios puntos:

1. Al recibir el webhook
2. Durante el procesamiento del webhook
3. Al preparar el mensaje de respuesta
4. Al enviar la respuesta
5. Al recibir la respuesta de la API de Telegram

Esto permite identificar en qué paso exactamente está fallando el proceso.

## 8. Pasos de corrección recomendados

1. Ejecutar `php diagnostico-respuestas-webhook-telegram.php` para un diagnóstico completo
2. Corregir la URL del webhook usando `php configurar-telegram-webhook-curl.php`
3. Verificar que las migraciones estén aplicadas correctamente
4. Probar el envío directo de mensajes con `php enviar-mensaje-directo-telegram.php`
5. Revisar los logs para identificar cualquier error persistente

## 9. Verificación final

Una vez aplicadas las correcciones, enviar un mensaje al bot para verificar si responde correctamente.
