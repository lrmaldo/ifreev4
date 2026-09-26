# 📱 Módulo de Notificaciones Telegram - Laravel 12 + Livewire 3

## 🎯 Descripción

Este módulo permite enviar notificaciones automáticas a grupos de Telegram cuando se registra una nueva métrica en la tabla `hotspot_metrics`. Incluye un sistema completo de webhook para comandos interactivos del bot. Utiliza las librerías modernas `telegram-bot/api` y `defstudio/telegraph` compatibles con Laravel 12.

## 📦 Componentes Instalados

### 1. **Librerías Telegram**
- `telegram-bot/api` - Para notificaciones automáticas
- `defstudio/telegraph` - Para webhook y comandos interactivos

### 2. **Modelos**
- `TelegramChat` - Representa un grupo/chat de Telegram con campo tipo
- Relación muchos a muchos entre `Zona` y `TelegramChat`

### 3. **Migraciones**
- `create_telegram_chats_table` - Tabla para almacenar chats de Telegram
- `update_telegram_chats_table_add_tipo` - Agregar campo tipo (private, group, supergroup, channel)
- `create_telegram_chat_zona_table` - Tabla pivote para la relación muchos a muchos
- Telegraph migrations - Para el sistema de webhook

### 4. **Servicios y Controladores**
- `TelegramNotificationService` - Servicio principal para envío de notificaciones
- `TelegramWebhookController` - Controlador para manejar webhooks y comandos

### 5. **Eventos y Listeners**
- `HotspotMetricCreated` - Evento disparado al crear una nueva métrica
- `SendTelegramNotification` - Listener que procesa el envío (en cola)

### 6. **Componente Livewire**
- `TelegramChatManager` - Panel administrativo para gestionar chats de Telegram

### 7. **Comandos Artisan**
- `telegram:test` - Comando para probar las notificaciones
- `telegram:setup-webhook` - Configurar webhook del bot
- `telegram:create-bot` - Crear y configurar bot de Telegraph
- `telegram:test-webhook` - Probar webhook con mensaje

## 🤖 Comandos del Bot

### **Comandos Disponibles:**

1. **`/start`** - Mensaje de bienvenida y registro automático del chat
2. **`/zonas`** - Lista todas las zonas disponibles del sistema
3. **`/registrar [zona_id]`** - Asocia el chat actual con una zona específica
4. **`/ayuda`** - Muestra ayuda detallada y estado del chat

### **Funcionalidades Automáticas:**
- **Registro automático** de chats cuando envían mensajes
- **Detección de tipo** de chat (privado, grupo, supergrupo, canal)
- **Validaciones** para evitar duplicados y errores
- **Respuestas inteligentes** a mensajes no reconocidos

## ⚙️ Configuración

### 1. **Variables de Entorno**

Agregar en `.env`:
```env
TELEGRAM_BOT_TOKEN=tu_token_del_bot
TELEGRAM_BOT_ENABLED=true
```

### 2. **Crear Bot de Telegram**

