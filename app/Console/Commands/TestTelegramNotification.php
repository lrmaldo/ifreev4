<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HotspotMetric;
use App\Models\Zona;
use App\Services\TelegramNotificationService;

class TestTelegramNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {--zona_id= : ID de la zona para simular}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba las notificaciones de Telegram creando una métrica de hotspot simulada';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando prueba de notificaciones Telegram...');

        // Obtener zona
        $zonaId = $this->option('zona_id');

        if ($zonaId) {
            $zona = Zona::find($zonaId);
            if (!$zona) {
                $this->error("Zona con ID {$zonaId} no encontrada.");
                return 1;
            }
        } else {
            $zona = Zona::first();
            if (!$zona) {
                $this->error('No hay zonas disponibles en el sistema.');
                return 1;
            }
        }

        $this->info("📍 Usando zona: {$zona->nombre} (ID: {$zona->id})");

        // Verificar si la zona tiene chats de Telegram configurados
        $telegramChats = $zona->telegramChats()->activos()->get();

        if ($telegramChats->isEmpty()) {
            $this->warn('⚠️  Esta zona no tiene chats de Telegram configurados.');
            $this->info('   Configura chats de Telegram en el panel administrativo.');
            return 1;
        }

        $this->info("📱 Chats configurados: {$telegramChats->count()}");

        // Crear métrica de prueba
        $this->info('📊 Creando métrica de prueba...');

        $testMetric = HotspotMetric::create([
            'zona_id' => $zona->id,
            'mac_address' => '00:11:22:33:44:55',
            'formulario_id' => null,
            'dispositivo' => 'iPhone 15 Pro',
            'navegador' => 'Safari 17.0',
            'sistema_operativo' => 'iOS 17.1',
            'tipo_visual' => 'completa',
            'duracion_visual' => 120,
            'clic_boton' => true,
            'veces_entradas' => 1,
        ]);

        $this->info("✅ Métrica de prueba creada (ID: {$testMetric->id})");
        $this->info('📤 Las notificaciones se enviarán automáticamente vía evento...');

        // Probar servicio directamente también
        $this->info('🔧 Probando servicio directamente...');

        try {
            $telegramService = new TelegramNotificationService();
            $telegramService->notifyNewHotspotMetric($testMetric);
            $this->info('✅ Notificación enviada exitosamente');
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar notificación: ' . $e->getMessage());
        }

        $this->info('🎉 Prueba completada');

        return 0;
    }
}
