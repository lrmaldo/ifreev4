<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una lectura por minuto de un access point (clientes y tráfico del uplink).
 */
class AccessPointLectura extends Model
{
    protected $table = 'access_point_lecturas';

    public const UPDATED_AT = null;

    protected $fillable = [
        'access_point_id',
        'clientes',
        'bajada_bps',
        'subida_bps',
        'created_at',
    ];

    protected $casts = [
        'clientes' => 'integer',
        'bajada_bps' => 'integer',
        'subida_bps' => 'integer',
    ];

    public function accessPoint(): BelongsTo
    {
        return $this->belongsTo(AccessPoint::class);
    }
}
