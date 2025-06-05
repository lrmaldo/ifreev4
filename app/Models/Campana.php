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
        $diaNombre = strtolower($hoy->locale('es')->dayName); // Nombre del día en español
        
        \Log::info("Aplicando scope activas, fecha: {$hoy}, día de semana: {$diaSemanaActual}, nombre día: {$diaNombre}");
        
        // Primero verificar las condiciones críticas de visibilidad
        $campanasIds = $query->pluck('id', 'id')->toArray();
        if (!empty($campanasIds)) {
            \Log::info("Verificando visibilidad para " . count($campanasIds) . " campañas");
        }

        return $query->where('visible', true) // Solo campañas marcadas como visibles
                    ->where(function($query) use ($hoy, $diaSemanaActual, $diaNombre) {
                        // Campañas siempre visibles
                        $query->where('siempre_visible', true)
                        
                        // O campañas con fechas y días específicos
                        ->orWhere(function($query) use ($hoy, $diaSemanaActual, $diaNombre) {
                            $query->where(function($q) use ($hoy) {
                                // Fechas nulas o dentro del rango
                                $q->whereNull('fecha_inicio')
                                  ->orWhere('fecha_inicio', '<=', $hoy);
                            })
                            ->where(function($q) use ($hoy) {
                                // Fechas nulas o dentro del rango
                                $q->whereNull('fecha_fin')
                                  ->orWhere('fecha_fin', '>=', $hoy);
                            })
                            ->where(function($q) use ($diaSemanaActual, $diaNombre) {
                                // Sin restricción de días o incluye el día actual
                                // Usamos una solución compatible con SQLite y MySQL
                                $q->whereNull('dias_visibles')
                                  ->orWhere('dias_visibles', 'like', '%"' . $diaSemanaActual . '"%')
                                  ->orWhere('dias_visibles', 'like', '%"' . $diaNombre . '"%');
                            });
                        });
                    });
    }
}
