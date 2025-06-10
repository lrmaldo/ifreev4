<?php
// diagnóstico-archivos-livewire.php
// Coloca este archivo en la carpeta public y accede a él desde el navegador

// Mostrar todos los errores para debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Función para mostrar mensajes de diagnóstico
function showMessage($message, $type = 'info') {
    $color = 'blue';
    if ($type == 'success') $color = 'green';
    if ($type == 'error') $color = 'red';
    if ($type == 'warning') $color = 'orange';

    echo "<div style='margin: 5px; padding: 10px; border: 1px solid {$color}; border-left: 5px solid {$color}; background-color: " . ($type == 'error' ? '#ffeeee' : '#f9f9f9') . ";'>";
    echo "<strong style='color: {$color};'>" . strtoupper($type) . ":</strong> {$message}";
    echo "</div>";
}

// Función para verificar permisos
function checkPermissions($path, $display = true) {
    $exists = file_exists($path);
    $writable = is_writable($path);
    $permissions = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';

    if ($display) {
        if (!$exists) {
            showMessage("La ruta {$path} no existe", "error");
            return false;
        } elseif (!$writable) {
            showMessage("La ruta {$path} no tiene permisos de escritura", "error");
            return false;
        } else {
            showMessage("La ruta {$path} tiene permisos adecuados ({$permissions})", "success");
            return true;
        }
    }

    return $exists && $writable;
}

// Función para obtener información del sistema
function getSystemInfo() {
    $info = [
        'OS' => PHP_OS,
        'PHP Version' => PHP_VERSION,
        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
        'Server Protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Desconocido',
        'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconocido',
        'Script Filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Desconocido',
        'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido',
    ];

    echo "<h3>Información del Sistema</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    foreach ($info as $key => $value) {
        echo "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
    }
    echo "</table>";
}

