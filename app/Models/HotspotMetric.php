<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'zona_id',
        'mac_address',
        'formulario_id',
        'dispositivo',
        'navegador',
        'sistema_operativo',
        'tipo_visual',
        'duracion_visual',
        'clic_boton',
        'veces_entradas',
    ];

    protected $casts = [
        'clic_boton' => 'boolean',
        'veces_entradas' => 'integer',
        'duracion_visual' => 'integer',
    ];

    /**
     * Relación con la zona
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * Relación con el formulario respondido (nullable)
     */
    public function formulario(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class, 'formulario_id');
    }

    /**
     * Relación con los detalles de la métrica
     */
    public function detalles()
    {
        return $this->hasMany(MetricaDetalle::class, 'metrica_id');
    }

    /**
     * Verifica si el usuario llenó el formulario
     */
    public function getRespondioFormularioAttribute(): bool
    {
        return !is_null($this->formulario_id);
    }

    /**
     * Scope para filtrar por zona
     */
    public function scopeByZona($query, $zonaId)
    {
        if ($zonaId) {
            return $query->where('zona_id', $zonaId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeByDateRange($query, $from, $to)
    {
        if ($from && $to) {
            return $query->whereBetween('created_at', [$from, $to]);
        }
        if ($from) {
            return $query->where('created_at', '>=', $from);
        }
        if ($to) {
            return $query->where('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Scope para filtrar por MAC address
     */
    public function scopeByMac($query, $mac)
    {
        if ($mac) {
            return $query->where('mac_address', 'like', "%{$mac}%");
        }
        return $query;
    }

    /**
     * Validar y mapear el tipo visual a uno permitido
     */
    public static function validarTipoVisual($tipoVisual)
    {
        $valoresPermitidos = ['formulario', 'carrusel', 'video', 'portal_cautivo', 'portal_entrada', 'login'];

        if (!in_array($tipoVisual, $valoresPermitidos)) {
            // Si es un botón de trial o login, lo mapeamos a 'login'
            if (in_array($tipoVisual, ['trial', 'login'])) {
                return 'login';
            } else {
                // Cualquier otro valor no reconocido lo mapeamos a 'formulario'
                return 'formulario';
            }
        }

        return $tipoVisual;
    }

    /**
     * Registra o actualiza una métrica de hotspot
     */
    public static function registrarMetrica($data)
    {
        // Aseguramos que tipo_visual sea un valor válido
        if (isset($data['tipo_visual'])) {
            $data['tipo_visual'] = self::validarTipoVisual($data['tipo_visual']);
        } else {
            $data['tipo_visual'] = 'formulario'; // Valor por defecto
        }

        $existingMetric = static::where('mac_address', $data['mac_address'])
            ->where('zona_id', $data['zona_id'])
            ->first();

        if ($existingMetric) {
            // Incrementar veces_entradas
            $existingMetric->increment('veces_entradas');

            // Actualizar datos de la visita actual
            $existingMetric->update([
                'dispositivo' => $data['dispositivo'],
                'navegador' => $data['navegador'],
                'sistema_operativo' => $data['sistema_operativo'] ?? null,
                'tipo_visual' => $data['tipo_visual'],
                'duracion_visual' => $data['duracion_visual'] ?? 0,
                'clic_boton' => $data['clic_boton'] ?? false,
                'formulario_id' => $data['formulario_id'] ?? $existingMetric->formulario_id,
            ]);

            return $existingMetric;
        } else {
            // Crear nueva métrica
            return static::create($data);
        }
    }
}
