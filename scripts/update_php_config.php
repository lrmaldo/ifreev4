<?php
// Script para actualizar phpForApache.ini

// Ruta al archivo phpForApache.ini
$phpForApachePath = 'c:/wamp64/bin/php/php8.2.0/phpForApache.ini';

// Verificar que el archivo existe
if (!file_exists($phpForApachePath)) {
    echo "ERROR: No se encuentra el archivo $phpForApachePath\n";
    exit(1);
}

// Crear una copia de seguridad
$backupPath = $phpForApachePath . '.bak';
if (!copy($phpForApachePath, $backupPath)) {
    echo "ERROR: No se pudo hacer una copia de seguridad del archivo\n";
    exit(1);
}
echo "Se creó una copia de seguridad en $backupPath\n";

// Leer el contenido del archivo
$content = file_get_contents($phpForApachePath);
if ($content === false) {
    echo "ERROR: No se pudo leer el archivo $phpForApachePath\n";
    exit(1);
}

// Definir los valores que queremos actualizar
$updates = [
    'upload_max_filesize = 200M' => 'upload_max_filesize = 100M',
    'post_max_size = 200M' => 'post_max_size = 100M',
    'memory_limit = 128M' => 'memory_limit = 256M',
    'max_execution_time = 400' => 'max_execution_time = 300',
    'max_input_time = 60' => 'max_input_time = 300'
];

// Realizar las actualizaciones
$originalContent = $content;
foreach ($updates as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

// Verificar si hubo cambios
if ($content === $originalContent) {
    echo "ADVERTENCIA: No se detectaron cambios necesarios en la configuración\n";
} else {
    // Escribir los cambios al archivo
    if (file_put_contents($phpForApachePath, $content) === false) {
        echo "ERROR: No se pudieron guardar los cambios en el archivo\n";
        exit(1);
    }
    echo "Configuración actualizada exitosamente\n";
}

// Verificar los nuevos valores
echo "\nNuevos valores de configuración:\n";
echo "--------------------------------\n";
$configValues = [
    'upload_max_filesize',
    'post_max_size',
    'memory_limit',
    'max_execution_time',
    'max_input_time'
];

foreach ($configValues as $value) {
    preg_match("/$value = (.*)/", $content, $matches);
    if (isset($matches[1])) {
        echo "$value = {$matches[1]}\n";
    } else {
        echo "$value = [No encontrado]\n";
    }
}

echo "\nLa actualización se ha completado.\n";
echo "Recuerda reiniciar Apache para que los cambios surtan efecto.\n";
?>
