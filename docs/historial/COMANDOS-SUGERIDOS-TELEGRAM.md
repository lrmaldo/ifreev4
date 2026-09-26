# 💡 Nuevos Comandos Sugeridos para el Bot de Telegram

## 📊 Comandos de Estadísticas

### 1. `/estadisticas` 
**Uso:** Ver estadísticas resumidas del día
```
/estadisticas[zona_id]
/estadisticas 1    (estadísticas de zona específica)
/estadisticas      (todas las zonas asociadas al chat)
```

**Respuesta:**
```
📊 ESTADÍSTICAS DE HOY - Zona Rotamundos

👥 Visitas: 145
📱 Dispositivos únicos: 89
✅ Formularios completados: 42 (29%)
⏱️ Duración promedio: 2m 30s
🔘 Clics en botones: 18
🔄 Usuarios recurrentes: 12

📈 Tendencia: +12% vs ayer
```

---

### 2. `/reporte [período]`
**Uso:** Ver reportes por período (hoy, semana, mes)
```
/reporte hoy
/reporte semana
/reporte mes
/reporte 7        (últimos 7 días)
```

**Respuesta:**
```
📋 REPORTE - Últimas 7 Días

Total visitas: 1,245
Dispositivos únicos: 892
Tasa conversión: 34%
Dispositivo más usado: iPhone (34%)
Navegador favorito: Safari (31%)
```

---

## 🔔 Comandos de Alertas

### 3. `/alertas [on|off]`
**Uso:** Activar/desactivar alertas en tiempo real
```
/alertas on        (recibir notificaciones de cada nueva métrica)
/alertas off       (solo resúmenes)
```

**Respuesta:**
```
✅ Alertas activadas para Zona Rotamundos
Recibirás notificaciones en tiempo real de nuevas conexiones
```

---

### 4. `/umbral [número]`
**Uso:** Configurar alertas solo cuando haya un número mínimo de visitas
```
/umbral 50         (alertar solo cada 50 visitas)
/umbral 0          (alertar cada visita)
```

---

## 📱 Comandos de Dispositivos

### 5. `/dispositivos [zona_id]`
**Uso:** Ver dispositivos más populares
```
/dispositivos
/dispositivos 1
```

**Respuesta:**
```
📱 DISPOSITIVOS MÁS USADOS - Hoy

🥇 iPhone 15 Pro: 34 (24%)
🥈 Samsung A53: 28 (20%)
🥉 iPhone 14: 22 (16%)
📱 Google Pixel 8: 18 (13%)
```

---

### 6. `/navegadores [zona_id]`
**Uso:** Ver navegadores más usados
```
/navegadores
```

**Respuesta:**
```
🌐 NAVEGADORES MÁS USADOS - Hoy

Safari: 56 (39%)
Chrome: 45 (31%)
Edge: 22 (15%)
Firefox: 12 (8%)
Otros: 10 (7%)
```

---

## 📅 Comandos de Tiempo Real

### 7. `/conectados [zona_id]`
**Uso:** Ver cuántos usuarios están conectados AHORA
```
/conectados
/conectados 1
```

**Respuesta:**
```
🔴 CONEXIONES EN TIEMPO REAL

Zona Rotamundos: 12 usuarios activos
Zona Norte: 8 usuarios activos
Zona Sur: 5 usuarios activos

Total: 25 usuarios conectados
```

---

### 8. `/ultimo`
**Uso:** Ver la última conexión registrada
```
/ultimo
```

**Respuesta:**
```
📍 ÚLTIMA CONEXIÓN - Zona Rotamundos

Hora: 20:35:42
Dispositivo: Samsung Galaxy S23
Navegador: Chrome 119
Tipo: Portal Cautivo
Duración: 2m 15s
```

---

## 🎯 Comandos de Filtros

### 9. `/filtrar [tipo]`
**Uso:** Filtrar notificaciones por tipo de portal
```
/filtrar formulario    (solo portales con formulario)
/filtrar video         (solo con video)
/filtrar carrusel      (solo con carrusel)
/filtrar todo          (todos los tipos)
```

---

### 10. `/top [número]`
**Uso:** Ver top de dispositivos/navegadores
```
/top 5                (top 5 dispositivos)
/top 10               (top 10 dispositivos)
/top navegadores 5    (top 5 navegadores)
```

---

## ⚙️ Comandos de Configuración

### 11. `/idioma [es|en|pt]`
**Uso:** Cambiar idioma del bot
```
/idioma es
/idioma en
```

---

### 12. `/zona_por_defecto [id]`
**Uso:** Establecer zona por defecto para comandos
```
/zona_por_defecto 1
```

---

### 13. `/preferencias`
**Uso:** Ver/editar todas las preferencias
```
/preferencias
```

**Respuesta:**
```
⚙️ PREFERENCIAS DEL CHAT

✅ Alertas: Activas
🔔 Umbral: Cada 10 visitas
📍 Zona por defecto: Rotamundos
🌐 Idioma: Español
⏰ Zona horaria: America/Mexico_City
```

---

## 📥 Comandos de Descarga

