<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Access point que se consulta por SNMP cada minuto (aps:sondear).
 * Las columnas de "última lectura" las llena el sondeo; el resto se edita en Admin → Access points.
 */
class AccessPoint extends Model
{
    protected $fillable = [
        'zona_id',
        'nombre',
        'host',
        'puerto',
        'community',
        'activo',
        'en_linea',
        'modelo',
        'clientes',
        'macs',
        'uptime_seg',
        'bajada_bps',
        'subida_bps',
        'contadores',
        'ultimo_error',
        'ultima_respuesta_at',
        'ultimo_sondeo_at',
    ];

    protected $hidden = [
        'community',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'en_linea' => 'boolean',
        'puerto' => 'integer',
        'clientes' => 'integer',
        'macs' => 'array',
        'uptime_seg' => 'integer',
        'bajada_bps' => 'integer',
        'subida_bps' => 'integer',
        'contadores' => 'array',
        'ultima_respuesta_at' => 'datetime',
        'ultimo_sondeo_at' => 'datetime',
    ];

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(AccessPointLectura::class);
    }
}
