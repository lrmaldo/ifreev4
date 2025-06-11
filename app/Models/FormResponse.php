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
     * Relación con las métricas de hotspot
     */
    public function metricas()
    {
        return $this->hasMany(HotspotMetric::class, 'formulario_id');
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

        foreach ($respuestas as $campoKey => $valor) {
            // Buscar el campo por su nombre (campo)
            $campo = $campos->where('campo', $campoKey)->first();

            if ($campo) {
                $formateadas[] = [
                    'etiqueta' => $campo->etiqueta,
                    'valor' => $this->formatearValor($campo, $valor)
                ];
            } else {
                // Si es un campo anidado (como intereses)
                if (is_array($valor) || is_object($valor)) {
                    foreach ($valor as $subCampo => $subValor) {
                        $campoSubCampo = $campos->where('campo', $subCampo)->first();
                        if ($campoSubCampo) {
                            $formateadas[] = [
                                'etiqueta' => $campoSubCampo->etiqueta,
                                'valor' => $this->formatearValor($campoSubCampo, $subValor)
                            ];
                        } else {
                            // Si no se encuentra el campo, mostrar con la clave como etiqueta
                            $formateadas[] = [
                                'etiqueta' => ucfirst($subCampo),
                                'valor' => $subValor
                            ];
                        }
                    }
                } else {
                    // Agregar al listado sin formato especial (clave como etiqueta)
                    $formateadas[] = [
                        'etiqueta' => ucfirst($campoKey),
                        'valor' => $valor
                    ];
                }
            }
        }

        return $formateadas;
    }

    /**
     * Formatear el valor según el tipo de campo
     */
    private function formatearValor($campo, $valor)
    {
        // Manejar casillas de verificación (checkbox)
        if ($campo->tipo === 'checkbox') {
            if (is_array($valor)) {
                // Si es un array de valores seleccionados
                $opciones = [];
                foreach ($valor as $key => $val) {
                    if ($val === '1' || $val === 1 || $val === true) {
                        $opcion = $campo->opciones->where('valor', $key)->first();
                        $opciones[] = $opcion ? $opcion->etiqueta : ucfirst($key);
                    }
                }
                return !empty($opciones) ? implode(', ', $opciones) : 'No seleccionado';
            } else {
                // Si es un único valor booleano
                return ($valor === '1' || $valor === 1 || $valor === true || $valor === 'true') ? 'Sí' : 'No';
            }
        }

        // Manejar selects y radios
        if (in_array($campo->tipo, ['select', 'radio']) && $campo->opciones->count() > 0) {
            $opcion = $campo->opciones->where('valor', $valor)->first();
            return $opcion ? $opcion->etiqueta : $valor;
        }

        return $valor;
    }
}
