<?php
/**
 * Herramienta para diagnosticar el problema "The archivo field is required"
 * en la subida de videos de campañas.
 */

$error = null;
$success = null;
$fileInfo = null;

// Procesar la carga del archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    if ($_FILES['test_file']['error'] === 0) {
        $tmpName = $_FILES['test_file']['tmp_name'];
        $originalName = $_FILES['test_file']['name'];
        $fileSize = $_FILES['test_file']['size'];
        $fileType = $_FILES['test_file']['type'];

        // Obtener información real del archivo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMimeType = $finfo->file($tmpName);

        // Determinar la extensión del archivo
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Verificar si es un video según la extensión
        $allowedExtensions = ['mp4', 'mov', 'ogg', 'qt', 'webm', 'mpeg', 'avi'];
        $isAllowedExtension = in_array($extension, $allowedExtensions);

        // Verificar si es un video según el MIME type
        $videoMimeTypes = ['video/mp4', 'video/quicktime', 'video/ogg', 'video/webm', 'video/mpeg', 'video/avi', 'video/x-msvideo'];
        $isVideoMimeType = in_array($detectedMimeType, $videoMimeTypes);

        // Verificar si pasaría la validación de Laravel 'mimes:mp4,mov,ogg,qt'
        $wouldPassLaravelMimes = $isAllowedExtension;

        // Verificar si pasaría la validación de Laravel 'mimetypes:video/mp4,video/quicktime'
        $wouldPassLaravelMimeTypes = $isVideoMimeType;

        $fileInfo = [
            'name' => $originalName,
            'size' => formatBytes($fileSize),
            'type_reported' => $fileType,
            'type_detected' => $detectedMimeType,
            'extension' => $extension,
            'is_allowed_extension' => $isAllowedExtension,
            'is_video_mime' => $isVideoMimeType,
            'would_pass_mimes' => $wouldPassLaravelMimes,
            'would_pass_mimetypes' => $wouldPassLaravelMimeTypes
        ];

        $success = 'Archivo analizado correctamente.';
    } else {
        $error = 'Error al subir el archivo: ' . uploadErrorMessage($_FILES['test_file']['error']);
    }
}

