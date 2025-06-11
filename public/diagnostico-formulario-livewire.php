<?php
/**
 * Diagnóstico para problemas de funcionamiento de formularios Livewire
 * 
 * Este script realiza verificaciones avanzadas para diagnosticar problemas comunes
 * al usar Livewire en producción, especialmente relacionados con la subida de archivos.
 */

// Verificación básica para asegurar que este script solo se ejecute en entorno controlado
if (!isset($_GET['token']) || $_GET['token'] !== 'debugging_diagnosis') {
    die('Acceso no autorizado. Agregue ?token=debugging_diagnosis a la URL.');
}

// Incluir el autocargador de Laravel
require __DIR__ . '/../vendor/autoload.php';

// Crear una nueva instancia de la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Arrancar el kernel de Laravel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

// Configurar salida como HTML
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Livewire - i-Free</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>
</head>
<body class='bg-gray-100'>
    <div class='container mx-auto px-4 py-8'>
        <h1 class='text-3xl font-bold mb-6'>Diagnóstico de Formularios Livewire - i-Free</h1>";

// Función para mostrar sección
function mostrarSeccion($titulo, $contenido) {
    echo "<div class='bg-white shadow rounded-lg p-6 mb-6'>
        <h2 class='text-xl font-semibold mb-4'>$titulo</h2>
        $contenido
    </div>";
}

// Función para mostrar información de error/éxito
function mostrarItem($label, $valor, $tipo = 'info') {
    $colorClase = [
        'success' => 'text-green-600',
        'warning' => 'text-yellow-600',
        'error' => 'text-red-600',
        'info' => 'text-blue-600',
    ][$tipo] ?? 'text-gray-800';
    
    return "<div class='grid grid-cols-1 md:grid-cols-3 gap-4 py-2 border-b'>
        <div class='font-medium'>$label:</div>
        <div class='md:col-span-2 $colorClase'>$valor</div>
    </div>";
}

// Verificación de entorno
$contenido = mostrarItem('Entorno', app()->environment(), app()->environment() === 'production' ? 'warning' : 'info');
$contenido .= mostrarItem('Debug Mode', config('app.debug') ? 'Activado' : 'Desactivado', config('app.debug') ? 'warning' : 'success');
$contenido .= mostrarItem('URL de la aplicación', config('app.url'));
mostrarSeccion('1. Información General', $contenido);

// Verificación de PHP y límites
$contenido = mostrarItem('Versión PHP', phpversion());
$contenido .= mostrarItem('upload_max_filesize', ini_get('upload_max_filesize'), ini_get('upload_max_filesize') === '2M' ? 'warning' : 'info');
$contenido .= mostrarItem('post_max_size', ini_get('post_max_size'), ini_get('post_max_size') === '8M' ? 'warning' : 'info');
$contenido .= mostrarItem('max_execution_time', ini_get('max_execution_time'), ini_get('max_execution_time') < 30 ? 'warning' : 'info');
$contenido .= mostrarItem('memory_limit', ini_get('memory_limit'), ini_get('memory_limit') === '128M' ? 'warning' : 'info');
mostrarSeccion('2. Configuración de PHP', $contenido);

// Verificación de Livewire
$contenido = mostrarItem('Versión de Livewire', class_exists('Livewire\\LivewireManager') ? 'Livewire 2' : (class_exists('Livewire\\Livewire') ? 'Livewire 3' : 'No detectado'));

if (class_exists('Livewire\\Livewire') || class_exists('Livewire\\LivewireManager')) {
    $livewireConfig = config('livewire');
    $uploadTemp = $livewireConfig['temporary_file_upload']['directory'] ?? 'Por defecto (livewire-tmp)';
    $uploadRules = $livewireConfig['temporary_file_upload']['rules'] ?? 'Por defecto';
    $uploadTime = $livewireConfig['temporary_file_upload']['max_upload_time'] ?? 'Por defecto (5 minutos)';
    
    $contenido .= mostrarItem('Directorio temporal', $uploadTemp);
    $contenido .= mostrarItem('Reglas de subida', is_array($uploadRules) ? implode(', ', $uploadRules) : $uploadRules);
    $contenido .= mostrarItem('Tiempo máximo de subida', $uploadTime);
} else {
    $contenido .= mostrarItem('Estado de Livewire', 'No se detectó Livewire correctamente', 'error');
}
mostrarSeccion('3. Configuración de Livewire', $contenido);

// Verificación de permisos de carpetas
$directorios = [
    'storage' => storage_path(),
    'storage/app' => storage_path('app'),
    'storage/app/public' => storage_path('app/public'),
    'storage/app/public/campanas' => storage_path('app/public/campanas'),
    'storage/app/public/campanas/imagenes' => storage_path('app/public/campanas/imagenes'),
    'storage/app/public/campanas/videos' => storage_path('app/public/campanas/videos'),
    'storage/logs' => storage_path('logs'),
    'livewire-tmp' => storage_path('app/livewire-tmp')
];

