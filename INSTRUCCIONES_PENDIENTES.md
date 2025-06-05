# Actualización de configuración de PHP para subida de archivos grandes

## Cambios realizados hasta ahora
Hemos modificado satisfactoriamente las siguientes configuraciones:

1. **php.ini para PHP CLI y PHP CGI/FastCGI** (c:\wamp64\bin\php\php8.2.0\php.ini):
   - `upload_max_filesize = 100M`
   - `post_max_size = 100M`
   - `memory_limit = 256M`
   - `max_execution_time = 300`
   - `max_input_time = 300`

2. **Archivos de proyecto**:
   - Creado archivo `php.ini` en la raíz del proyecto
   - Modificado `.htaccess` para incluir directivas PHP
   - Actualizado `index.php` para establecer límites con `ini_set()`
   - Actualizado component `Livewire\Admin\Campanas\Index.php` para aumentar el límite de tamaño del archivo

## Pendiente: Configuración de PHP para Apache

Es necesario actualizar la configuración de PHP cuando se ejecuta como módulo de Apache. La ruta del archivo es:
```
c:\wamp64\bin\php\php8.2.0\phpForApache.ini
```

Los valores actuales son:
- `upload_max_filesize = 200M` (ya es mayor que nuestro objetivo de 100M)
- `post_max_size = 200M` (ya es mayor que nuestro objetivo de 100M)
- `memory_limit = 128M` (debe aumentarse a 256M)
- `max_execution_time = 400` (ya es mayor que nuestro objetivo de 300)
- `max_input_time = 60` (debe aumentarse a 300)

### Instrucciones para actualizar manualmente:

1. Cierra el servidor Apache si está en ejecución
2. Haz una copia de seguridad del archivo:
   ```
   copy c:\wamp64\bin\php\php8.2.0\phpForApache.ini c:\wamp64\bin\php\php8.2.0\phpForApache.ini.bak
   ```
3. Abre el archivo `c:\wamp64\bin\php\php8.2.0\phpForApache.ini` con un editor de texto como administrador
4. Busca y actualiza los siguientes valores:
   - Cambia `memory_limit = 128M` a `memory_limit = 256M`
   - Cambia `max_input_time = 60` a `max_input_time = 300`
5. Guarda el archivo
6. Reinicia el servidor Apache
7. Para verificar los cambios, puedes cargar el script `diagnostico-videos.php` en el navegador

Nota: Los valores de `upload_max_filesize` y `post_max_size` ya son de 200M, lo cual excede nuestro requisito de 100M, por lo que no es necesario modificarlos.

## Pruebas recomendadas

Una vez completados todos los cambios:

1. Inicia el servidor Apache
2. Accede a `diagnostico-videos.php` para verificar que todas las configuraciones están correctas
3. Intenta subir el archivo de video MP4 de 33.8MB a la plataforma
4. Verifica que no aparezca el error "The archivo field is required"
5. Confirma que el archivo se ha subido correctamente

## Solución completa

Con estos cambios, deberíamos haber resuelto los dos problemas identificados:
1. El límite de subida de PHP que estaba en 2MB (predeterminado)
2. El error de validación que mostraba "The archivo field is required" a pesar de seleccionar un archivo MP4

La solución ha incluido:
- Aumentar los límites de PHP en múltiples niveles de configuración
- Mejorar la validación para aceptar archivos MP4 y otros formatos de video
- Proporcionar herramientas de diagnóstico para identificar y resolver problemas
