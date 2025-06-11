<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormResponse;

class VerificarRespuestasFormularioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formulario:verificar-respuestas {id? : ID específico de respuesta a verificar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y muestra las respuestas de formularios formateadas correctamente';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');

        if ($id) {
            // Verificar una respuesta específica
            $respuesta = FormResponse::find($id);

            if (!$respuesta) {
                $this->error("No se encontró la respuesta con ID: {$id}");
                return 1;
            }

            $this->mostrarRespuesta($respuesta);
        } else {
            // Verificar todas las respuestas
            $count = FormResponse::count();

            if ($count === 0) {
                $this->info('No hay respuestas de formulario para verificar.');
                return 0;
            }

            $this->info("Se encontraron {$count} respuestas.");

            if ($this->confirm('¿Quieres mostrar todas las respuestas? (Puede ser mucha información)')) {
                $bar = $this->output->createProgressBar($count);
                $bar->start();

                FormResponse::chunk(10, function ($respuestas) use ($bar) {
                    foreach ($respuestas as $respuesta) {
                        $this->mostrarRespuesta($respuesta);
                        $bar->advance();
                    }
                });

                $bar->finish();
                $this->newLine(2);
            } else {
                // Mostrar solo algunas para muestra
                $limit = $this->ask('¿Cuántas respuestas quieres mostrar como muestra?', 5);
                $respuestas = FormResponse::take($limit)->get();

                foreach ($respuestas as $respuesta) {
                    $this->mostrarRespuesta($respuesta);
                }
            }
        }

        return 0;
    }

    /**
     * Muestra la información de una respuesta de formulario
     */
    protected function mostrarRespuesta(FormResponse $respuesta)
    {
        $this->newLine();
        $this->info("=== Respuesta ID: {$respuesta->id} ===");
        $this->info("Zona: " . ($respuesta->zona ? $respuesta->zona->nombre : 'Desconocida'));
        $this->info("MAC: {$respuesta->mac_address}");
        $this->info("Fecha: {$respuesta->created_at}");

        $this->newLine();
        $this->line('Respuestas JSON:');
        $this->line(json_encode($respuesta->respuestas, JSON_PRETTY_PRINT));

        $this->newLine();
        $this->line('Respuestas Formateadas:');

        $respuestasFormateadas = $respuesta->respuestas_formateadas;

        if (!empty($respuestasFormateadas)) {
            $headers = ['Etiqueta', 'Valor'];
            $rows = [];

            foreach ($respuestasFormateadas as $campo) {
                $rows[] = [
                    $campo['etiqueta'],
                    is_array($campo['valor']) ? json_encode($campo['valor']) : $campo['valor']
                ];
            }

            $this->table($headers, $rows);
        } else {
            $this->warn('No se encontraron respuestas formateadas.');
        }

        $this->newLine();
    }
}
