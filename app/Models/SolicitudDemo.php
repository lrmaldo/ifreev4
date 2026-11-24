<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Alguien pidió una demo de i-Free desde una página pública de resultados.
 */
class SolicitudDemo extends Model
{
    protected $table = 'solicitudes_demo';

    public const INTERESES = [
        'isp' => 'Tengo un ISP o WISP',
        'evento' => 'Organizo eventos o expos',
        'expositor' => 'Fui expositor o patrocinador',
        'otro' => 'Otro (negocio, plaza, escuela…)',
    ];

    protected $fillable = ['nombre', 'empresa', 'telefono', 'correo', 'interes', 'mensaje', 'origen', 'estado'];
}
