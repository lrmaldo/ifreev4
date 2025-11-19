<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zona;

class DiagnosticoZonas extends Command
{
    protected $signature = 'diagnostico:zonas {--id=}';
    protected $description = 'Diagnóstico de zonas disponibles';

    public function handle()
    {
        $this->info('=== DIAGNÓSTICO DE ZONAS ===');

        try {
            $totalZonas = Zona::count();
            $this->info("Total de zonas: $totalZonas");

            if ($this->option('id')) {
                $id = $this->option('id');
                $this->info("\nBuscando zona ID: $id");
                
                // Buscar por ID normal
                $zona = Zona::find($id);
                if ($zona) {
                    $this->info("✅ Zona encontrada por ID:");
                    $this->line("   - ID: {$zona->id}");
                    $this->line("   - Nombre: {$zona->nombre}");
                    $this->line("   - Activa: " . ($zona->activo ? 'Sí' : 'No'));
                    $this->line("   - ID personalizado: " . ($zona->id_personalizado ?? 'N/A'));
                } else {
                    $this->error("❌ Zona con ID $id no encontrada");
                }

                // Buscar por ID personalizado
                $zonaPorIdPersonalizado = Zona::where('id_personalizado', $id)->first();
                if ($zonaPorIdPersonalizado) {
                    $this->info("✅ Zona encontrada por ID personalizado:");
                    $this->line("   - ID real: {$zonaPorIdPersonalizado->id}");
                    $this->line("   - Nombre: {$zonaPorIdPersonalizado->nombre}");
                    $this->line("   - Activa: " . ($zonaPorIdPersonalizado->activo ? 'Sí' : 'No'));
                }
            }

            $this->info("\n=== TODAS LAS ZONAS ===");
            $zonas = Zona::orderBy('id')->get();
            
            if ($zonas->count() > 0) {
                foreach ($zonas as $zona) {
                    $estado = $zona->activo ? '✅' : '❌';
                    $idPersonalizado = $zona->id_personalizado ? " (ID personalizado: {$zona->id_personalizado})" : "";
                    $this->line("{$estado} ID: {$zona->id} - {$zona->nombre}{$idPersonalizado}");
                }
            } else {
                $this->error("No hay zonas registradas");
            }

        } catch (\Exception $e) {
            $this->error("ERROR: " . $e->getMessage());
        }
    }
}