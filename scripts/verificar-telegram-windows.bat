@echo off
REM Script de verificación y solución de problemas del webhook de Telegram
REM Ejecutar este archivo para verificar y corregir problemas comunes

echo --------------------------------------------------------------
echo     VERIFICACIÓN Y SOLUCIÓN DE PROBLEMAS DE TELEGRAM BOT
echo --------------------------------------------------------------
echo.

echo [1/5] Verificando instalación de Telegraph...
php artisan migrate:status | findstr telegraph_bots
if %ERRORLEVEL% NEQ 0 (
    echo ⚠️  Las migraciones de Telegraph no parecen estar ejecutadas
    echo.
    set /p respuesta=¿Desea ejecutar las migraciones? (s/n):
    if /i "%respuesta%"=="s" (
        echo Ejecutando migraciones...
        php artisan migrate
    ) else (
        echo Migraciones no ejecutadas, continuando...
    )
) else (
    echo ✅ Migraciones de Telegraph detectadas
)
echo.

echo [2/5] Ejecutando diagnóstico de Telegraph...
php diagnostico-telegraph.php
echo.

echo [3/5] Verificando y solucionando problemas del webhook...
php verificar-y-solucionar-webhook-telegram.php
echo.

echo [4/5] Verificando bots configurados...
php check-telegraph-bots.php
echo.

echo [5/5] Probando envío de mensajes...
set /p chatid=Ingrese un ID de chat para probar (o deje vacío para omitir):
if not "%chatid%"=="" (
    echo Enviando mensaje de prueba a chat %chatid%...
    php artisan telegraph:send %chatid% "🧪 Mensaje de prueba enviado a las %TIME%"
    echo.
)

echo.
echo --------------------------------------------------------------
echo     VERIFICACIÓN COMPLETADA
echo --------------------------------------------------------------
echo.
echo Para más información, consulte los siguientes archivos:
echo - SOLUCION-WEBHOOK-TELEGRAM.md
echo - VERIFICACION-TELEGRAM-COMPLETADA.md
echo - INSTRUCCIONES-ACTUALIZACION-TELEGRAM-PRODUCCION.md
echo.
echo Si los problemas persisten, compruebe los logs:
echo - storage/logs/laravel.log
echo.
pause
