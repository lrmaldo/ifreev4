<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class VerificarEstructuraZonas extends Command
{
    protected $signature = 'zona:estructura';
    protected $description = 'Verificar la estructura de la tabla zonas';

    public function handle()
    {
        $this->info('=== ESTRUCTURA DE LA TABLA ZONAS ===');

        try {
            // Obtener columnas de la tabla
            $columns = Schema::getColumnListing('zonas');

            $this->info("Columnas disponibles en la tabla 'zonas':");
            foreach ($columns as $column) {
                $this->line("  - $column");
            }

            // Obtener información detallada de las columnas
            $this->info("\n=== INFORMACIÓN DETALLADA DE COLUMNAS ===");

            $columnDetails = DB::select("DESCRIBE zonas");

            foreach ($columnDetails as $detail) {
                $this->line("Columna: {$detail->Field}");
                $this->line("  Tipo: {$detail->Type}");
                $this->line("  Null: {$detail->Null}");
                $this->line("  Clave: {$detail->Key}");
                $this->line("  Por defecto: " . ($detail->Default ?? 'NULL'));
                $this->line("  Extra: {$detail->Extra}");
                $this->line("---");
            }

            // Buscar campos similares a 'activo'
            $this->info("\n=== CAMPOS RELACIONADOS CON ESTADO ===");
            $possibleFields = ['activo', 'active', 'enabled', 'estado', 'status', 'habilitado'];

            foreach ($possibleFields as $field) {
                if (in_array($field, $columns)) {
                    $this->info("✅ Campo encontrado: $field");

                    // Obtener valores únicos para este campo
                    $values = DB::table('zonas')->distinct()->pluck($field);
                    $this->line("  Valores únicos: " . $values->implode(', '));
                } else {
                    $this->line("❌ Campo no encontrado: $field");
                }
            }

            // Mostrar una zona de ejemplo
            $this->info("\n=== EJEMPLO DE REGISTRO ===");
            $zona = DB::table('zonas')->first();
            if ($zona) {
                foreach ($zona as $campo => $valor) {
                    $this->line("$campo: $valor");
                }
            }

        } catch (\Exception $e) {
            $this->error("Error al verificar la estructura: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
