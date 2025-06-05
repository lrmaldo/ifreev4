<?php

/**
 * Herramienta para comprimir videos y hacerlos compatibles con la plataforma
 * 
 * Este script utiliza FFmpeg para comprimir videos grandes y hacerlos más pequeños
 * para que puedan ser subidos a la plataforma sin problemas.
 * 
 * Uso: php video-compressor.php [ruta-al-video]
 */

if (PHP_SAPI !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos\n");
}

if ($argc < 2) {
    echo "Uso: php video-compressor.php [ruta-al-video]\n";
    echo "Ejemplo: php video-compressor.php C:\\videos\\mi-video.mp4\n";
    exit(1);
}

$inputFile = $argv[1];

if (!file_exists($inputFile)) {
    echo "Error: El archivo {$inputFile} no existe\n";
    exit(1);
}

// Verificar que FFmpeg esté instalado
exec('ffmpeg -version', $output, $returnCode);
if ($returnCode !== 0) {
    echo "Error: FFmpeg no está instalado o no está en el PATH\n";
    echo "Descarga FFmpeg desde https://ffmpeg.org/download.html e instálalo\n";
    exit(1);
}

// Crear nombre para archivo de salida
$pathInfo = pathinfo($inputFile);
$outputFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . 
             $pathInfo['filename'] . '_comprimido.' . ($pathInfo['extension'] ?? 'mp4');

echo "Comprimiendo video...\n";
echo "Archivo de entrada: {$inputFile}\n";
echo "Archivo de salida: {$outputFile}\n";

// Comando FFmpeg para comprimir el video
// -vcodec libx264: Usa el códec H.264 para video
// -crf 28: Factor de calidad constante (valores más altos = menor calidad/tamaño)
// -preset faster: Equilibrio entre velocidad de compresión y tamaño de archivo
// -acodec aac: Usa el códec AAC para audio
// -b:a 128k: Bitrate de audio de 128kbps
// -vf scale=-2:720: Reducir resolución a 720p manteniendo relación de aspecto
$command = "ffmpeg -i \"{$inputFile}\" -vcodec libx264 -crf 28 -preset faster -acodec aac -b:a 128k -vf scale=-2:720 \"{$outputFile}\"";

echo "Ejecutando: {$command}\n";
system($command, $returnCode);

if ($returnCode !== 0) {
    echo "Error: La compresión falló con código {$returnCode}\n";
    exit(1);
}

// Obtener tamaños de archivos
$originalSize = filesize($inputFile);
$compressedSize = filesize($outputFile);
$savingsPercent = round(100 - ($compressedSize / $originalSize * 100), 2);

echo "\nCompresión completada!\n";
echo "Tamaño original: " . formatBytes($originalSize) . "\n";
echo "Tamaño comprimido: " . formatBytes($compressedSize) . "\n";
echo "Ahorro: {$savingsPercent}%\n";

/**
 * Formatea bytes a una representación más legible
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
