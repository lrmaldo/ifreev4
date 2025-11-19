<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Zona;

class ProbarZona extends Command
{
    protected $signature = 'zona:probar {id}';
    protected $description = 'Probar acceso a una zona específica';

    public function handle()
    {
        $id = $this->argument('id');

        $this->info("=== PRUEBA DE ZONA $id ===");

        try {
            // Buscar por ID normal
            $zona = Zona::find($id);

            if ($zona) {
                $this->info("✅ Zona encontrada:");
                $this->line("   - ID: {$zona->id}");
                $this->line("   - Nombre: {$zona->nombre}");
                $this->line("   - Usuario ID: {$zona->user_id}");
                $this->line("   - Tipo registro: {$zona->tipo_registro}");
                $this->line("   - ID personalizado: " . ($zona->id_personalizado ?? 'N/A'));

                // Probar URL
                $this->info("\n📍 URLs disponibles:");
                $this->line("   - Principal: /login_formulario/{$zona->id}");
                if ($zona->id_personalizado && $zona->id_personalizado != $zona->id) {
                    $this->line("   - Alternativa: /login_formulario/{$zona->id_personalizado}");
                }

                // Verificar user
                if ($zona->user) {
                    $this->line("\n👤 Usuario propietario:");
                    $this->line("   - Nombre: {$zona->user->name}");
                    $this->line("   - Email: {$zona->user->email}");
                } else {
                    $this->error("\n❌ Usuario propietario no encontrado (ID: {$zona->user_id})");
                }

                // Verificar campañas
                $campanas = $zona->campanas;
                $this->line("\n🎯 Campañas asociadas: {$campanas->count()}");

                if ($campanas->count() > 0) {
                    foreach ($campanas as $campana) {
                        $this->line("   - {$campana->titulo} (ID: {$campana->id})");
                    }
                }

                $this->info("\n🎉 La zona está completamente configurada y lista para usar.");

            } else {
                $this->error("❌ Zona con ID $id no encontrada");

                // Buscar por ID personalizado
                $zonaPorIdPersonalizado = Zona::where('id_personalizado', $id)->first();
                if ($zonaPorIdPersonalizado) {
                    $this->info("\n✅ Encontrada zona con ID personalizado '$id':");
                    $this->line("   - ID real: {$zonaPorIdPersonalizado->id}");
                    $this->line("   - Nombre: {$zonaPorIdPersonalizado->nombre}");
                    $this->line("   - URL: /login_formulario/{$zonaPorIdPersonalizado->id}");
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Error al probar la zona: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
