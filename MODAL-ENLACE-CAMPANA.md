# Modal de Enlace de Campaña - Documentación

## 📖 Descripción

Se ha implementado una funcionalidad que permite mostrar el campo `enlace` de las campañas en un modal con iframe debajo del título del carrusel en el portal cautivo. Esta funcionalidad es completamente responsive y funciona en dispositivos móviles.

## ✨ Características

### Funcionalidades Principales
- **Modal responsive**: Se adapta automáticamente a diferentes tamaños de pantalla
- **Iframe integrado**: Carga páginas externas dentro del modal
- **Botón de cierre**: X en la esquina superior derecha
- **Cierre por overlay**: Clic fuera del modal para cerrarlo
- **Tecla ESC**: Cierre con teclado
- **Manejo de errores**: Detección de errores de carga del iframe
- **Métricas**: Registro de interacciones del usuario

### Responsive Design
- **Desktop**: Modal de 95% del ancho, máximo 1200px
- **Tablet**: Modal de 98% del ancho con bordes redondeados
- **Móvil**: Modal a pantalla completa sin bordes

## 🎯 Ubicación

El botón "Ver más información" aparece:

1. **Con formulario**: Debajo del título de la campaña, antes del formulario
2. **Sin formulario**: Debajo del título de la campaña, antes del contenido visual

## 🛠️ Implementación Técnica

### Archivos Modificados

1. **formulario-cautivo.blade.php**
   - Agregado HTML del modal
   - Agregados estilos CSS responsive
   - Agregadas funciones JavaScript
   - Agregados botones en las secciones correspondientes

2. **ZonaLoginController.php**
   - Modificado mapeo de tipos visuales para incluir `enlace_campana`
   - Mapeo a tipo `carrusel` para métricas

### Estructura del Modal

```html
<div id="modalEnlace" class="modal-enlace">
    <div class="modal-enlace-overlay"></div>
    <div class="modal-enlace-content">
        <div class="modal-enlace-header">
            <h3 class="modal-enlace-titulo"></h3>
            <button class="modal-enlace-cerrar">X</button>
        </div>
        <div class="modal-enlace-body">
            <iframe class="modal-enlace-iframe"></iframe>
        </div>
    </div>
</div>
```

### Funciones JavaScript

#### `abrirModalEnlace(url, titulo)`
- Abre el modal con la URL y título especificados
- Previene el scroll del body
- Registra métrica de interacción

#### `cerrarModalEnlace()`
- Cierra el modal
- Restaura el scroll del body
- Limpia el iframe

#### `registrarInteraccionEnlace()`
- Envía métricas al servidor
- Registra el tipo de interacción como `enlace_campana`

## 🎨 Estilos CSS

### Variables CSS Utilizadas
- `--color-primary`: Color principal (#ff5e2c)
- `--color-secondary`: Color secundario (#ff8159)
- `--animation-speed`: Velocidad de animaciones (0.3s)

### Clases Principales
- `.modal-enlace`: Container principal del modal
- `.modal-enlace-content`: Contenido del modal
- `.modal-enlace-header`: Cabecera con título y botón cerrar
- `.modal-enlace-body`: Cuerpo que contiene el iframe
- `.btn-enlace-campana`: Estilo del botón que abre el modal

## 📱 Responsive Breakpoints

### Tablet (≤ 768px)
```css
.modal-enlace-content {
    width: 98%;
    height: 95vh;
    margin: 2.5vh auto;
    border-radius: 8px;
}
```

### Móvil (≤ 480px)
```css
.modal-enlace-content {
    width: 100%;
    height: 100vh;
    margin: 0;
    border-radius: 0;
}
```

## 🔧 Configuración

### Requisitos
1. La campaña debe tener el campo `enlace` completado
2. El enlace debe ser una URL válida
3. El sitio web debe permitir ser mostrado en iframe (algunos sitios tienen restricciones)

### Condiciones de Visualización
```php
@if($campanaSeleccionada && $campanaSeleccionada->enlace)
    <!-- Botón se muestra -->
@endif
```

## 📊 Métricas

### Datos Registrados
- **Tipo visual**: `enlace_campana` (mapeado a `carrusel`)
- **Acción**: `clic_boton` = true
- **Detalle**: "Usuario abrió enlace de campaña"
- **Zona y MAC**: Para identificación del usuario

### Endpoint de Métricas
```
POST /portal/actualizar-metrica
```

## 🧪 Testing

Se incluye un archivo de prueba: `public/test-modal-enlace.html`

### Funcionalidades Probadas
- ✅ Apertura del modal con diferentes URLs
- ✅ Cierre con botón X
- ✅ Cierre con overlay
- ✅ Cierre con tecla ESC
- ✅ Responsive en diferentes tamaños
- ✅ Manejo de errores de iframe
- ✅ Carga de contenido HTML directo

### URLs de Prueba Incluidas
1. Google (sitio básico)
2. Wikipedia (contenido informativo)
3. YouTube Embed (contenido multimedia)
4. HTML directo (data URL)

## 🚨 Limitaciones y Consideraciones

### Restricciones de Iframe
Algunos sitios web tienen políticas que impiden ser mostrados en iframe:
- **X-Frame-Options**: DENY o SAMEORIGIN
- **Content-Security-Policy**: frame-ancestors

### Solución para Sitios Bloqueados
Cuando un sitio no se puede cargar en iframe:
1. Se muestra un mensaje de error
2. Se ofrece un botón para abrir en nueva ventana
3. El usuario puede acceder al contenido externamente

### Rendimiento
- El iframe se limpia al cerrar el modal para liberar recursos
- Las URLs se cargan con un delay de 100ms para mejorar la experiencia
- Se previene el scroll del body cuando el modal está abierto

## 🔄 Flujo de Usuario

1. Usuario ve botón "Ver más información" debajo del título
2. Hace clic en el botón
3. Se abre modal con iframe cargando la URL
4. Usuario interactúa con el contenido
5. Cierra modal con X, overlay o ESC
6. Continúa con el flujo normal del portal

## 🎯 Casos de Uso

### Ideales para Iframe
- Páginas de promociones
- Formularios de contacto
- Catálogos de productos
- Información de servicios
- Videos de YouTube (embed)

### Mejor en Nueva Ventana
- Redes sociales (Facebook, Instagram)
- Tiendas online complejas
- Aplicaciones web
- Sitios con muchas interacciones

## 🛡️ Seguridad

### Validaciones Implementadas
- Validación de URL en el backend
- Escape de HTML en los títulos
- Limpieza del iframe al cerrar
- CSRF token en peticiones AJAX

### Recomendaciones
- Usar HTTPS en los enlaces siempre que sea posible
- Verificar que los sitios enlazados sean confiables
- Monitorear las métricas de interacción

---

**Implementado por**: GitHub Copilot  
**Fecha**: 25 de junio de 2025  
**Versión**: 1.0
