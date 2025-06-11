<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormResponse;
use App\Models\FormField;
use App\Models\Zona;

class CorregirRespuestasFormularioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formulario:corregir-respuestas {--zona=} {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige y optimiza el formato de respuestas de formularios existentes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $zonaId = $this->option('zona');
        $limit = $this->option('limit');
        
        $query = FormResponse::query();
        
        if ($zonaId) {
            $query->where('zona_id', $zonaId);
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $total = $query->count();
        
        if ($total === 0) {
            $this->info('No se encontraron respuestas para procesar.');
            return 0;
        }
        
        $this->info("Se encontraron {$total} respuestas para procesar.");
        
        if (!$this->confirm('¿Desea continuar con el procesamiento?')) {
            $this->info('Operación cancelada.');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $procesadas = 0;
        $errores = 0;
        
        $query->chunkById(100, function($respuestas) use (&$procesadas, &$errores, $bar) {
            foreach ($respuestas as $respuesta) {
                try {
                    // Cargar zona y campos necesarios
                    $respuesta->load(['zona', 'zona.campos', 'zona.campos.opciones']);
                    
                    // Procesar y verificar formato de respuestas
                    if ($respuesta->respuestas) {
                        $respuestasProcesadas = $this->procesarRespuestas($respuesta);
                        
                        // Solo actualizar si hay cambios
                        if ($respuestasProcesadas !== $respuesta->respuestas) {
                            $respuesta->respuestas = $respuestasProcesadas;
                            $respuesta->save();
                        }
                    }
                    
                    $procesadas++;
                } catch (\Exception $e) {
                    $errores++;
                    $this->error("Error procesando respuesta ID {$respuesta->id}: " . $e->getMessage());
                }
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        $this->info("Procesamiento completado: {$procesadas} respuestas procesadas, {$errores} errores.");
        
        return 0;
    }
    
    /**
     * Procesa y optimiza el formato de respuestas
     */
    protected function procesarRespuestas(FormResponse $respuesta)
    {
        $respuestasOriginales = $respuesta->respuestas;
        $zona = $respuesta->zona;
        
        if (!$zona) {
            return $respuestasOriginales;
        }
        
        $respuestasProcesadas = [];
        $intereses = [];
        
        foreach ($respuestasOriginales as $campoKey => $valor) {
            // Si es un objeto o array como intereses, procesarlo especialmente
            if (is_array($valor) || is_object($valor)) {
                foreach ((array)$valor as $subCampo => $subValor) {
                    // Si es un checkbox, estandarizar el valor
                    $campo = $zona->campos->where('campo', $subCampo)->first();
                    if ($campo && $campo->tipo === 'checkbox') {
                        $intereses[$subCampo] = $this->normalizarValorBooleano($subValor);
                    } else {
                        $intereses[$subCampo] = $subValor;
                    }
                }
                
                // Solo agregar intereses si hay alguno
                if (!empty($intereses)) {
                    $respuestasProcesadas['intereses'] = $intereses;
                }
            } else {
                // Campos normales
                $campo = $zona->campos->where('campo', $campoKey)->first();
                
                if ($campo && $campo->tipo === 'checkbox') {
                    $respuestasProcesadas[$campoKey] = $this->normalizarValorBooleano($valor);
                } else {
                    $respuestasProcesadas[$campoKey] = $valor;
                }
            }
        }
        
        return $respuestasProcesadas;
    }
    
    /**
     * Normaliza un valor booleano a '1' o '0'
     */
    protected function normalizarValorBooleano($valor)
    {
        if ($valor === true || $valor === 1 || $valor === '1' || strtolower($valor) === 'true' || strtolower($valor) === 'sí' || strtolower($valor) === 'si' || strtolower($valor) === 'yes') {
            return '1';
        } else {
            return '0';
        }
    }
}
