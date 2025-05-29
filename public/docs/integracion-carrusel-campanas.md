# Documentación: Integración del Carrusel de Campañas en Portales Cautivos

Este documento explica cómo integrar el carrusel de campañas en portales cautivos de WiFi externos al sistema.

## Descripción

El sistema de campañas permite mostrar un carrusel de imágenes y videos en el portal cautivo de login WiFi.
Las campañas se configuran desde el panel de administración y se muestran automáticamente según:

1. Periodo activo: `fecha_inicio <= hoy <= fecha_fin`
2. Visibilidad: `visible = true`
3. Cliente asignado: campañas específicas del cliente o campañas globales (sin cliente asignado)

## Integración en un Portal Cautivo Personalizado

### Paso 1: Incluir el script JS

Añade la siguiente etiqueta script en el HTML de tu portal cautivo:

```html
<script src="https://tu-dominio.com/js/carrusel-campanas.js"></script>
```

### Paso 2: Añadir el contenedor para el carrusel

Añade un contenedor donde se mostrará el carrusel:

```html
<div id="campanas-carrusel" style="width: 100%; height: 300px;"></div>
```

Puedes ajustar el tamaño según tus necesidades.

### Paso 3: Inicializar el carrusel

Añade el siguiente código JavaScript para inicializar el carrusel:

```html
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Reemplaza 'ID_DE_TU_ZONA' con el ID o ID personalizado de tu zona WiFi
    inicializarCarrusel('ID_DE_TU_ZONA');
  });
</script>
```

## Personalización

### Cambiar el ID del contenedor

Si necesitas usar otro ID para el contenedor, especifícalo como segundo parámetro:

```javascript
inicializarCarrusel('ID_DE_TU_ZONA', 'mi-contenedor-personalizado');
```

### Personalizar estilos

Puedes sobrescribir los estilos CSS predeterminados añadiendo tus propias reglas CSS después de cargar el script:

```html
<style>
  .carrusel-container {
    border-radius: 0; /* Quitar bordes redondeados */
  }

  .slide-title {
    font-size: 24px; /* Título más grande */
    color: yellow; /* Color personalizado */
  }

  /* Más personalizaciones... */
</style>
```

## Ejemplo completo

```html
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Cautivo</title>
  <!-- Script del carrusel -->
  <script src="https://tu-dominio.com/js/carrusel-campanas.js"></script>
  <style>
    body, html {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    .login-container {
      display: flex;
      flex-direction: column;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }

    .carrusel-wrapper {
      width: 100%;
      height: 300px;
      margin-bottom: 20px;
    }

    /* Personalizaciones adicionales */
    .carrusel-container {
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="carrusel-wrapper">
      <div id="campanas-carrusel" style="width: 100%; height: 100%;"></div>
    </div>

    <!-- Formulario de login y otros elementos del portal cautivo -->
    <form action="$(link-login)" method="post">
      <input type="hidden" name="mac" value="$(mac)">
      <input type="hidden" name="ip" value="$(ip)">
      <input type="text" name="username" placeholder="Usuario">
      <input type="password" name="password" placeholder="Contraseña">
      <button type="submit">Conectar</button>
    </form>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      inicializarCarrusel('ID_DE_TU_ZONA');
    });
  </script>
</body>
</html>
```

## Solución de problemas

### El carrusel no muestra nada

1. Verifica que el ID de la zona sea correcto
2. Comprueba que existan campañas activas para ese cliente
3. Revisa la consola del navegador para ver posibles errores
4. Verifica la conectividad con el servidor

### Los videos no se reproducen

Algunos navegadores móviles requieren interacción del usuario para reproducir videos. Considera usar imágenes para dispositivos móviles.

## Notas de seguridad

- El carrusel está diseñado para funcionar incluso en redes WiFi sin acceso a internet (solo con acceso al servidor del portal cautivo)
- Las campañas se sirven desde el mismo servidor, no requieren acceso a servicios externos
- Los archivos multimedia se entregan a través de URLs firmadas para mayor seguridad

Para más información o soporte, contacta al equipo de desarrollo.
