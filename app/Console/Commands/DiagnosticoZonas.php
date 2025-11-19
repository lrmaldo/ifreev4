<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zona;
use Illuminate\Support\Facades\Schema;

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

            // Detectar el campo de estado
            $campoEstado = $this->detectarCampoEstado();

            if ($this->option('id')) {
                $id = $this->option('id');
                $this->info("\nBuscando zona ID: $id");

                // Buscar por ID normal
                $zona = Zona::find($id);
                if ($zona) {
                    $this->info("✅ Zona encontrada por ID:");
                    $this->line("   - ID: {$zona->id}");
                    $this->line("   - Nombre: {$zona->nombre}");

                    if ($campoEstado) {
                        $estadoValor = $zona->{$campoEstado};
                        $estado = $this->interpretarEstado($estadoValor);
                        $this->line("   - Estado ($campoEstado): $estado");
                    } else {
                        $this->line("   - Estado: No determinado (campo de estado no encontrado)");
                    }

                    $this->line("   - ID personalizado: " . ($zona->id_personalizado ?? 'N/A'));
                } else {
                    $this->error("❌ Zona con ID $id no encontrada");
                }

                // Buscar por ID personalizado
                $zonaPorIdPersonalizado = Zona::where('id_personalizado', $id)->first();
                if ($zonaPorIdPersonalizado && $zonaPorIdPersonalizado->id != ($zona->id ?? null)) {
                    $this->info("✅ Zona encontrada por ID personalizado:");
                    $this->line("   - ID real: {$zonaPorIdPersonalizado->id}");
                    $this->line("   - Nombre: {$zonaPorIdPersonalizado->nombre}");

                    if ($campoEstado) {
                        $estadoValor = $zonaPorIdPersonalizado->{$campoEstado};
                        $estado = $this->interpretarEstado($estadoValor);
                        $this->line("   - Estado ($campoEstado): $estado");
                    }
                }
            }

            $this->info("\n=== TODAS LAS ZONAS ===");
            $zonas = Zona::orderBy('id')->get();

            if ($zonas->count() > 0) {
                foreach ($zonas as $zona) {
                    if ($campoEstado) {
                        $estadoValor = $zona->{$campoEstado};
                        $esActiva = $this->esZonaActiva($estadoValor);
                        $estado = $esActiva ? '✅' : '❌';
                    } else {
                        $estado = '❓'; // No se puede determinar
                    }

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

    private function detectarCampoEstado()
    {
        $campos = Schema::getColumnListing('zonas');
        $posiblesCampos = ['activo', 'active', 'enabled', 'estado', 'status', 'habilitado'];

        foreach ($posiblesCampos as $campo) {
            if (in_array($campo, $campos)) {
                return $campo;
            }
        }

        return null;
    }

    private function interpretarEstado($valor)
    {
        if (is_bool($valor)) {
            return $valor ? 'Activa' : 'Inactiva';
        }

        if (is_numeric($valor)) {
            return $valor == 1 ? 'Activa' : 'Inactiva';
        }

        if (is_string($valor)) {
            $valor = strtolower($valor);
            if (in_array($valor, ['activo', 'active', 'enabled', '1', 'true', 'si', 'yes'])) {
                return 'Activa';
            }
            if (in_array($valor, ['inactivo', 'inactive', 'disabled', '0', 'false', 'no'])) {
                return 'Inactiva';
            }
        }

        return "Desconocido ($valor)";
    }

    private function esZonaActiva($valor)
    {
        if (is_bool($valor)) {
            return $valor;
        }

        if (is_numeric($valor)) {
            return $valor == 1;
        }

        if (is_string($valor)) {
            $valor = strtolower($valor);
            return in_array($valor, ['activo', 'active', 'enabled', '1', 'true', 'si', 'yes']);
        }

        return false;
    }
}
