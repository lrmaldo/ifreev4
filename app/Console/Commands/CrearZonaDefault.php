<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zona;

class CrearZonaDefault extends Command
{
    protected $signature = 'zona:crear-default {--id=10} {--nombre=} {--activa=true}';
    protected $description = 'Crear una zona por defecto para evitar errores 404';

    public function handle()
    {
        $id = $this->option('id');
        $nombre = $this->option('nombre') ?: "Zona WiFi $id";
        $activa = $this->option('activa') === 'true';

        try {
            // Verificar si ya existe
            $zonaExistente = Zona::find($id);
            if ($zonaExistente) {
                $this->error("Ya existe una zona con ID $id: {$zonaExistente->nombre}");
                return 1;
            }

            // Crear nueva zona
            $zona = new Zona();
            $zona->id = $id;
            $zona->nombre = $nombre;
            $zona->descripcion = "Zona creada automáticamente para evitar errores 404";
            $zona->tipo_registro = 'sin_registro'; // Sin formulario por defecto
            $zona->tiempo_visualizacion = 15;
            $zona->activo = $activa;
            $zona->save();

            $this->info("✅ Zona creada exitosamente:");
            $this->line("   - ID: {$zona->id}");
            $this->line("   - Nombre: {$zona->nombre}");
            $this->line("   - Activa: " . ($zona->activo ? 'Sí' : 'No'));
            $this->line("   - Tipo: {$zona->tipo_registro}");

            $this->info("\nAhora puedes acceder a: /login_formulario/$id");

        } catch (\Exception $e) {
            $this->error("Error al crear la zona: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
