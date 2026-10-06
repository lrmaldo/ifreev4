<?php

/**
 * Script para diagnosticar y solucionar problemas de validación en subida de videos
 */

// Modo: "check" (diagnosticar) o "fix" (arreglar)
$mode = $_GET['mode'] ?? 'check';

// Resultados
$results = [];
$fixesApplied = [];
$issues = [];

// 1. Verificar los límites de PHP
$results['php_limits'] = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time')
];

// Funciones para convertir límites a bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int) $val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// Comprobar si los límites son suficientes
$required_upload = 104857600; // 100MB en bytes
$required_post = 104857600;   // 100MB en bytes
$required_memory = 268435456; // 256MB en bytes

if (return_bytes($results['php_limits']['upload_max_filesize']) < $required_upload) {
    $issues[] = "El límite de upload_max_filesize ({$results['php_limits']['upload_max_filesize']}) es menor de 100MB";
}

if (return_bytes($results['php_limits']['post_max_size']) < $required_post) {
    $issues[] = "El límite de post_max_size ({$results['php_limits']['post_max_size']}) es menor de 100MB";
}

if (return_bytes($results['php_limits']['memory_limit']) < $required_memory) {
    $issues[] = "El límite de memory_limit ({$results['php_limits']['memory_limit']}) es menor de 256MB";
}

// 2. Verificar la extensión fileinfo (necesaria para detectar MIME types)
$results['fileinfo_extension'] = extension_loaded('fileinfo');
if (!$results['fileinfo_extension']) {
    $issues[] = "La extensión fileinfo no está cargada";
}

// 3. Comprobar la existencia y permisos de directorios de carga temporales
$upload_tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
$results['tmp_dir'] = [
    'path' => $upload_tmp_dir,
    'exists' => file_exists($upload_tmp_dir),
    'writable' => is_writable($upload_tmp_dir)
];

if (!$results['tmp_dir']['exists'] || !$results['tmp_dir']['writable']) {
    $issues[] = "El directorio temporal de carga no existe o no tiene permisos de escritura";
}

// 4. Verificar configuración de Livewire
$livewireConfigPath = __DIR__ . '/../config/livewire.php';
$results['livewire_config'] = [
    'path' => $livewireConfigPath,
    'exists' => file_exists($livewireConfigPath)
];

if ($results['livewire_config']['exists']) {
    $livewireConfig = include($livewireConfigPath);
    $results['livewire_config']['max_upload'] = $livewireConfig['temporary_file_upload']['rules'][2] ?? 'unknown';

    if (!isset($livewireConfig['temporary_file_upload']['rules'][2]) || $livewireConfig['temporary_file_upload']['rules'][2] !== 'max:102400') {
        $issues[] = "La configuración de Livewire no tiene el límite máximo establecido correctamente";
    }

    // Verifica si mp4 está en la lista de preview_mimes
    $previewMimes = $livewireConfig['temporary_file_upload']['preview_mimes'] ?? [];
    $results['livewire_config']['mp4_in_preview_mimes'] = in_array('mp4', $previewMimes);

    if (!$results['livewire_config']['mp4_in_preview_mimes']) {
        $issues[] = "MP4 no está en la lista de 'preview_mimes' en la configuración de Livewire";
    }
} else {
    $issues[] = "No se encuentra el archivo de configuración de Livewire";
}

// 5. Verificar el componente de Campanas
$campanasComponentPath = __DIR__ . '/../app/Livewire/Admin/Campanas/Index.php';
$results['campanas_component'] = [
    'path' => $campanasComponentPath,
    'exists' => file_exists($campanasComponentPath)
];

if ($results['campanas_component']['exists']) {
    $campanasCode = file_get_contents($campanasComponentPath);

    // Buscar la validación de video
    preg_match("/(['\"](nullable|required)\|mimes:([^'\"]+)['\"])/", $campanasCode, $matches);
    $results['campanas_component']['validation'] = $matches[0] ?? 'no encontrada';

    if (!isset($matches[0]) || !strpos($matches[0], 'mp4')) {
        $issues[] = "No se encuentra la validación correcta para archivos MP4 en el componente de Campanas";
    }
} else {
    $issues[] = "No se encuentra el componente de Campanas";
}

