<?php

namespace App\Console\Commands;

use App\Models\Campana;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LimpiarCampanasCaducadas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campanas:limpiar-caducadas {--delete : Eliminar campañas caducadas en lugar de marcarlas como invisibles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca como invisibles o elimina las campañas que han caducado (fecha_fin < hoy)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $hoy = now()->startOfDay();

        // Obtener campañas caducadas que aún están visibles
        $query = Campana::where('visible', true)
                        ->where('fecha_fin', '<', $hoy);

        $total = $query->count();

        if ($total === 0) {
            $this->info('No hay campañas caducadas que procesar.');
            return 0;
        }

        if ($this->option('delete')) {
            // Eliminar campañas caducadas
            $campanas = $query->get();

            foreach ($campanas as $campana) {
                // Eliminar archivo asociado
                if ($campana->archivo_path && Storage::disk('public')->exists($campana->archivo_path)) {
                    Storage::disk('public')->delete($campana->archivo_path);
                }

                // Eliminar la campaña
                $campana->delete();
            }

            $this->info("Se han eliminado {$total} campañas caducadas y sus archivos asociados.");
        } else {
            // Marcar como invisibles
            $actualizadas = $query->update(['visible' => false]);
            $this->info("Se han marcado como invisibles {$actualizadas} campañas caducadas.");
        }

        return 0;
    }
}
