<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campana extends Model
{
    protected $table = 'campanas';

    protected $fillable = [
        'titulo',
        'descripcion',
        'enlace',
        'fecha_inicio',
        'fecha_fin',
        'visible',
        'prioridad',
        'siempre_visible',
        'dias_visibles',
        'tipo',
        'archivo_path',
        'cliente_id'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'visible' => 'boolean',
        'siempre_visible' => 'boolean',
        'dias_visibles' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtiene el cliente asociado a esta campaña
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Obtiene las zonas asociadas a esta campaña
     */
    public function zonas()
    {
        return $this->belongsToMany(Zona::class, 'campana_zona')
                    ->withTimestamps();
    }

    /**
     * Scope para obtener solo las campañas activas
     */
    public function scopeActivas($query)
    {
        $hoy = now();
        $diaSemanaActual = $hoy->dayOfWeek; // 0 (domingo) hasta 6 (sábado)

        return $query->where('visible', true)
                    ->where(function($query) use ($hoy, $diaSemanaActual) {
                        // Campañas siempre visibles
                        $query->where('siempre_visible', true)

                        // O campañas con fechas y días específicos
                        ->orWhere(function($query) use ($hoy, $diaSemanaActual) {
                            $query->where('fecha_inicio', '<=', $hoy)
                                  ->where('fecha_fin', '>=', $hoy)
                                  ->where(function($query) use ($diaSemanaActual) {
                                      // Sin restricción de días o incluye el día actual
                                      $query->whereNull('dias_visibles')
                                            ->orWhereJsonContains('dias_visibles', (string)$diaSemanaActual);
                                  });
                        });
                    });
    }
}