1. Contacta a [@BotFather](https://t.me/botfather) en Telegram
2. Ejecuta `/newbot` y sigue las instrucciones
3. Guarda el token proporcionado en `TELEGRAM_BOT_TOKEN`

### 3. **Configurar Webhook**

```bash
# Método 1: Usando Telegraph (recomendado)
php artisan telegram:create-bot --name="I-Free Bot"

# Método 2: Configuración manual
php artisan telegram:setup-webhook --url=https://tudominio.com/telegram/webhook
```

### 4. **Obtener Chat ID**

**Opción 1: Automático (recomendado)**
- Añade el bot al grupo
- Envía `/start` en el grupo
- El chat se registra automáticamente

**Opción 2: Manual**
- Visita: `https://api.telegram.org/bot<TOKEN>/getUpdates`
- Busca el `chat.id` en la respuesta (será negativo para grupos)

## 🚀 Uso

### 1. **Panel Administrativo**

Accede al componente Livewire `TelegramChatManager` en tu aplicación:

```php
// En una ruta o vista
<livewire:telegram-chat-manager />
```

Desde aquí puedes:
- Ver chats registrados automáticamente por el webhook
- Editar información de chats existentes
- Asociar chats con zonas específicas manualmente
- Probar la conexión con el bot
- Gestionar el estado activo/inactivo de los chats

### 2. **Notificaciones Automáticas**

Las notificaciones se envían automáticamente cuando:
- Se crea un nuevo registro en `hotspot_metrics`
- La zona tiene chats de Telegram asociados y activos

### 3. **Uso del Bot por Usuarios**

**En grupos o chats privados:**

```
Usuario: /start
Bot: 🤖 ¡Bienvenido al Bot de I-Free!
     [Mensaje de bienvenida y comandos]
     ✅ Chat registrado correctamente

Usuario: /zonas
Bot: 📍 Zonas disponibles:
     🏷️ ID: 1
     📌 Nombre: Lobby Principal
     💡 Para asociar: /registrar 1

Usuario: /registrar 1
Bot: ✅ ¡Registro exitoso!
     Chat asociado con la zona: Lobby Principal
     🔔 Ahora recibirás notificaciones de esta zona.
```

### 4. **Envío Manual desde Código**

```php
use App\Services\TelegramNotificationService;

$telegramService = new TelegramNotificationService();
$telegramService->notifyNewHotspotMetric($hotspotMetric);
```

### 5. **Pruebas**

```bash
# Probar notificaciones automáticas
php artisan telegram:test --zona_id=1

# Probar webhook y comandos
php artisan telegram:test-webhook -123456789

# Configurar webhook
php artisan telegram:setup-webhook --url=https://tudominio.com/telegram/webhook
```

## 📋 Estructura del Mensaje

Las notificaciones incluyen:

- **Información de la zona**
- **Detalles del dispositivo** (MAC, navegador, SO)
- **Métricas de uso** (duración, clics, entradas)
- **Timestamp** de la conexión
- **Formato HTML** para mejor legibilidad

Ejemplo de mensaje:
```
🚨 Nueva Conexión Detectada

📍 Zona: Lobby Principal
🆔 ID Zona: 1

📱 Detalles del Dispositivo:
• MAC Address: 00:11:22:33:44:55
• Dispositivo: iPhone 15 Pro
• Navegador: Safari 17.0
• Sistema Operativo: iOS 17.1

📊 Métricas de Uso:
• Tipo Visual: completa
• Duración Visual: 120 segundos
• Clic en Botón: ✅ Sí
• Veces de Entrada: 1

🕒 Fecha: 12/06/2025 14:30:15
```

## 🔧 Funciones Avanzadas

### 1. **Sistema de Webhook**

- **Ruta protegida**: `/telegram/webhook` con throttling
- **Comandos interactivos** procesados automáticamente
- **Registro automático** de chats nuevos
- **Validaciones** y manejo de errores

### 2. **Detección Automática de Tipo**

El sistema detecta automáticamente:
- **💬 Privado** - Chats individuales
- **👥 Grupo** - Grupos normales
- **🏢 Supergrupo** - Supergrupos
- **📢 Canal** - Canales de difusión

### 3. **Colas de Trabajo**

El listener `SendTelegramNotification` implementa `ShouldQueue`, por lo que las notificaciones se procesan en background:

```bash
php artisan queue:work
```

### 4. **Manejo de Errores**

- Los errores se registran en los logs de Laravel
- Los jobs fallidos se pueden reintentar automáticamente
- El método `failed()` del listener registra fallos específicos

### 5. **Testing de Conexión**

```php
$telegramService = new TelegramNotificationService();

// Probar conexión
$success = $telegramService->testConnection($chatId);

// Obtener info del bot
$botInfo = $telegramService->getBotInfo();
```

## 🛠️ Mantenimiento

### **Logs importantes**

- `storage/logs/laravel.log` - Errores generales y registros de chat
- Queue logs - Para errores de procesamiento en cola

### **Comandos útiles**

```bash
# Ver estado de migraciones
php artisan migrate:status

# Limpiar caches
php artisan cache:clear
php artisan config:clear

# Reiniciar colas
php artisan queue:restart

# Ver trabajos fallidos
php artisan queue:failed

# Ver información del webhook
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

## 🔧 Resolución de Problemas

### **Webhook no recibe mensajes**

1. Verificar que la URL sea accesible públicamente
2. Comprobar que use HTTPS (requerido por Telegram)
3. Verificar configuración del webhook:
```bash
php artisan telegram:setup-webhook --url=https://tudominio.com/telegram/webhook
```

### **Comandos no funcionan**

1. Verificar que el controlador esté en la ruta correcta
2. Comprobar logs en `storage/logs/laravel.log`
3. Probar con comando de test:
```bash
php artisan telegram:test-webhook -123456789
```

### **Chat no se registra automáticamente**

1. Verificar que el bot tenga permisos en el grupo
2. Comprobar que el webhook esté configurado correctamente
3. Revisar logs para errores

## 🔒 Seguridad

- El token del bot debe mantenerse secreto
- Los Chat IDs son únicos por chat/grupo
- Solo los chats marcados como "activos" reciben notificaciones
- Las notificaciones se procesan de forma asíncrona por seguridad
- El webhook tiene protección contra rate limiting

## 📈 Escalabilidad

- **Colas**: Las notificaciones se procesan en background
- **Relaciones optimizadas**: Queries eficientes con `with()`
- **Validaciones**: Verificación de existencia antes de envíos
- **Configuración por zona**: Permite granularidad en las notificaciones
- **Registro automático**: Reduce la configuración manual

---

**Autor**: Módulo desarrollado para I-Free v3 con Laravel 12 + Livewire 3  
**Fecha**: Junio 2025  
**Librerías**: telegram-bot/api + defstudio/telegraph

## ⚙️ Configuración

### 1. **Variables de Entorno**

Agregar en `.env`:
```env
TELEGRAM_BOT_TOKEN=tu_token_del_bot
TELEGRAM_BOT_ENABLED=true
```

### 2. **Crear Bot de Telegram**

1. Contacta a [@BotFather](https://t.me/botfather) en Telegram
2. Ejecuta `/newbot` y sigue las instrucciones
3. Guarda el token proporcionado en `TELEGRAM_BOT_TOKEN`

### 3. **Obtener Chat ID**

Para obtener el Chat ID de un grupo:

1. Añade el bot al grupo de Telegram
2. Envía un mensaje en el grupo
3. Visita: `https://api.telegram.org/bot<TOKEN>/getUpdates`
4. Busca el `chat.id` en la respuesta (será negativo para grupos)

## 🚀 Uso

### 1. **Panel Administrativo**

Accede al componente Livewire `TelegramChatManager` en tu aplicación:

```php
// En una ruta o vista
<livewire:telegram-chat-manager />
```

Desde aquí puedes:
- Registrar nuevos chats de Telegram
- Asociar chats con zonas específicas
- Probar la conexión con el bot
- Gestionar el estado activo/inactivo de los chats

### 2. **Notificaciones Automáticas**

Las notificaciones se envían automáticamente cuando:
- Se crea un nuevo registro en `hotspot_metrics`
- La zona tiene chats de Telegram asociados y activos

### 3. **Envío Manual desde Código**

```php
use App\Services\TelegramNotificationService;

$telegramService = new TelegramNotificationService();
$telegramService->notifyNewHotspotMetric($hotspotMetric);
```

### 4. **Pruebas**

```bash
# Probar con la primera zona disponible
php artisan telegram:test

# Probar con una zona específica
php artisan telegram:test --zona_id=1
```

## 📋 Estructura del Mensaje

Las notificaciones incluyen:

- **Información de la zona**
- **Detalles del dispositivo** (MAC, navegador, SO)
- **Métricas de uso** (duración, clics, entradas)
- **Timestamp** de la conexión
- **Formato HTML** para mejor legibilidad

Ejemplo de mensaje:
```
🚨 Nueva Conexión Detectada

📍 Zona: Lobby Principal
🆔 ID Zona: 1

📱 Detalles del Dispositivo:
• MAC Address: 00:11:22:33:44:55
• Dispositivo: iPhone 15 Pro
• Navegador: Safari 17.0
• Sistema Operativo: iOS 17.1

📊 Métricas de Uso:
• Tipo Visual: completa
• Duración Visual: 120 segundos
• Clic en Botón: ✅ Sí
• Veces de Entrada: 1

🕒 Fecha: 12/06/2025 14:30:15
```

## 🔧 Funciones Avanzadas

### 1. **Colas de Trabajo**

El listener `SendTelegramNotification` implementa `ShouldQueue`, por lo que las notificaciones se procesan en background. Asegúrate de tener configuradas las colas:

```bash
php artisan queue:work
```

### 2. **Manejo de Errores**

- Los errores se registran en los logs de Laravel
- Los jobs fallidos se pueden reintentar automáticamente
- El método `failed()` del listener registra fallos específicos

### 3. **Testing de Conexión**

```php
$telegramService = new TelegramNotificationService();

// Probar conexión
$success = $telegramService->testConnection($chatId);

// Obtener info del bot
$botInfo = $telegramService->getBotInfo();
```

## 🛠️ Mantenimiento

### **Logs importantes**

- `storage/logs/laravel.log` - Errores generales
- Queue logs - Para errores de procesamiento en cola

### **Comandos útiles**

```bash
# Ver estado de migraciones
php artisan migrate:status

# Limpiar caches
php artisan cache:clear
php artisan config:clear

# Reiniciar colas
php artisan queue:restart

# Ver trabajos fallidos
php artisan queue:failed
```

## 🔒 Seguridad

- El token del bot debe mantenerse secreto
- Los Chat IDs son únicos por chat/grupo
- Solo los chats marcados como "activos" reciben notificaciones
- Las notificaciones se procesan de forma asíncrona por seguridad

## 📈 Escalabilidad

- **Colas**: Las notificaciones se procesan en background
- **Relaciones optimizadas**: Queries eficientes con `with()`
- **Validaciones**: Verificación de existencia antes de envíos
- **Configuración por zona**: Permite granularidad en las notificaciones

---

**Autor**: Módulo desarrollado para I-Free v3 con Laravel 12 + Livewire 3  
**Fecha**: Junio 2025