// Función para formatear bytes a unidades legibles
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Función para obtener mensajes de error de carga de archivos
function uploadErrorMessage($code) {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize).';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario.';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo fue subido parcialmente.';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'La carga del archivo se detuvo por una extensión PHP.';
        default:
            return 'Error desconocido.';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Subida de Videos</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .btn {
            background: #3498db;
            color: white;
            border: 0;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #2980b9;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f2f2f2;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 12px;
            border-radius: 10px;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .info-section {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 4px;
            margin-top: 30px;
        }
        .info-section h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <h1>Diagnóstico de Error "The archivo field is required"</h1>

    <div class="card">
        <h2>Subir un video para analizar</h2>
        <p>Esta herramienta analizará tu archivo de video para determinar por qué puede estar fallando la validación de Laravel.</p>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="test_file">Selecciona el archivo de video que estás intentando subir:</label>
                <input type="file" name="test_file" id="test_file" accept=".mp4,.mov,.ogg,.qt,.webm,.mpeg,.avi,video/*">
            </div>

            <button type="submit" class="btn">Analizar Archivo</button>
        </form>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>

    <?php if ($fileInfo): ?>
    <div class="card">
        <h2>Resultados del Análisis</h2>

        <table>
            <tr>
                <th>Propiedad</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Nombre del archivo</td>
                <td><?php echo htmlspecialchars($fileInfo['name']); ?></td>
            </tr>
            <tr>
                <td>Tamaño</td>
                <td><?php echo htmlspecialchars($fileInfo['size']); ?></td>
            </tr>
            <tr>
                <td>Extensión</td>
                <td><?php echo htmlspecialchars($fileInfo['extension']); ?></td>
            </tr>
            <tr>
                <td>¿Extensión permitida?</td>
                <td>
                    <?php if ($fileInfo['is_allowed_extension']): ?>
                        <span class="badge badge-success">✓ Sí</span>
                    <?php else: ?>
                        <span class="badge badge-danger">✗ No</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Tipo MIME reportado por el navegador</td>
                <td><?php echo htmlspecialchars($fileInfo['type_reported']); ?></td>
            </tr>
            <tr>
                <td>Tipo MIME detectado por PHP</td>
                <td><?php echo htmlspecialchars($fileInfo['type_detected']); ?></td>
            </tr>
            <tr>
                <td>¿Es un tipo MIME de video válido?</td>
                <td>
                    <?php if ($fileInfo['is_video_mime']): ?>
                        <span class="badge badge-success">✓ Sí</span>
                    <?php else: ?>
                        <span class="badge badge-danger">✗ No</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>¿Pasaría validación 'mimes:mp4,mov,ogg,qt'?</td>
                <td>
                    <?php if ($fileInfo['would_pass_mimes']): ?>
                        <span class="badge badge-success">✓ Sí</span>
                    <?php else: ?>
                        <span class="badge badge-danger">✗ No</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>¿Pasaría validación 'mimetypes:video/mp4,video/quicktime'?</td>
                <td>
                    <?php if ($fileInfo['would_pass_mimetypes']): ?>
                        <span class="badge badge-success">✓ Sí</span>
                    <?php else: ?>
                        <span class="badge badge-danger">✗ No</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php
        // Diagnóstico de problemas comunes
        $diagnoses = [];

        if (!$fileInfo['is_allowed_extension']) {
            $diagnoses[] = "La extensión del archivo no está permitida. Las extensiones permitidas son: mp4, mov, ogg, qt, webm, mpeg, avi.";
        }

        if (!$fileInfo['is_video_mime']) {
            $diagnoses[] = "El tipo MIME detectado no corresponde a un archivo de video válido.";
        }

        if ($fileInfo['is_allowed_extension'] && !$fileInfo['is_video_mime']) {
            $diagnoses[] = "A pesar de tener una extensión correcta, el contenido del archivo no parece ser un video válido.";
        }

        if (!empty($diagnoses)):
        ?>
        <h3>Diagnóstico:</h3>
        <ul>
            <?php foreach ($diagnoses as $diagnosis): ?>
            <li><?php echo htmlspecialchars($diagnosis); ?></li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Recomendación:</strong> Intenta usar el <a href="video-compressor.php">compresor de videos</a> o convierte el archivo a formato MP4 estándar usando otra herramienta.</p>
        <?php else: ?>
        <h3>Diagnóstico:</h3>
        <p>El archivo parece válido según nuestro análisis. El problema puede estar en:</p>
        <ul>
            <li>Los límites de subida de archivos de PHP (si el archivo es mayor a lo permitido).</li>
            <li>La configuración de Livewire para manejo de archivos temporales.</li>
            <li>Errores en la validación del componente.</li>
        </ul>
        <p><strong>Recomendación:</strong> Ejecuta el <a href="diagnostico-videos.php">diagnóstico completo</a> para verificar todos los aspectos de la configuración.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="info-section">
        <h3>Información Adicional</h3>
        <p>El error "The archivo field is required" suele aparecer cuando:</p>
        <ul>
            <li>El archivo es demasiado grande y PHP lo rechaza antes de llegar a la validación de Laravel.</li>
            <li>El archivo tiene una extensión correcta pero su contenido no coincide con el tipo MIME esperado.</li>
            <li>La validación en el componente Livewire está rechazando el archivo por algún criterio no cumplido.</li>
        </ul>
        <p>Límites de PHP actuales:</p>
        <ul>
            <li>upload_max_filesize: <strong><?php echo ini_get('upload_max_filesize'); ?></strong></li>
            <li>post_max_size: <strong><?php echo ini_get('post_max_size'); ?></strong></li>
            <li>memory_limit: <strong><?php echo ini_get('memory_limit'); ?></strong></li>
        </ul>
    </div>
</body>
</html>
