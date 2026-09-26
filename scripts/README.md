# Scripts de diagnóstico (legado)

Scripts sueltos que antes vivían en la raíz del proyecto: pruebas manuales, diagnósticos de Telegram/MikroTik y utilidades de configuración.

- Se ejecutan desde la raíz del proyecto: `php scripts/nombre-del-script.php`.
- Ya cargan `vendor/` y `bootstrap/` desde la raíz (`dirname(__DIR__)`).
- Varios están obsoletos: hacen referencia a `TelegramWebhookController` o a Telegraph, que ya no existen. `EJEMPLO-COMANDOS-TELEGRAM.php` es un fragmento de ejemplo, no un script ejecutable.

Si un script sigue siendo útil, lo mejor es convertirlo en un comando de Artisan (`app/Console/Commands`) o en una prueba (`tests/`). Los demás se pueden borrar.

La documentación histórica de cambios está en [`docs/historial/`](../docs/historial/INDICE-DOCUMENTACION.md).
