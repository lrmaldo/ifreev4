<?php
// Script para mostrar la configuración actual de PHP para Apache

// Ruta al archivo phpForApache.ini
$phpForApachePath = 'c:/wamp64/bin/php/php8.2.0/phpForApache.ini';

// Verificar que el archivo existe
if (!file_exists($phpForApachePath)) {
    echo "ERROR: No se encuentra el archivo $phpForApachePath\n";
    exit(1);
}

// Leer el contenido del archivo
$content = file_get_contents($phpForApachePath);
if ($content === false) {
    echo "ERROR: No se pudo leer el archivo $phpForApachePath\n";
    exit(1);
}

// Extraer y mostrar los valores actuales
echo "Valores actuales en $phpForApachePath:\n";
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

echo "\nValores recomendados:\n";
echo "--------------------------------\n";
echo "upload_max_filesize = 100M\n";
echo "post_max_size = 100M\n";
echo "memory_limit = 256M\n";
echo "max_execution_time = 300\n";
echo "max_input_time = 300\n";

echo "\nPara actualizar la configuración, deberías abrir el archivo en un editor con permisos de administrador y realizar los cambios manualmente.\n";
?>