// Aplicar arreglos si estamos en modo "fix"
if ($mode === 'fix') {
    // 1. Crear o actualizar php.ini personalizado
    $phpIniContent = "; Archivo php.ini para controlar subidas de archivos grandes
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
";

    if (file_put_contents(__DIR__ . '/../php.ini', $phpIniContent)) {
        $fixesApplied[] = "Actualizado php.ini con límites adecuados";
    }

    // 2. Actualizar index.php si existe
    $indexPhpPath = __DIR__ . '/index.php';
    if (file_exists($indexPhpPath)) {
        $indexContent = file_get_contents($indexPhpPath);

        // Buscar los ini_set para límites y actualizar o agregar si no existen
        $updatedContent = $indexContent;

        if (strpos($indexContent, 'ini_set(\'upload_max_filesize') === false) {
            $phpOpening = '<?php';
            $iniSettings = "\n\n// Establecer límites de subida de archivos\nini_set('upload_max_filesize', '100M');\nini_set('post_max_size', '100M');\nini_set('memory_limit', '256M');\nini_set('max_execution_time', '300');\nini_set('max_input_time', '300');\n";
            $updatedContent = str_replace($phpOpening, $phpOpening . $iniSettings, $indexContent);
        }

        if ($updatedContent !== $indexContent) {
            file_put_contents($indexPhpPath, $updatedContent);
            $fixesApplied[] = "Actualizado index.php con límites PHP adecuados";
        }
    }

    // 3. Actualizar la configuración de Livewire si es necesario
    if ($results['livewire_config']['exists']) {
        $livewireConfigContent = file_get_contents($livewireConfigPath);

        // Buscar y actualizar la regla de tamaño máximo si es necesario
        if (strpos($livewireConfigContent, "'max:102400'") === false) {
            $livewireConfigContent = preg_replace(
                "/'rules' => \[[^\]]*\]/",
                "'rules' => ['required', 'file', 'max:102400']",
                $livewireConfigContent
            );

            file_put_contents($livewireConfigPath, $livewireConfigContent);
            $fixesApplied[] = "Actualizada la configuración de Livewire con tamaño máximo adecuado";
        }

        // Asegurar que mp4 está en preview_mimes
        if (!$results['livewire_config']['mp4_in_preview_mimes']) {
            $livewireConfigContent = preg_replace(
                "/'preview_mimes' => \[[^\]]*\]/",
                "'preview_mimes' => [\n            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',\n            'mov', 'avi', 'wmv', 'mp3', 'm4a',\n            'jpg', 'jpeg', 'mpga', 'webp', 'wma',\n        ]",
                $livewireConfigContent
            );

            file_put_contents($livewireConfigPath, $livewireConfigContent);
            $fixesApplied[] = "Añadido mp4 a la lista de preview_mimes en la configuración de Livewire";
        }
    }

    // 4. Asegurar que la validación en el componente es correcta
    if ($results['campanas_component']['exists']) {
        $campanasCode = file_get_contents($campanasComponentPath);

        // Buscar y reemplazar la validación si usa mimetypes en lugar de mimes
        if (strpos($campanasCode, 'mimetypes:video/mp4') !== false) {
            $campanasCode = str_replace(
                "mimetypes:video/mp4,video/quicktime",
                "mimes:mp4,mov,ogg,qt",
                $campanasCode
            );

            file_put_contents($campanasComponentPath, $campanasCode);
            $fixesApplied[] = "Actualizada la validación en el componente Campanas de mimetypes a mimes";
        }
    }
}

// Generar recomendaciones adicionales si hay problemas
$recommendations = [];

if (count($issues) > 0) {
    $recommendations[] = "Asegúrate de que tu servidor web (Apache/Nginx) esté configurado para manejar cargas de archivos grandes";
    $recommendations[] = "Si usas PHP-FPM, actualiza también la configuración en php-fpm.conf";
    $recommendations[] = "Considera usar el script de compresión de videos antes de subirlos";
    $recommendations[] = "Verifica el log de errores de PHP para más información sobre errores de carga";
}

