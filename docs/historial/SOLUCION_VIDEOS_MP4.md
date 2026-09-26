# Solución para el Problema de Subida de Videos MP4

## Resumen del Problema

Se presentaba un error con la subida de archivos MP4 grandes (33.8MB) en la plataforma i-free, con dos problemas principales:

1. **Límites de PHP insuficientes**: El sistema tenía un límite predeterminado de 2MB para `upload_max_filesize`
2. **Error de validación**: A pesar de seleccionar un archivo MP4 correcto, aparecía el mensaje de error "The archivo field is required"

## Cambios Realizados

### 1. Configuración de PHP

Se han configurado límites de PHP más altos en múltiples ubicaciones para asegurar que sean efectivos:

* **php.ini personalizado** en la raíz del proyecto:
  ```
  upload_max_filesize = 100M
  post_max_size = 100M
  memory_limit = 256M
  max_execution_time = 300
  max_input_time = 300
  ```

* **index.php** con configuración mediante `ini_set()`:
  ```php
  ini_set('upload_max_filesize', '100M');
  ini_set('post_max_size', '100M');
  ini_set('memory_limit', '256M');
  ini_set('max_execution_time', '300');
  ini_set('max_input_time', '300');
  ```

* **.htaccess** para Apache:
  ```
  <IfModule mod_php.c>
      php_value upload_max_filesize 100M
      php_value post_max_size 100M
      php_value memory_limit 256M
      php_value max_execution_time 300
      php_value max_input_time 300
  </IfModule>
  
  <IfModule mod_fcgid.c>
      FcgidMaxRequestLen 104857600
      FcgidIOTimeout 300
  </IfModule>
  
  <IfModule mod_fastcgi.c>
      FastCgiServer /usr/bin/php-cgi -idle-timeout 300 -processes 1
  </IfModule>
  
  <IfModule mod_security.c>
      SecRequestBodyLimit 104857600
      SecRequestBodyNoFilesLimit 104857600
  </IfModule>
  ```

### 2. Validación de Archivos

Se ha mejorado la validación de archivos en el componente Campanas:

* **Cambio de validación**:
  * De: `mimetypes:video/mp4,video/quicktime` 
  * A: `mimes:mp4,mov,ogg,qt,webm,mpeg,avi`

* **Mejora del campo de archivo en el formulario**:
  * Se han agregado atributos `accept` para especificar tipos permitidos
  * Se muestra el tamaño del archivo seleccionado
  * Se agregaron más detalles en los mensajes de error

### 3. Manejo de Errores

Se ha mejorado el manejo de errores en el método `save()` del componente Campanas:

* Se añadió un bloque `try-catch` para capturar excepciones
* Se agregó logging detallado para facilitar la depuración
* Se muestran mensajes de error más claros al usuario

### 4. Herramientas de Diagnóstico

Se crearon varias herramientas para ayudar a diagnosticar y solucionar problemas:

* **test-mime.php**: Analiza archivos y verifica su tipo MIME
* **test-video-error.php**: Herramienta específica para diagnosticar el error "The archivo field is required"
* **diagnostico-videos.php**: Diagnóstico completo del sistema y arreglo automático de problemas
* Se mejoró la herramienta existente **video-compressor.php**

### 5. Configuración de Livewire

Se verificó y optimizó la configuración de Livewire para subidas temporales:

* Límite de tamaño incrementado a 100MB
* Inclusión explícita de formatos de video en `preview_mimes`

## Recomendaciones para Usuario Final

1. **Utilizar el compresor de videos**: Si el video es demasiado grande, usar la herramienta `video-compressor.php` antes de subirlo.

2. **Verificar el formato**: Asegurarse de que el video esté en formato MP4 estándar, compatible con la web.

3. **Diagnosticar problemas**: Si persisten los errores, usar las herramientas de diagnóstico para identificar la causa.

4. **Límites del servidor**: Tener en cuenta que algunos límites solo pueden cambiarse a nivel del servidor web o PHP, por lo que podría ser necesario contactar al administrador del servidor.

## Notas sobre compatibilidad

* Estas soluciones son efectivas para entornos donde se puede modificar la configuración de PHP.
* En entornos de hosting compartido, puede ser necesario solicitar cambios al proveedor de hosting.
* La compresión de videos antes de la subida sigue siendo la solución más segura para archivos grandes.
