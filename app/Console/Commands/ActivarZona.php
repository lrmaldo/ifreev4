<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zona;
use Illuminate\Support\Facades\Schema;

class ActivarZona extends Command
{
    protected $signature = 'zona:activar {id} {--all : Activar todas las zonas}';
    protected $description = 'Activar una zona específica o todas las zonas';

    public function handle()
    {
        // Detectar el campo de estado correcto
        $campoEstado = $this->detectarCampoEstado();

        if (!$campoEstado) {
            $this->error("❌ No se pudo encontrar un campo de estado válido en la tabla zonas");
            $this->line("Campos disponibles: " . implode(', ', Schema::getColumnListing('zonas')));
            return 1;
        }

        $this->line("🔍 Usando campo de estado: $campoEstado");

        if ($this->option('all')) {
            return $this->activarTodasLasZonas($campoEstado);
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

            $estadoActual = $zona->{$campoEstado};
            if ($this->esZonaActiva($estadoActual)) {
                $this->info("ℹ️ La zona '{$zona->nombre}' ya está activa");
                return 0;
            }

            // Activar la zona
            $zona->{$campoEstado} = $this->obtenerValorActivo($campoEstado);
            $zona->save();

            $this->info("✅ Zona activada exitosamente:");
            $this->line("   - ID: {$zona->id}");
            $this->line("   - Nombre: {$zona->nombre}");
            $this->line("   - Campo actualizado: $campoEstado = " . $zona->{$campoEstado});
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

    private function activarTodasLasZonas($campoEstado)
    {
        try {
            // Buscar zonas inactivas
            $todasLasZonas = Zona::all();
            $zonasInactivas = $todasLasZonas->filter(function($zona) use ($campoEstado) {
                return !$this->esZonaActiva($zona->{$campoEstado});
            });

            if ($zonasInactivas->isEmpty()) {
                $this->info("ℹ️ Todas las zonas ya están activas");
                return 0;
            }

            $this->info("Activando " . $zonasInactivas->count() . " zonas...");

            foreach ($zonasInactivas as $zona) {
                $zona->{$campoEstado} = $this->obtenerValorActivo($campoEstado);
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

    private function obtenerValorActivo($campoEstado)
    {
        // Para la mayoría de casos, 1 o true funcionará
        // Pero podemos ser más específicos según el campo
        if (in_array($campoEstado, ['activo', 'habilitado'])) {
            return 1; // Para campos booleanos/enteros en español
        }

        if (in_array($campoEstado, ['active', 'enabled'])) {
            return 1; // Para campos booleanos/enteros en inglés
        }

        if ($campoEstado === 'estado') {
            return 'activo'; // Para campos de texto en español
        }

        if ($campoEstado === 'status') {
            return 'active'; // Para campos de texto en inglés
        }

        return 1; // Valor por defecto
    }
}
