<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormResponse extends Model
{
    protected $table = 'form_responses';

    protected $fillable = [
        'zona_id',
        'mac_address',
        'dispositivo',
        'navegador',
        'tiempo_activo',
        'formulario_completado',
        'respuestas'
    ];

    protected $casts = [
        'formulario_completado' => 'boolean',
        'tiempo_activo' => 'integer',
        'respuestas' => 'array'
    ];

    /**
     * Relación con zona
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    /**
     * Obtener respuestas formateadas para mostrar
     */
    public function getRespuestasFormateadasAttribute()
    {
        $respuestas = $this->respuestas;
        $zona = $this->zona;

        if (!$zona || !$respuestas) {
            return [];
        }

        $campos = $zona->campos;
        $formateadas = [];

        foreach ($respuestas as $campoId => $valor) {
            $campo = $campos->find($campoId);
            if ($campo) {
                $formateadas[] = [
                    'etiqueta' => $campo->etiqueta,
                    'valor' => $this->formatearValor($campo, $valor)
                ];
            }
        }

        return $formateadas;
    }

    /**
     * Formatear el valor según el tipo de campo
     */
    private function formatearValor($campo, $valor)
    {
        if ($campo->tipo === 'checkbox' && is_array($valor)) {
            return implode(', ', $valor);
        }

        if (in_array($campo->tipo, ['select', 'radio']) && $campo->opciones->count() > 0) {
            $opcion = $campo->opciones->find($valor);
            return $opcion ? $opcion->texto : $valor;
        }

        return $valor;
    }
}
