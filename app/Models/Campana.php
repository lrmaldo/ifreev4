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
     * Estado para mostrar en el panel, con la misma lógica que scopeActivas():
     * oculta (visible = false), activa (siempre visible o dentro de fechas),
     * programada (aún no inicia) o vencida (ya terminó).
     *
     * @return array{clave: string, etiqueta: string, detalle: ?string}
     */
    public function getEstadoAttribute(): array
    {
        $hoy = now()->startOfDay();

        // Ojo: dentro del modelo $this->visible es la propiedad de serialización de Eloquent, no la columna
        if (!$this->getAttribute('visible')) {
            return ['clave' => 'oculta', 'etiqueta' => 'Oculta', 'detalle' => 'No se muestra en ningún portal'];
        }
        if ($this->siempre_visible) {
            return ['clave' => 'activa', 'etiqueta' => 'Activa', 'detalle' => 'Siempre visible'];
        }
        if ($this->fecha_inicio && $this->fecha_inicio->gt($hoy)) {
            return ['clave' => 'programada', 'etiqueta' => 'Programada', 'detalle' => 'Inicia ' . $this->fecha_inicio->locale('es')->isoFormat('D MMM')];
        }
        if ($this->fecha_fin && $this->fecha_fin->lt($hoy)) {
            return ['clave' => 'vencida', 'etiqueta' => 'Vencida', 'detalle' => 'Terminó ' . $this->fecha_fin->locale('es')->isoFormat('D MMM')];
        }

        $detalle = 'Hasta ' . $this->fecha_fin?->locale('es')->isoFormat('D MMM');
        if (!empty($this->dias_visibles)) {
            $nombres = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            $dias = collect($this->dias_visibles)->map(fn ($d) => $nombres[(int) $d] ?? null)->filter()->join(', ');
            $detalle .= " · solo {$dias}";
        }

        return ['clave' => 'activa', 'etiqueta' => 'Activa', 'detalle' => $detalle];
    }

    /** Visibles que aún no inician (y no son "siempre visibles") */
    public function scopeProgramadas($query)
    {
        return $query->where('visible', true)->where('siempre_visible', false)->where('fecha_inicio', '>', now()->toDateString());
    }

    /** Visibles que ya terminaron (y no son "siempre visibles") */
    public function scopeVencidas($query)
    {
        return $query->where('visible', true)->where('siempre_visible', false)->where('fecha_fin', '<', now()->toDateString());
    }

    /**
     * Scope para obtener solo las campañas activas
     */
    public function scopeActivas($query)
    {
        $hoy = now();
        $diaSemanaActual = $hoy->dayOfWeek; // 0 (domingo) hasta 6 (sábado)
        $diaNombre = strtolower($hoy->locale('es')->dayName); // Nombre del día en español

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