### 14. `/exportar [período] [formato]`
**Uso:** Exportar datos en CSV o JSON
```
/exportar hoy csv
/exportar semana json
/exportar mes csv
```

**Respuesta:**
```
📄 Descargando reporte...
(Archivo CSV con 145 registros - 45 KB)
```

---

## 🔍 Comandos de Búsqueda

### 15. `/buscar [mac|dispositivo|navegador] [valor]`
**Uso:** Buscar conexiones específicas
```
/buscar mac 00:11:22:33:44:55
/buscar dispositivo iPhone
/buscar navegador Chrome
```

---

## 📌 Comandos de Zona

### 16. `/zonas_info`
**Uso:** Ver información detallada de todas las zonas
```
/zonas_info
```

**Respuesta:**
```
📍 INFORMACIÓN DE ZONAS

1️⃣ Rotamundos
   Tipo: Formulario | Conectados: 12 | Hoy: 145 visitas

2️⃣ Zona Norte
   Tipo: Video | Conectados: 8 | Hoy: 89 visitas

3️⃣ Zona Sur
   Tipo: Carrusel | Conectados: 5 | Hoy: 56 visitas
```

---

### 17. `/desuscribir [zona_id]`
**Uso:** Dejar de recibir notificaciones de una zona
```
/desuscribir 1
/desuscribir    (desuscribir de todas)
```

---

## 🆘 Comandos de Soporte

### 18. `/estado`
**Uso:** Ver estado del bot y servidor
```
/estado
```

**Respuesta:**
```
✅ BOT EN LÍNEA

Versión: 2.0.1
Servidor: v3.i-free.com.mx
BD: Sincronizada
Última actualización: hace 30s
```

---

### 19. `/ping`
**Uso:** Verificar conectividad
```
/ping
```

**Respuesta:**
```
🏓 PONG! - 245ms
Conexión: Excelente
```

---

## 🎨 Comandos Interactivos

### 20. `/menu`
**Uso:** Mostrar menú interactivo con botones
```
/menu
```

**Botones:**
- 📊 Estadísticas
- 📱 Dispositivos
- 🔔 Alertas
- ⚙️ Preferencias
- 📥 Exportar

---

## 📈 Comandos Avanzados

### 21. `/comparar [zona1] [zona2] [período]`
**Uso:** Comparar estadísticas entre zonas
```
/comparar 1 2 hoy
/comparar 1 2 semana
```

---

### 22. `/tendencia [zona_id] [días]`
**Uso:** Ver tendencia de crecimiento
```
/tendencia 1 7      (últimos 7 días)
/tendencia 1 30     (últimos 30 días)
```

**Respuesta:**
```
📈 TENDENCIA - Últimos 7 Días

Día 1: 120 visitas
Día 2: 135 visitas ↑ +12%
Día 3: 128 visitas ↓ -5%
...
Promedio: 129 visitas/día
```

---

## 🔐 Comandos de Administrador

### 23. `/broadcast [mensaje]`
**Uso:** Enviar mensaje a todos los chats (solo admin)
```
/broadcast Mantenimiento programado en 1 hora
```

---

### 24. `/logs [líneas]`
**Uso:** Ver últimos registros del sistema
```
/logs 10
/logs 50
```

---

## 📋 Recomendación de Prioridad

### 🔴 Alta Prioridad (Implementar Primero)
1. `/estadisticas` - Muy útil
2. `/reporte` - Muy útil
3. `/alertas` - Control importante
4. `/dispositivos` - Análisis valioso
5. `/conectados` - Info en tiempo real

### 🟡 Media Prioridad
6. `/navegadores` - Análisis
7. `/filtrar` - Personalización
8. `/top` - Información complementaria
9. `/zona_por_defecto` - Conveniencia
10. `/exportar` - Utilidad

### 🟢 Baja Prioridad (Extras)
11. `/ping` - Verificación
12. `/estado` - Monitoreo
13. `/menu` - Interface
14. `/comparar` - Análisis avanzado

---

## 💾 Implementación de Ejemplo

```php
// En TelegramController.php

protected function handleStadisticasCommand($chatId, array $params)
{
    try {
        $zonaId = $params[0] ?? null;
        $chat = TelegramChat::where('chat_id', $chatId)->first();
        
        if (!$chat) {
            $this->enviarMensaje($chatId, '❌ Chat no registrado');
            return response()->json(['status' => 'error']);
        }
        
        // Obtener zonas (default: todas las del chat)
        if ($zonaId) {
            $zonas = $chat->zonas()->where('id', $zonaId)->get();
        } else {
            $zonas = $chat->zonas()->get();
        }
        
        $mensaje = '<b>📊 ESTADÍSTICAS DEL DÍA</b>\n\n';
        
        foreach ($zonas as $zona) {
            $stats = $this->obtenerEstadisticas($zona->id);
            $mensaje .= $this->formatearEstadisticas($zona, $stats);
        }
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);
        
        return response()->json(['status' => 'success']);
    } catch (\Exception $e) {
        Log::error('Error en /estadisticas: ' . $e->getMessage());
        return response()->json(['status' => 'error'], 500);
    }
}
```