$contenido = '';
foreach ($directorios as $nombre => $ruta) {
    $existe = file_exists($ruta);
    $esEscribible = is_writable($ruta);
    $permisos = $existe ? substr(sprintf('%o', fileperms($ruta)), -4) : 'N/A';
    
    $estado = '';
    $tipo = 'info';
    if (!$existe) {
        $estado = 'No existe';
        $tipo = 'error';
    } elseif (!$esEscribible) {
        $estado = 'Sin permisos de escritura';
        $tipo = 'error';
    } else {
        $estado = 'OK';
        $tipo = 'success';
    }
    
    $contenido .= mostrarItem(
        "$nombre <span class='text-xs text-gray-500'>($ruta)</span>", 
        "$estado - Permisos: $permisos", 
        $tipo
    );
}
mostrarSeccion('4. Permisos de Directorios', $contenido);

// Verificación del enlace simbólico
$publicPath = public_path('storage');
$storagePath = storage_path('app/public');
$enlaceOk = false;

if (file_exists($publicPath)) {
    if (PHP_OS_FAMILY === 'Windows') {
        // En Windows el enlace es más complicado de verificar
        $esEnlace = is_dir($publicPath);
        $apuntaAStorage = file_exists($publicPath . '/campanas');
        $enlaceOk = $esEnlace && $apuntaAStorage;
    } else {
        $esEnlace = is_link($publicPath);
        $apuntaAStorage = readlink($publicPath) === $storagePath;
        $enlaceOk = $esEnlace && $apuntaAStorage;
    }
}

$contenido = mostrarItem(
    'Estado del enlace simbólico storage', 
    $enlaceOk ? 'OK' : 'Problema detectado (el enlace no existe o no apunta correctamente)',
    $enlaceOk ? 'success' : 'error'
);

if (!$enlaceOk) {
    $contenido .= mostrarItem(
        'Solución recomendada', 
        'Ejecutar: <code>php artisan storage:link</code> o crear manualmente el enlace simbólico',
        'warning'
    );
}
mostrarSeccion('5. Enlace Simbólico de Storage', $contenido);

// Diagnóstico adicional
$contenido = '';

// Comprobar si estamos detrás de un proxy
$posiblesProxies = [
    'HTTP_X_FORWARDED_FOR',
    'HTTP_X_FORWARDED_HOST',
    'HTTP_X_FORWARDED_PROTO',
    'HTTP_X_REAL_IP',
    'HTTP_CLIENT_IP'
];

$detrasDeProxy = false;
foreach ($posiblesProxies as $header) {
    if (!empty($_SERVER[$header])) {
        $detrasDeProxy = true;
        $contenido .= mostrarItem("Cabecera Proxy: $header", $_SERVER[$header], 'info');
    }
}

if ($detrasDeProxy) {
    $trustedProxies = config('trustedproxy.proxies');
    $trustedHeaders = config('trustedproxy.headers');
    
    $contenido .= mostrarItem(
        'Estado de Proxy', 
        'Se detectó que la aplicación está detrás de un proxy',
        'warning'
    );
    
    $contenido .= mostrarItem(
        'Proxies confiables', 
        is_array($trustedProxies) ? implode(', ', $trustedProxies) : $trustedProxies,
        'info'
    );
    
    $contenido .= mostrarItem(
        'Headers confiables', 
        is_numeric($trustedHeaders) ? "Usando constante: $trustedHeaders" : (is_array($trustedHeaders) ? implode(', ', $trustedHeaders) : $trustedHeaders),
        'info'
    );
    
    $contenido .= mostrarItem(
        'Solución potencial', 
        'Verificar configuración en <code>config/trustedproxy.php</code> y considerar establecer <code>proxies => \'*\'</code> para producción',
        'info'
    );
} else {
    $contenido .= mostrarItem('Estado de Proxy', 'No se detectaron cabeceras de proxy', 'success');
}

mostrarSeccion('6. Configuración de Proxy', $contenido);

// Diagnóstico específico para Campanas
try {
    $contenido = '';
    
    // Intentar crear directorios necesarios
    $directoriosNecesarios = [
        'campanas/imagenes',
        'campanas/videos'
    ];

    foreach ($directoriosNecesarios as $dir) {
        $fullPath = 'public/' . $dir;
        $exists = Storage::exists($fullPath);
        
        if (!$exists) {
            try {
                Storage::makeDirectory($fullPath);
                $contenido .= mostrarItem("Directorio $dir", "Creado automáticamente", 'success');
            } catch (\Exception $e) {
                $contenido .= mostrarItem("Directorio $dir", "Error al crear: " . $e->getMessage(), 'error');
            }
        } else {
            $contenido .= mostrarItem("Directorio $dir", "Ya existe", 'success');
        }
    }
    
    // Verificar si podemos escribir en estos directorios
    foreach ($directoriosNecesarios as $dir) {
        $fullPath = storage_path('app/public/' . $dir);
        
        if (file_exists($fullPath)) {
            // Intentar crear un archivo temporal
            $testfile = $fullPath . '/test_' . time() . '.txt';
            $resultado = '';
            
            try {
                $fp = @fopen($testfile, 'w');
                if ($fp) {
                    fwrite($fp, 'Test de escritura');
                    fclose($fp);
                    unlink($testfile);
                    $resultado = "Se puede escribir correctamente";
                    $tipo = 'success';
                } else {
                    throw new \Exception("No se pudo crear el archivo");
                }
            } catch (\Exception $e) {
                $resultado = "Error al escribir: " . $e->getMessage();
                $tipo = 'error';
            }
            
            $contenido .= mostrarItem("Prueba de escritura en $dir", $resultado, $tipo);
        }
    }
    
    mostrarSeccion('7. Diagnóstico Específico de Campañas', $contenido);
} catch (\Exception $e) {
    $contenido = mostrarItem("Error", "Error al realizar diagnóstico específico: " . $e->getMessage(), 'error');
    mostrarSeccion('7. Diagnóstico Específico de Campañas', $contenido);
}

