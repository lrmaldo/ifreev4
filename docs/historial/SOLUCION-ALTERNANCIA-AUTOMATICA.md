# Corrección de Alternancia Automática en Portales Cautivos

Fecha: 16 de junio de 2025

## Problema Resuelto

Se ha corregido la funcionalidad de "Alternancia automática" (modo aleatorio) para que alterne correctamente entre videos e imágenes cuando se configura como "aleatorio". Anteriormente existían los siguientes problemas:

1. La primera visualización siempre comenzaba con imágenes (nunca con videos)
2. La persistencia de la alternancia no era confiable entre sesiones
3. Faltaba documentación visual sobre qué tipo de contenido se estaba mostrando

## Solución Implementada

### 1. Correcciones en la Lógica de Alternancia

Se mejoró la lógica para garantizar que:
- La primera visualización sea verdaderamente aleatoria
- Se mantenga un registro consistente del último tipo mostrado
- La alternancia estricta se respete en visitas subsecuentes

### 2. Sistema Robusto de Persistencia

Se implementó un sistema dual de persistencia mediante:
- Cookies con duración de 24 horas
- Respaldo en sesión de Laravel
- Sincronización entre ambos mecanismos

### 3. Diagnóstico y Depuración

Se añadieron herramientas para facilitar el diagnóstico:
- Panel de depuración con información en tiempo real
- Ruta de diagnóstico `/diagnostico/alternancia/{zonaId}`
- Botón de diagnóstico en la configuración de campañas
- Indicadores visuales del tipo de contenido actual

### 4. Ampliación de Opciones de Configuración

Se ha ampliado el ENUM de `seleccion_campanas` para incluir:
- `aleatorio`: Alternancia automática entre videos e imágenes
- `prioridad`: Selección por prioridad numérica (menor número = mayor prioridad)
- `video`: Mostrar solo videos (si hay disponibles)
- `imagen`: Mostrar solo imágenes (si hay disponibles)

## Cómo Funciona la Alternancia

La alternancia automática tiene el siguiente comportamiento:

1. **Primera Visita**: Se selecciona aleatoriamente entre video o imagen.
2. **Visitas Subsecuentes**: Se alterna estrictamente respecto al último tipo mostrado.
3. **Sin Contenido Disponible**: Si solo hay un tipo de contenido disponible (solo videos o solo imágenes), se muestra ese tipo.

Los datos de alternancia se almacenan en:
- Cookie: `ultimo_tipo_zona_{id_zona}` (duración: 24 horas)
- Sesión: `ultimo_tipo_mostrado_{id_zona}`

## Cómo Verificar el Funcionamiento

1. Acceda a la configuración de campañas de una zona
2. Asegúrese de tener al menos un video y una imagen configurados como campañas activas
3. Seleccione "Alternancia automática" como método de selección
4. Use el botón "Diagnosticar Alternancia" para verificar
5. Refresque la página varias veces para comprobar la alternancia

## Recomendaciones

- Para un funcionamiento óptimo de la alternancia, asegúrese de tener al menos un video y una imagen configurados como campañas activas.
- Las campañas deben estar activas (fecha actual dentro del rango de fechas de inicio y fin).
- Utilice la herramienta de diagnóstico en entornos de desarrollo para verificar el comportamiento.

---
*Documento generado el 16 de junio de 2025*
