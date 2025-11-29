# Guía de Uso - Bot de Telegram IFree

## 🚀 Cómo Funciona Ahora

### 1. **Primeros Pasos**

El bot se registra automáticamente cuando:
- Envías tu primer mensaje (comando) en el grupo
- Se crea un registro en la tabla `telegram_chats` con la información del grupo

### 2. **Comandos Disponibles**

#### En Chats Privados (DM)
```
/start           - Mensaje de bienvenida
/zonas           - Ver todas las zonas disponibles
/registrar [ID]  - Asociar el chat con una zona
/ayuda           - Mostrar ayuda detallada
```

#### En Grupos
```
/start@iFreeBotv3_bot           - Mensaje de bienvenida
/zonas@iFreeBotv3_bot           - Ver todas las zonas
/registrar@iFreeBotv3_bot [ID]  - Asociar el grupo con una zona
/ayuda@iFreeBotv3_bot           - Mostrar ayuda
```

**Nota:** En grupos es OBLIGATORIO mencionar al bot con `@iFreeBotv3_bot` para que responda

### 3. **Flujo Para Recibir Notificaciones**

1. **Registrar el grupo/chat:**
   - Envía `/zonas@iFreeBotv3_bot` (en grupo) o `/zonas` (en DM)
   - El bot se registrará automáticamente

2. **Seleccionar una zona:**
   - Usa `/registrar [ID]` reemplazando [ID] con el número de la zona
   - O usa los botones inline que aparecen al enviar `/zonas`

3. **Recibir notificaciones:**
   - Cuando haya nuevas conexiones en esa zona, el bot enviará notificaciones automáticas

## 🔧 Problemas Solucionados

### ✅ Comandos en Grupos
**Problema:** El bot no respondía a comandos como `/zonas@iFreeBotv3_bot`

**Solución:** Se agregó procesamiento de menciones en comandos

```php
// Antes (no funcionaba):
$command = '/zonas@iFreeBotv3_bot'  // No se reconocía

// Ahora (funciona):
$command = '/zonas'  // Se extrae correctamente la mención
```

### ✅ Notificaciones en Grupos
**Problema:** El grupo registrado no recibía notificaciones

**Solución:** Se corrigieron los filtros de zonas y se mejoró el envío

- Se eliminaron filtros por campo `activo` que no existe
- Se corrigió el scope de chats activos
- Se agregó mejor logging para diagnosticar problemas

## 📊 Diagrama de Base de Datos

```
telegram_chats (Grupos/Chats registrados)
├── chat_id: ID único de Telegram
├── nombre: Nombre del grupo o usuario
├── tipo: private|group|supergroup|channel
└── activo: 1|0

        ↓ (relación M:N)

telegram_chat_zona (Asociaciones)
├── telegram_chat_id → telegram_chats.id
└── zona_id → zonas.id

zonas (Áreas de cobertura)
├── id
├── nombre
├── tipo_registro: formulario|simple
└── ... otros campos
```

## 🐛 Diagnóstico

Si el bot no envía notificaciones, verifica:

1. **¿El grupo está registrado?**
   ```sql
   SELECT * FROM telegram_chats WHERE chat_id = '-5064303539';
   ```

2. **¿El grupo está asociado a una zona?**
   ```sql
   SELECT * FROM telegram_chat_zona 
   WHERE telegram_chat_id = (SELECT id FROM telegram_chats WHERE chat_id = '-5064303539');
   ```

3. **¿El grupo está activo?**
   ```sql
   SELECT activo FROM telegram_chats WHERE chat_id = '-5064303539';
   ```

4. **¿Se están creando métricas?**
   ```sql
   SELECT * FROM hotspot_metrics ORDER BY created_at DESC LIMIT 5;
   ```

## 📝 Próximas Acciones Recomendadas

1. **Prueba en el grupo:**
   - Envía `/zonas@iFreeBotv3_bot`
   - El bot debería responder con la lista de zonas

2. **Registra el grupo con una zona:**
   - Envía `/registrar 4` (por ejemplo)
   - El bot confirmará la asociación

3. **Verifica que reciba notificaciones:**
   - Genera una nueva métrica en esa zona
   - El grupo debería recibir el mensaje automáticamente

## 🔑 Archivos Modificados

- `app/Http/Controllers/TelegramController.php` - Nuevo método `cleanCommandText()`
- `app/Listeners/SendTelegramNotification.php` - Mejor procesamiento de notificaciones
- `app/Listeners/SendTelegramFormMetricNotification.php` - Mejor logging

