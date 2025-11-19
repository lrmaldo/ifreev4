<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zona;

class ActivarZona extends Command
{
    protected $signature = 'zona:activar {id} {--all : Activar todas las zonas}';
    protected $description = 'Activar una zona específica o todas las zonas';

    public function handle()
    {
        if ($this->option('all')) {
            return $this->activarTodasLasZonas();
        }

        $id = $this->argument('id');

        try {
            // Buscar zona por ID o ID personalizado
            $zona = Zona::where('id', $id)
                       ->orWhere('id_personalizado', $id)
                       ->first();

            if (!$zona) {
                $this->error("❌ Zona con ID '$id' no encontrada");
                return 1;
            }

            if ($zona->activo) {
                $this->info("ℹ️ La zona '{$zona->nombre}' ya está activa");
                return 0;
            }

            $zona->activo = true;
            $zona->save();

            $this->info("✅ Zona activada exitosamente:");
            $this->line("   - ID: {$zona->id}");
            $this->line("   - Nombre: {$zona->nombre}");
            $this->line("   - ID personalizado: " . ($zona->id_personalizado ?? 'N/A'));
            $this->line("   - URL: /login_formulario/{$zona->id}");

            if ($zona->id_personalizado && $zona->id_personalizado != $zona->id) {
                $this->line("   - URL alternativa: /login_formulario/{$zona->id_personalizado}");
            }

        } catch (\Exception $e) {
            $this->error("Error al activar la zona: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function activarTodasLasZonas()
    {
        try {
            $zonasInactivas = Zona::where('activo', false)->get();

            if ($zonasInactivas->isEmpty()) {
                $this->info("ℹ️ Todas las zonas ya están activas");
                return 0;
            }

            $this->info("Activando " . $zonasInactivas->count() . " zonas...");

            foreach ($zonasInactivas as $zona) {
                $zona->activo = true;
                $zona->save();
                $this->line("✅ Activada: {$zona->nombre} (ID: {$zona->id})");
            }

            $this->info("\n🎉 Todas las zonas han sido activadas exitosamente");

        } catch (\Exception $e) {
            $this->error("Error al activar las zonas: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
