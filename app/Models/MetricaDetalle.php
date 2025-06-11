<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricaDetalle extends Model
{
    use HasFactory;

    protected $table = 'metrica_detalles';

    protected $fillable = [
        'metrica_id',
        'tipo_evento',
        'contenido',
        'detalle',
        'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    /**
     * Obtener la métrica principal asociada a este detalle
     */
    public function metrica(): BelongsTo
    {
        return $this->belongsTo(HotspotMetric::class, 'metrica_id');
    }

    /**
     * Scope para filtrar por tipo de evento
     */
    public function scopeByTipoEvento($query, $tipoEvento)
    {
        if ($tipoEvento) {
            return $query->where('tipo_evento', $tipoEvento);
        }
        return $query;
    }

    /**
     * Scope para filtrar por ID de métrica
     */
    public function scopeByMetrica($query, $metricaId)
    {
        if ($metricaId) {
            return $query->where('metrica_id', $metricaId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeByDateRange($query, $from, $to)
    {
        if ($from && $to) {
            return $query->whereBetween('fecha_hora', [$from, $to]);
        }
        if ($from) {
            return $query->where('fecha_hora', '>=', $from);
        }
        if ($to) {
            return $query->where('fecha_hora', '<=', $to);
        }
        return $query;
    }
}
