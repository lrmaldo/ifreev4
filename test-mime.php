<?php

// Script para probar la detección de tipos MIME

// Obtener parámetros de la línea de comandos o de la petición web
$filePath = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_GET['file'] ?? null) : null;

if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
    // Si se cargó un archivo mediante un formulario
    $tempName = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileSize = $_FILES['file']['size'];
    $fileError = $_FILES['file']['error'];

    echo "Archivo subido: $fileName<br>";
    echo "Tamaño: " . formatBytes($fileSize) . "<br>";
    echo "Código de error: $fileError (" . getUploadErrorMessage($fileError) . ")<br>";
    echo "Tipo MIME (según PHP): " . $_FILES['file']['type'] . "<br>";

    // Usar finfo para obtener el tipo MIME real
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($tempName);
    echo "Tipo MIME (detectado): $detectedMime<br>";

    // Información específica para videos MP4
    if (preg_match('/video/i', $detectedMime)) {
        echo "<br>Información del video:<br>";
        if (function_exists('exec')) {
            exec("ffmpeg -i " . escapeshellarg($tempName) . " 2>&1", $output);
            echo "<pre>" . implode("\n", $output) . "</pre>";
        } else {
            echo "La función exec() está deshabilitada, no se puede obtener información detallada del video.<br>";
        }
    }
} elseif ($filePath && file_exists($filePath)) {
    // Si se proporcionó una ruta a un archivo existente
    echo "Analizando archivo: $filePath<br>";
    echo "Tamaño: " . formatBytes(filesize($filePath)) . "<br>";

    // Obtener el tipo MIME usando finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($filePath);
    echo "Tipo MIME (detectado): $detectedMime<br>";

    // Verificar validaciones de Laravel
    echo "<br>Validaciones Laravel:<br>";
    $extensions = ['mp4', 'mov', 'ogg', 'qt'];
    $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
    echo "Extensión: $fileExt<br>";
    echo "¿Extensión válida según 'mimes:mp4,mov,ogg,qt'?: " . (in_array(strtolower($fileExt), $extensions) ? 'Sí' : 'No') . "<br>";

    // Validación por mime type
    $validMimeTypes = ['video/mp4', 'video/quicktime', 'video/ogg'];
    echo "¿MIME type válido según 'mimetypes:video/mp4,video/quicktime'?: " . (in_array($detectedMime, $validMimeTypes) ? 'Sí' : 'No') . "<br>";

} else {
    // Formulario para subir archivo o proporcionar ruta
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Test de MIME para archivo MP4</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
            h1, h2 { color: #333; }
            .field { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="file"], input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; }
            button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
            .info { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; }
        </style>
    </head>
    <body>
        <h1>Test de MIME para archivos de video</h1>

        <div class="info">
            <p>Esta herramienta ayuda a diagnosticar problemas con la validación de archivos de video en Laravel.</p>
            <p>Puedes subir un archivo o especificar la ruta a un archivo existente en el servidor.</p>
        </div>

        <div class="container">
            <h2>Subir archivo</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="field">
                    <label for="file_upload">Selecciona un archivo:</label>
                    <input type="file" name="file" id="file_upload">
                </div>
                <button type="submit">Analizar archivo</button>
            </form>
        </div>

        <div class="container">
            <h2>Analizar archivo existente</h2>
            <form method="get">
                <div class="field">
                    <label for="file_path">Ruta del archivo:</label>
                    <input type="text" name="file" id="file_path" placeholder="Ej: C:\\ruta\\a\\tu\\archivo.mp4">
                </div>
                <button type="submit">Analizar ruta</button>
            </form>
        </div>

        <div class="container">
            <h2>Información de configuración PHP</h2>
            <ul>
                <li>upload_max_filesize: <strong>' . ini_get("upload_max_filesize") . '</strong></li>
                <li>post_max_size: <strong>' . ini_get("post_max_size") . '</strong></li>
                <li>memory_limit: <strong>' . ini_get("memory_limit") . '</strong></li>
                <li>max_execution_time: <strong>' . ini_get("max_execution_time") . '</strong></li>
                <li>max_input_time: <strong>' . ini_get("max_input_time") . '</strong></li>
            </ul>
        </div>
    </body>
    </html>';
}

/**
 * Formatea bytes a un formato más legible
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Obtiene un mensaje descriptivo para códigos de error de subida
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize)';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo especificado en el formulario';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo fue subido parcialmente';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal';
        case UPLOAD_ERR_CANT_WRITE:
            return 'No se pudo escribir el archivo en el disco';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida';
        default:
            return 'Error desconocido';
    }
}
