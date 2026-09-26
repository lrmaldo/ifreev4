#!/bin/bash
# Script de verificación y solución de problemas del webhook de Telegram
# Ejecutar este archivo para verificar y corregir problemas comunes en Linux/Mac

echo "--------------------------------------------------------------"
echo "    VERIFICACIÓN Y SOLUCIÓN DE PROBLEMAS DE TELEGRAM BOT"
echo "--------------------------------------------------------------"
echo ""

echo "[1/5] Verificando instalación de Telegraph..."
if php artisan migrate:status | grep -q telegraph_bots; then
    echo "✅ Migraciones de Telegraph detectadas"
else
    echo "⚠️ Las migraciones de Telegraph no parecen estar ejecutadas"
    echo ""
    read -p "¿Desea ejecutar las migraciones? (s/n): " respuesta
    if [ "$respuesta" = "s" ] || [ "$respuesta" = "S" ]; then
        echo "Ejecutando migraciones..."
        php artisan migrate
    else
        echo "Migraciones no ejecutadas, continuando..."
    fi
fi
echo ""

echo "[2/5] Ejecutando diagnóstico de Telegraph..."
php diagnostico-telegraph.php
echo ""

echo "[3/5] Verificando y solucionando problemas del webhook..."
php verificar-y-solucionar-webhook-telegram.php
echo ""

echo "[4/5] Verificando bots configurados..."
php check-telegraph-bots.php
echo ""

echo "[5/5] Probando envío de mensajes..."
read -p "Ingrese un ID de chat para probar (o deje vacío para omitir): " chatid
if [ ! -z "$chatid" ]; then
    echo "Enviando mensaje de prueba a chat $chatid..."
    php artisan telegraph:send "$chatid" "🧪 Mensaje de prueba enviado a las $(date +%H:%M:%S)"
    echo ""
fi

echo ""
echo "--------------------------------------------------------------"
echo "    VERIFICACIÓN COMPLETADA"
echo "--------------------------------------------------------------"
echo ""
echo "Para más información, consulte los siguientes archivos:"
echo "- SOLUCION-WEBHOOK-TELEGRAM.md"
echo "- VERIFICACION-TELEGRAM-COMPLETADA.md"
echo "- INSTRUCCIONES-ACTUALIZACION-TELEGRAM-PRODUCCION.md"
echo ""
echo "Si los problemas persisten, compruebe los logs:"
echo "- storage/logs/laravel.log"
echo ""
read -p "Presione Enter para continuar..."
