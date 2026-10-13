<?php

return [
    /*
    | Dirección pública del portal: a ella apunta el login.html que se sube al MikroTik.
    | El dominio debe estar en el walled garden del hotspot.
    */
    'portal_url' => rtrim(env('IFREE_PORTAL_URL', 'https://v3.i-free.com.mx'), '/'),

    /*
    | Segundos que el login.html espera respuesta del servidor antes de usar el respaldo
    | (conectar directo con el trial del MikroTik).
    */
    'respaldo_timeout' => (int) env('IFREE_RESPALDO_TIMEOUT', 6),

    /*
    | Alertas por Telegram durante eventos (comando evento:vigilar, cada minuto).
    | chats: IDs separados por coma (p. ej. el grupo de operación de Sattlink). Si se deja
    | vacío, se avisa a los chats de las zonas con tema "evento".
    */
    'alertas' => [
        'activas' => (bool) env('IFREE_ALERTAS', true),
        'chats' => env('IFREE_ALERTAS_CHATS', ''),
        'portal_lento_ms' => (int) env('IFREE_ALERTA_LENTO_MS', 3000),
        'errores_5min' => (int) env('IFREE_ALERTA_ERRORES', 20),
        'sin_visitas_min' => 10,       // minutos sin visitas...
        'sin_visitas_previas' => 20,   // ...después de una hora con al menos estas visitas
        'sin_registros_min' => 20,     // minutos con dispositivos nuevos...
        'sin_registros_nuevos' => 15,  // ...al menos estos, y ningún registro
        'recordar_min' => 15,          // cada cuánto se recuerda un problema que sigue
    ],

    /*
    | Responsable de los datos personales que se recaban en el portal cautivo cuando
    | la zona no pertenece a un cliente con nombre comercial. Revisar con su abogado.
    */
    'privacidad' => [
        'responsable' => env('IFREE_PRIVACIDAD_RESPONSABLE', 'Sattlink'),
        'domicilio' => env('IFREE_PRIVACIDAD_DOMICILIO', ''),
        'contacto' => env('IFREE_PRIVACIDAD_CONTACTO', ''),
        'url_aviso_integral' => env('IFREE_PRIVACIDAD_URL', ''),
    ],
];
