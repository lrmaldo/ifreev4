# Este script debe ejecutarse como administrador
# Actualiza la configuración de PHP para Apache en WAMP

# Ruta al archivo phpForApache.ini
$phpForApachePath = "c:\wamp64\bin\php\php8.2.0\phpForApache.ini"

# Verificar que el archivo existe
if (-not (Test-Path $phpForApachePath)) {
    Write-Host "ERROR: No se encuentra el archivo $phpForApachePath" -ForegroundColor Red
    exit 1
}

# Crear una copia de seguridad
$backupPath = "$phpForApachePath.bak"
Copy-Item -Path $phpForApachePath -Destination $backupPath -Force
Write-Host "Se creó una copia de seguridad en $backupPath" -ForegroundColor Green

# Leer el contenido del archivo
$content = Get-Content $phpForApachePath -Raw

# Realizar las actualizaciones
$content = $content -replace "memory_limit = 128M", "memory_limit = 256M"
$content = $content -replace "max_input_time = 60", "max_input_time = 300"

# Escribir los cambios al archivo
Set-Content -Path $phpForApachePath -Value $content
Write-Host "Configuración actualizada exitosamente" -ForegroundColor Green

# Mostrar los valores actualizados
Write-Host "`nNuevos valores de configuración:" -ForegroundColor Cyan
Write-Host "--------------------------------"

$configValues = @(
    "upload_max_filesize",
    "post_max_size",
    "memory_limit",
    "max_execution_time",
    "max_input_time"
)

foreach ($value in $configValues) {
    if ($content -match "$value = (.*?)(\r|\n)") {
        Write-Host "$value = $($matches[1])"
    } else {
        Write-Host "$value = [No encontrado]" -ForegroundColor Yellow
    }
}

Write-Host "`nLa actualización se ha completado." -ForegroundColor Green
Write-Host "Recuerda reiniciar Apache para que los cambios surtan efecto." -ForegroundColor Yellow
