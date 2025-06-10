<?php

// Script para probar la subida de archivos en el servidor
// Guardar en public/test-file-upload.php

// Configuración de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar si se ha enviado un archivo
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    echo "<h2>Archivo recibido correctamente</h2>";

    // Mostrar información del archivo
    echo "<h3>Información del archivo:</h3>";
    echo "<ul>";
    echo "<li>Nombre: " . htmlspecialchars($_FILES['archivo']['name']) . "</li>";
    echo "<li>Tipo: " . htmlspecialchars($_FILES['archivo']['type']) . "</li>";
    echo "<li>Tamaño: " . number_format($_FILES['archivo']['size'] / 1048576, 2) . " MB</li>";
    echo "</ul>";

    // Carpeta de destino
    $carpeta = 'storage/test-uploads';

    // Crear la carpeta si no existe
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    // Generar un nombre único para el archivo
    $nombreArchivo = time() . '_' . basename($_FILES['archivo']['name']);
    $rutaDestino = $carpeta . '/' . $nombreArchivo;

    // Intentar mover el archivo
    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
        echo "<p style='color: green;'>Archivo guardado correctamente en: " . htmlspecialchars($rutaDestino) . "</p>";

        // Mostrar la imagen o link al archivo
        $esImagen = preg_match('/^image\//i', $_FILES['archivo']['type']);
        if ($esImagen) {
            echo "<p><img src='$rutaDestino' style='max-height: 200px;'></p>";
        } else {
            echo "<p><a href='$rutaDestino' target='_blank'>Ver archivo</a></p>";
        }
    } else {
        echo "<p style='color: red;'>Error al mover el archivo a su destino final.</p>";
        echo "<pre>";
        var_dump(error_get_last());
        echo "</pre>";
    }
} else {
    // Mostrar el formulario de subida
    echo "<h2>Prueba de subida de archivos</h2>";

    if (isset($_FILES['archivo'])) {
        echo "<p style='color: red;'>Error en la subida: ";
        switch ($_FILES['archivo']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize).";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "El archivo excede el tamaño máximo permitido por el formulario.";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "El archivo se subió parcialmente.";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "No se seleccionó ningún archivo.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo "Falta la carpeta temporal del servidor.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                echo "No se pudo escribir el archivo en el disco.";
                break;
            case UPLOAD_ERR_EXTENSION:
                echo "Una extensión de PHP detuvo la subida.";
                break;
            default:
                echo "Error desconocido: " . $_FILES['archivo']['error'];
        }
        echo "</p>";
    }
?>
    <form action="" method="post" enctype="multipart/form-data">
        <p>
            <label for="archivo">Selecciona un archivo:</label>
            <input type="file" name="archivo" id="archivo">
        </p>
        <p>
            <button type="submit">Subir archivo</button>
        </p>
    </form>

    <h3>Configuración actual del servidor:</h3>
    <ul>
        <li>upload_max_filesize: <?= ini_get('upload_max_filesize') ?></li>
        <li>post_max_size: <?= ini_get('post_max_size') ?></li>
        <li>max_input_time: <?= ini_get('max_input_time') ?> segundos</li>
        <li>max_execution_time: <?= ini_get('max_execution_time') ?> segundos</li>
        <li>memory_limit: <?= ini_get('memory_limit') ?></li>
    </ul>
<?php
}
?>