// Mostrar resultados
echo '<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Subida de Videos</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; max-width: 1000px; margin: 0 auto; }
        h1, h2, h3 { color: #2c3e50; }
        .container { margin-bottom: 30px; }
        .result-item { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
        .result-key { font-weight: bold; display: inline-block; width: 180px; }
        .result-value { display: inline-block; }
        .issues { background-color: #ffeaa7; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .issue-item { color: #d35400; margin-bottom: 5px; }
        .fixes { background-color: #81ecec; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .fix-item { color: #00b894; margin-bottom: 5px; }
        .recommendations { background-color: #dfe6e9; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .buttons { margin-top: 20px; }
        button, .button { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; text-decoration: none; display: inline-block; }
        .fix-button { background-color: #e74c3c; color: white; }
        .check-button { background-color: #3498db; color: white; }
        .status-good { color: #27ae60; }
        .status-bad { color: #c0392b; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Subida de Videos</h1>

    <div class="container">
        <h2>Límites de PHP</h2>
        <div class="result-item">
            <div class="result-key">upload_max_filesize:</div>
            <div class="result-value ' . (return_bytes($results['php_limits']['upload_max_filesize']) >= $required_upload ? 'status-good' : 'status-bad') . '">
                ' . $results['php_limits']['upload_max_filesize'] . '
                ' . (return_bytes($results['php_limits']['upload_max_filesize']) < $required_upload ? '(Debería ser al menos 100M)' : '✓') . '
            </div>
        </div>
        <div class="result-item">
            <div class="result-key">post_max_size:</div>
            <div class="result-value ' . (return_bytes($results['php_limits']['post_max_size']) >= $required_post ? 'status-good' : 'status-bad') . '">
                ' . $results['php_limits']['post_max_size'] . '
                ' . (return_bytes($results['php_limits']['post_max_size']) < $required_post ? '(Debería ser al menos 100M)' : '✓') . '
            </div>
        </div>
        <div class="result-item">
            <div class="result-key">memory_limit:</div>
            <div class="result-value ' . (return_bytes($results['php_limits']['memory_limit']) >= $required_memory ? 'status-good' : 'status-bad') . '">
                ' . $results['php_limits']['memory_limit'] . '
                ' . (return_bytes($results['php_limits']['memory_limit']) < $required_memory ? '(Debería ser al menos 256M)' : '✓') . '
            </div>
        </div>
        <div class="result-item">
            <div class="result-key">max_execution_time:</div>
            <div class="result-value">
                ' . $results['php_limits']['max_execution_time'] . ' segundos
            </div>
        </div>
        <div class="result-item">
            <div class="result-key">max_input_time:</div>
            <div class="result-value">
                ' . $results['php_limits']['max_input_time'] . ' segundos
            </div>
        </div>
    </div>

    <div class="container">
        <h2>Extensión Fileinfo</h2>
        <div class="result-item">
            <div class="result-key">Estado:</div>
            <div class="result-value ' . ($results['fileinfo_extension'] ? 'status-good' : 'status-bad') . '">
                ' . ($results['fileinfo_extension'] ? 'Cargada ✓' : 'No cargada ✗') . '
            </div>
        </div>
    </div>

    <div class="container">
        <h2>Directorio Temporal</h2>
        <div class="result-item">
            <div class="result-key">Ruta:</div>
            <div class="result-value">
                ' . $results['tmp_dir']['path'] . '
            </div>
        </div>
        <div class="result-item">
            <div class="result-key">Existe:</div>
            <div class="result-value ' . ($results['tmp_dir']['exists'] ? 'status-good' : 'status-bad') . '">
                ' . ($results['tmp_dir']['exists'] ? 'Sí ✓' : 'No ✗') . '
            </div>
        </div>
        <div class="result-item">
            <div class="result-key">Permisos de escritura:</div>
            <div class="result-value ' . ($results['tmp_dir']['writable'] ? 'status-good' : 'status-bad') . '">
                ' . ($results['tmp_dir']['writable'] ? 'Sí ✓' : 'No ✗') . '
            </div>
        </div>
    </div>

    <div class="container">
        <h2>Configuración de Livewire</h2>
        <div class="result-item">
            <div class="result-key">Archivo:</div>
            <div class="result-value ' . ($results['livewire_config']['exists'] ? 'status-good' : 'status-bad') . '">
                ' . ($results['livewire_config']['exists'] ? $results['livewire_config']['path'] . ' ✓' : 'No encontrado ✗') . '
            </div>
        </div>';

if ($results['livewire_config']['exists']) {
    echo '<div class="result-item">
            <div class="result-key">Tamaño máximo:</div>
            <div class="result-value ' . ($results['livewire_config']['max_upload'] === 'max:102400' ? 'status-good' : 'status-bad') . '">
                ' . $results['livewire_config']['max_upload'] . '
                ' . ($results['livewire_config']['max_upload'] !== 'max:102400' ? '(Debería ser max:102400)' : '✓') . '
            </div>
          </div>
          <div class="result-item">
            <div class="result-key">MP4 en preview_mimes:</div>
            <div class="result-value ' . ($results['livewire_config']['mp4_in_preview_mimes'] ? 'status-good' : 'status-bad') . '">
                ' . ($results['livewire_config']['mp4_in_preview_mimes'] ? 'Sí ✓' : 'No ✗') . '
            </div>
          </div>';
}

echo '</div>

    <div class="container">
        <h2>Componente de Campanas</h2>
        <div class="result-item">
            <div class="result-key">Archivo:</div>
            <div class="result-value ' . ($results['campanas_component']['exists'] ? 'status-good' : 'status-bad') . '">
                ' . ($results['campanas_component']['exists'] ? $results['campanas_component']['path'] . ' ✓' : 'No encontrado ✗') . '
            </div>
        </div>';

if ($results['campanas_component']['exists']) {
    echo '<div class="result-item">
            <div class="result-key">Validación:</div>
            <div class="result-value">
                <pre>' . htmlspecialchars($results['campanas_component']['validation']) . '</pre>
            </div>
          </div>';
}

echo '</div>';

if (count($issues) > 0) {
    echo '<div class="issues">
        <h2>Problemas Detectados</h2>
        <ul>';
    foreach ($issues as $issue) {
        echo '<li class="issue-item">' . htmlspecialchars($issue) . '</li>';
    }
    echo '</ul>
    </div>';
}

if (count($fixesApplied) > 0) {
    echo '<div class="fixes">
        <h2>Arreglos Aplicados</h2>
        <ul>';
    foreach ($fixesApplied as $fix) {
        echo '<li class="fix-item">' . htmlspecialchars($fix) . '</li>';
    }
    echo '</ul>
    </div>';
}

if (count($recommendations) > 0) {
    echo '<div class="recommendations">
        <h2>Recomendaciones Adicionales</h2>
        <ul>';
    foreach ($recommendations as $recommendation) {
        echo '<li>' . htmlspecialchars($recommendation) . '</li>';
    }
    echo '</ul>
    </div>';
}

echo '<div class="buttons">';
if ($mode === 'check') {
    echo '<a href="?mode=fix" class="button fix-button">Aplicar Arreglos Automáticos</a>';
} else {
    echo '<a href="?mode=check" class="button check-button">Verificar Nuevamente</a>';

    // Si hay configuraciones que requieren reinicio del servidor
    if (count(array_intersect($issues, [
        "El límite de upload_max_filesize ({$results['php_limits']['upload_max_filesize']}) es menor de 100MB",
        "El límite de post_max_size ({$results['php_limits']['post_max_size']}) es menor de 100MB",
        "El límite de memory_limit ({$results['php_limits']['memory_limit']}) es menor de 256MB"
    ])) > 0) {
        echo '<div style="margin-top: 20px; background-color: #fab1a0; padding: 15px; border-radius: 5px;">
            <strong>Nota importante:</strong> Algunos cambios en los límites de PHP requieren reiniciar el servidor web para surtir efecto.
        </div>';
    }
}
echo '</div>
</body>
</html>';
