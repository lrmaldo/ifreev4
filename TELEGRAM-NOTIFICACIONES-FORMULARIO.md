# Notificaciones Telegram para Zonas con Formulario

## Descripción de la funcionalidad

Este módulo amplía el sistema de notificaciones de Telegram para enviar mensajes específicos cuando se registran nuevas métricas en zonas que tienen el formulario activado. Las notificaciones incluyen detalles importantes sobre la métrica y los datos recogidos en el formulario.

## Características principales

- ✅ Envío automático de notificaciones cuando se registra una métrica en una zona con formulario
- ✅ Inclusión de datos del formulario en la notificación (nombre, correo, teléfono, etc.)
- ✅ Procesamiento asíncrono mediante colas de Laravel
- ✅ Manejo de errores y registro detallado de eventos
- ✅ Compatible con el sistema de chats y zonas existente

## Requisitos técnicos

- Token de bot de Telegram configurado en `config/telegram.php`
- Zonas con tipo de registro `formulario` y campos definidos
- Chats de Telegram asociados a las zonas y marcados como activos

## Formato de las notificaciones

Las notificaciones tienen el siguiente formato:

```
🆕 Nueva conexión en [Nombre de la Zona]

📱 Dispositivo: [Dispositivo]
🌐 Navegador: [Navegador]
⏱️ Fecha: [Fecha y hora]
🔄 Visitas: [Número de visitas]

📝 Datos del formulario:
- [Etiqueta 1]: [Valor 1]
- [Etiqueta 2]: [Valor 2]
- ...
```

## Cómo probar

Para probar el sistema de notificaciones, puede usar el script de prueba incluido:

```bash
php test-telegram-form-notifications.php
```

También puede especificar una zona particular:

```bash
php test-telegram-form-notifications.php --zona_id=1
```

## Cómo funciona

1. Cuando se crea una nueva métrica (`HotspotMetric`), se dispara el evento `HotspotMetricCreated`
2. El listener `SendTelegramFormMetricNotification` procesa el evento
3. El listener verifica si la zona de la métrica tiene tipo de registro `formulario` y campos definidos
4. Si cumple los requisitos, prepara un mensaje con los datos de la métrica y el formulario
5. Envía la notificación a todos los chats de Telegram asociados a esa zona y marcados como activos

## Solución de problemas

Si las notificaciones no se envían, verifique:

1. Que la zona tenga tipo de registro `formulario` y campos definidos
2. Que existan chats de Telegram asociados a la zona y marcados como activos
3. Que el token de Telegram esté correctamente configurado
4. Que el sistema de colas esté funcionando correctamente (si aplica)

Para más información, revise los logs en `storage/logs/laravel.log`.
