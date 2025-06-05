<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campana;
use App\Models\Zona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificarRelacionesCampanas extends Command
{
    /**
     * El nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'campanas:verificar {--zona_id= : ID específico de zona a verificar} {--limpiar : Eliminar relaciones incorrectas}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Verifica y repara las relaciones entre campañas y zonas';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        $this->info('Verificando relaciones entre campañas y zonas...');

        $zonaId = $this->option('zona_id');
        $limpiar = $this->option('limpiar');

        // Verificar campañas activas generales
        $campanasActivas = Campana::activas()->get();
        $this->info("Hay {$campanasActivas->count()} campañas activas en el sistema");

        // Mostrar detalles de cada campaña
        foreach ($campanasActivas as $campana) {
            $this->line("ID: {$campana->id}, Título: {$campana->titulo}, Visible: " . ($campana->visible ? 'Sí' : 'No'));
            $this->line("Tipo: {$campana->tipo}, Archivo: {$campana->archivo_path}");
            $this->line("Fechas: " . ($campana->fecha_inicio ? $campana->fecha_inicio->format('Y-m-d') : 'Sin inicio') .
                        " a " . ($campana->fecha_fin ? $campana->fecha_fin->format('Y-m-d') : 'Sin fin'));
            $this->line("Siempre visible: " . ($campana->siempre_visible ? 'Sí' : 'No'));
            $this->line("-------------------------");
        }

        // Si se especificó una zona específica
        if ($zonaId) {
            $zona = Zona::find($zonaId);
            if (!$zona) {
                $this->error("No se encontró la zona con ID {$zonaId}");
                return 1;
            }

            $this->info("Verificando campañas activas para la zona {$zona->id} ({$zona->nombre})");
            $campanasActivasZona = $zona->getCampanasActivas();
            $this->info("Se encontraron {$campanasActivasZona->count()} campañas activas para esta zona");

            // Verificar relaciones en tabla pivot
            $relacionesPivot = DB::table('campana_zona')->where('zona_id', $zona->id)->get();
            $this->info("Relaciones en tabla pivot: {$relacionesPivot->count()}");

            // Mostrar detalles de cada relación
            foreach ($relacionesPivot as $relacion) {
                $campana = Campana::find($relacion->campana_id);
                $estadoActiva = $campana && $campanasActivas->contains($campana->id) ? 'ACTIVA' : 'INACTIVA';
                $this->line("- Campaña ID: {$relacion->campana_id}, Estado: {$estadoActiva}");
            }

            // Limpiar relaciones si se solicitó
            if ($limpiar) {
                $this->warn("Limpiando relaciones incorrectas...");

                // Eliminar relaciones con campañas inexistentes
                $eliminadas = 0;
                foreach ($relacionesPivot as $relacion) {
                    if (!Campana::find($relacion->campana_id)) {
                        DB::table('campana_zona')
                            ->where('zona_id', $zona->id)
                            ->where('campana_id', $relacion->campana_id)
                            ->delete();
                        $eliminadas++;
                    }
                }

                if ($eliminadas > 0) {
                    $this->info("Se eliminaron {$eliminadas} relaciones con campañas inexistentes");
                }

                // Crear relaciones faltantes con campañas activas
                $creadas = 0;
                foreach ($campanasActivas as $campana) {
                    if (!DB::table('campana_zona')
                            ->where('zona_id', $zona->id)
                            ->where('campana_id', $campana->id)
                            ->exists()) {
                        DB::table('campana_zona')->insert([
                            'zona_id' => $zona->id,
                            'campana_id' => $campana->id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $creadas++;
                    }
                }

                if ($creadas > 0) {
                    $this->info("Se crearon {$creadas} nuevas relaciones con campañas activas");
                }

                $this->info("Proceso de limpieza completado");
            }
        } else {
            // Verificar todas las zonas
            $zonas = Zona::all();
            $this->info("Verificando todas las zonas ({$zonas->count()})");

            foreach ($zonas as $zona) {
                $this->line("\nZONA: {$zona->id} - {$zona->nombre}");

                $campanasZona = $zona->campanas()->count();
                $campanasActivasZona = $zona->getCampanasActivas()->count();

                $this->line("- Campañas asociadas: {$campanasZona}");
                $this->line("- Campañas activas: {$campanasActivasZona}");

                if ($limpiar) {
                    // Crear relaciones faltantes para esta zona
                    $creadas = 0;
                    foreach ($campanasActivas as $campana) {
                        if (!DB::table('campana_zona')
                                ->where('zona_id', $zona->id)
                                ->where('campana_id', $campana->id)
                                ->exists()) {
                            DB::table('campana_zona')->insert([
                                'zona_id' => $zona->id,
                                'campana_id' => $campana->id,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                            $creadas++;
                        }
                    }

                    if ($creadas > 0) {
                        $this->line("  Se crearon {$creadas} nuevas relaciones");
                    }
                }
            }
        }

        $this->info("Verificación completada");
        return 0;
    }
}