// Función para mostrar configuración PHP relevante
function showPhpConfig() {
    $configs = [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'max_input_time' => ini_get('max_input_time'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'file_uploads' => ini_get('file_uploads') ? 'Habilitado' : 'Deshabilitado',
        'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'Por defecto del sistema',
    ];

    echo "<h3>Configuración PHP para Subida de Archivos</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";

    foreach ($configs as $key => $value) {
        $color = '';
        $recomendacion = '';

        // Verificar si los valores son adecuados
        if ($key === 'upload_max_filesize' && return_bytes($value) < 100 * 1024 * 1024) {
            $color = 'style="color: red;"';
            $recomendacion = 'Recomendado: al menos 100M';
        } else if ($key === 'post_max_size' && return_bytes($value) < 100 * 1024 * 1024) {
            $color = 'style="color: red;"';
            $recomendacion = 'Recomendado: al menos 100M';
        } else if ($key === 'max_input_time' && $value < 300) {
            $color = 'style="color: orange;"';
            $recomendacion = 'Recomendado: al menos 300 segundos';
        } else if ($key === 'max_execution_time' && $value < 300) {
            $color = 'style="color: orange;"';
            $recomendacion = 'Recomendado: al menos 300 segundos';
        } else if ($key === 'file_uploads' && $value !== 'Habilitado') {
            $color = 'style="color: red;"';
            $recomendacion = '¡Subida de archivos deshabilitada en PHP!';
        }

        echo "<tr {$color}>";
        echo "<td><strong>{$key}</strong></td>";
        echo "<td>{$value}</td>";
        echo "<td>{$recomendacion}</td>";
        echo "</tr>";
    }

    echo "</table>";
}

// Función helper para convertir valores como 8M a bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int) $val;

    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

// Verificar las extensiones PHP necesarias
function checkPhpExtensions() {
    $requiredExtensions = [
        'fileinfo' => 'Necesaria para determinar los tipos MIME',
        'gd' => 'Necesaria para manipulación de imágenes',
        'exif' => 'Útil para metadatos de imágenes',
    ];

    echo "<h3>Extensiones PHP</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Extensión</th><th>Estado</th><th>Descripción</th></tr>";

    foreach ($requiredExtensions as $extension => $description) {
        $loaded = extension_loaded($extension);
        $color = $loaded ? 'green' : 'red';
        $status = $loaded ? 'Cargado' : 'No Cargado';

        echo "<tr style='color: {$color};'>";
        echo "<td><strong>{$extension}</strong></td>";
        echo "<td>{$status}</td>";
        echo "<td>{$description}</td>";
        echo "</tr>";
    }

    echo "</table>";
}

// Función para probar la subida temporal de un archivo
function createTestUploadForm() {
    echo "<h3>Prueba de Subida de Archivo</h3>";
    echo "<form action='' method='post' enctype='multipart/form-data'>";
    echo "<p><input type='file' name='test_file'></p>";
    echo "<p><button type='submit' name='submit_test'>Subir archivo de prueba</button></p>";
    echo "</form>";

    if (isset($_POST['submit_test'])) {
        if (!isset($_FILES['test_file']) || $_FILES['test_file']['error'] !== 0) {
            $errorMessage = "Error en la subida: ";

            if (!isset($_FILES['test_file'])) {
                $errorMessage .= "No se recibió ningún archivo.";
            } else {
                switch ($_FILES['test_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errorMessage .= "El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize).";
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage .= "El archivo excede el tamaño máximo permitido por el formulario.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage .= "El archivo se subió parcialmente.";
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMessage .= "No se seleccionó ningún archivo.";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMessage .= "Falta la carpeta temporal del servidor.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMessage .= "No se pudo escribir el archivo en el disco.";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errorMessage .= "Una extensión de PHP detuvo la subida.";
                        break;
                    default:
                        $errorMessage .= "Error desconocido: " . $_FILES['test_file']['error'];
                }
            }

            showMessage($errorMessage, 'error');
        } else {
            showMessage("Archivo subido correctamente como archivo temporal", 'success');

            echo "<h4>Información del archivo:</h4>";
            echo "<pre>";
            print_r($_FILES['test_file']);
            echo "</pre>";

            // Intentar mover el archivo a una carpeta temporal para probar la escritura
            $tempDir = dirname(__FILE__) . '/storage/temp';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $destFile = $tempDir . '/' . basename($_FILES['test_file']['name']);

            if (move_uploaded_file($_FILES['test_file']['tmp_name'], $destFile)) {
                showMessage("El archivo se movió con éxito a {$destFile}", 'success');
            } else {
                showMessage("Error al mover el archivo a {$destFile}", 'error');
            }
        }
    }
}

// Comprobar la carpeta temporal del sistema
function checkTempDirectory() {
    $tempDir = sys_get_temp_dir();
    echo "<h3>Directorio Temporal del Sistema</h3>";
    echo "<p>Directorio temporal: <strong>{$tempDir}</strong></p>";

    checkPermissions($tempDir);
}

// Función principal
function runDiagnostic() {
    echo "<html><head><title>Diagnóstico de Subida de Archivos</title>";
    echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; line-height: 1.6; }</style>";
    echo "</head><body>";

    echo "<h1>Diagnóstico de Subida de Archivos para Livewire</h1>";

    // Verificar rutas importantes
    echo "<h3>Verificación de Permisos en Directorios Críticos</h3>";
    $rootPath = dirname(__FILE__, 2); // Path raíz del proyecto

    $paths = [
        "{$rootPath}/storage" => "Directorio principal de almacenamiento",
        "{$rootPath}/storage/app" => "Directorio de aplicación",
        "{$rootPath}/storage/app/public" => "Directorio público",
        "{$rootPath}/storage/app/public/campanas" => "Directorio de campañas",
        "{$rootPath}/storage/app/public/campanas/imagenes" => "Directorio de imágenes",
        "{$rootPath}/storage/app/public/campanas/videos" => "Directorio de videos",
        "{$rootPath}/public/storage" => "Enlace simbólico a almacenamiento público",
    ];

    $allGood = true;
    foreach ($paths as $path => $description) {
        echo "<p><strong>{$description}:</strong> {$path}</p>";
        if (!checkPermissions($path, true)) {
            $allGood = false;
        }
    }

    if (!$allGood) {
        showMessage("Se detectaron problemas con algunos directorios. Por favor, verifica los permisos.", "warning");
    } else {
        showMessage("Todos los directorios tienen los permisos correctos.", "success");
    }

    // Comprobar enlaces simbólicos
    echo "<h3>Verificación de Enlaces Simbólicos</h3>";
    $publicStorage = "{$rootPath}/public/storage";
    $appPublic = "{$rootPath}/storage/app/public";

    if (is_link($publicStorage)) {
        $target = readlink($publicStorage);
        showMessage("El enlace simbólico existe: {$publicStorage} -> {$target}", "success");
    } else if (is_dir($publicStorage)) {
        showMessage("La ruta {$publicStorage} es un directorio, no un enlace simbólico. Esto puede causar problemas.", "warning");
    } else {
        showMessage("No existe el enlace simbólico: {$publicStorage}. Ejecuta 'php artisan storage:link'", "error");
    }

    // Mostrar información del sistema
    getSystemInfo();

    // Verificar configuración PHP
    showPhpConfig();

    // Verificar extensiones
    checkPhpExtensions();

    // Verificar carpeta temporal
    checkTempDirectory();

    // Probar subida de archivos
    createTestUploadForm();

    echo "</body></html>";
}

// Ejecutar el diagnóstico
runDiagnostic();