// Recomendaciones finales
echo "<div class='bg-blue-50 p-6 rounded-lg shadow mb-6'>
    <h2 class='text-xl font-bold text-blue-800 mb-4'>Recomendaciones finales</h2>
    <ul class='list-disc ml-6 space-y-2 text-blue-700'>
        <li>Verifique que todos los directorios necesarios tengan permisos de escritura (chmod 755 para directorios, 644 para archivos)</li>
        <li>Asegúrese de que el enlace simbólico de storage esté correctamente configurado</li>
        <li>En producción, revise los límites de PHP para subida de archivos</li>
        <li>Verifique que las carpetas temporales de Livewire estén creadas y sean escribibles</li>
        <li>Considere revisar los logs de errores en <code>storage/logs/laravel.log</code></li>
        <li>Si utiliza un proxy o balanceador de carga, configure adecuadamente <code>trustedproxy.php</code></li>
    </ul>
</div>";

// Botón para probar la subida de archivos
echo "<div class='bg-white shadow rounded-lg p-6 mb-6'>
    <h2 class='text-xl font-semibold mb-4'>Herramienta de prueba de subida</h2>
    <form method='post' action='" . url('/diagnostico-formulario-livewire.php') . "?token=debugging_diagnosis' enctype='multipart/form-data' class='space-y-4'>
        " . csrf_field() . "
        <div>
            <label class='block text-sm font-medium text-gray-700 mb-1'>Seleccionar archivo de prueba:</label>
            <input type='file' name='test_file' class='border p-2 w-full'>
        </div>
        <div>
            <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'>
                Probar subida de archivo
            </button>
        </div>
    </form>";

// Procesar la subida de prueba si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    try {
        $file = $_FILES['test_file'];
        
        $info = "<div class='mt-4 p-4 bg-gray-50 rounded'>
            <h3 class='font-semibold mb-2'>Información del archivo recibido:</h3>
            <ul class='space-y-1 text-sm'>";
            
        $info .= "<li><span class='font-medium'>Nombre:</span> {$file['name']}</li>";
        $info .= "<li><span class='font-medium'>Tipo:</span> {$file['type']}</li>";
        $info .= "<li><span class='font-medium'>Tamaño:</span> " . number_format($file['size'] / 1024, 2) . " KB</li>";
        $info .= "<li><span class='font-medium'>Código de error:</span> {$file['error']}";
        
        // Interpretar el código de error
        $errorMsg = '';
        switch ($file['error']) {
            case UPLOAD_ERR_OK: 
                $errorMsg = 'No hay error, el archivo se cargó correctamente.';
                break;
            case UPLOAD_ERR_INI_SIZE:
                $errorMsg = 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize).';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'El archivo excede el tamaño máximo permitido por el formulario HTML.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = 'El archivo solo se cargó parcialmente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'No se cargó ningún archivo.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg = 'No se encuentra disponible la carpeta temporal.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg = 'No se pudo escribir el archivo en el disco.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMsg = 'Una extensión PHP detuvo la carga del archivo.';
                break;
            default:
                $errorMsg = 'Error desconocido.';
        }
        
        $info .= " - <span class='" . ($file['error'] === 0 ? 'text-green-600' : 'text-red-600') . "'>$errorMsg</span></li>";
        
        // Intentar guardar el archivo
        if ($file['error'] === 0) {
            $tempPath = $file['tmp_name'];
            $destino = storage_path('app/public/diagnostico_test_' . time() . '_' . $file['name']);
            
            if (move_uploaded_file($tempPath, $destino)) {
                $info .= "<li class='text-green-600 font-medium'>¡Éxito! Archivo guardado en: $destino</li>";
            } else {
                $info .= "<li class='text-red-600 font-medium'>Error al mover el archivo al destino final.</li>";
            }
        }
        
        $info .= "</ul></div>";
        echo $info;
        
    } catch (\Exception $e) {
        echo "<div class='mt-4 p-4 bg-red-50 text-red-700 rounded'>
            Error al procesar el archivo: {$e->getMessage()}
        </div>";
    }
}

echo "</div>";

echo "</div></body></html>";
