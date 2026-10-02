<?php

return [
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
