<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramChat;
use App\Models\Zona;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

class TelegramStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:status';

    /**
     * The console command description.
     */
    protected $description = 'Muestra el estado completo del sistema de Telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Estado del Sistema de Telegram I-Free');
        $this->line('══════════════════════════════════════════');

        // Configuración
        $this->line('');
        $this->line('⚙️  <comment>Configuración:</comment>');

        $token = config('app.telegram_bot_token', env('TELEGRAM_BOT_TOKEN'));
        $enabled = config('app.telegram_bot_enabled', env('TELEGRAM_BOT_ENABLED'));

        $this->line("Token configurado: " . ($token ? '✅ Sí' : '❌ No'));
        $this->line("Sistema habilitado: " . ($enabled ? '✅ Sí' : '❌ No'));

        // Bots de Telegraph
        $this->line('');
        $this->line('🤖 <comment>Bots de Telegraph:</comment>');

        $telegraphBots = TelegraphBot::count();
        $this->line("Total de bots: <info>{$telegraphBots}</info>");

        if ($telegraphBots > 0) {
            TelegraphBot::all()->each(function ($bot) {
                $this->line("  • {$bot->name} (ID: {$bot->id})");
            });
        }

        // Chats registrados (sistema propio)
        $this->line('');
        $this->line('💬 <comment>Chats Registrados (Sistema Propio):</comment>');

        $chats = TelegramChat::with('zonas')->get();
        $this->line("Total de chats: <info>{$chats->count()}</info>");

        if ($chats->count() > 0) {
            $chatsByType = $chats->groupBy('tipo');

            foreach ($chatsByType as $tipo => $chatsOfType) {
                $icon = match($tipo) {
                    'private' => '💬',
                    'group' => '👥',
                    'supergroup' => '🏢',
                    'channel' => '📢',
                    default => '❓'
                };

                $this->line("  {$icon} {$tipo}: {$chatsOfType->count()}");
            }

            $this->line('');
            $activeChats = $chats->where('activo', true)->count();
            $this->line("Chats activos: <info>{$activeChats}</info>");
        }

        // Chats de Telegraph
        $this->line('');
        $this->line('📱 <comment>Chats de Telegraph:</comment>');

        $telegraphChats = TelegraphChat::count();
        $this->line("Total de chats Telegraph: <info>{$telegraphChats}</info>");

        // Zonas
        $this->line('');
        $this->line('📍 <comment>Zonas del Sistema:</comment>');

        $zonas = Zona::with('telegramChats')->get();
        $this->line("Total de zonas: <info>{$zonas->count()}</info>");

        if ($zonas->count() > 0) {
            $zonasConChats = $zonas->filter(fn($zona) => $zona->telegramChats->count() > 0);
            $this->line("Zonas con chats asociados: <info>{$zonasConChats->count()}</info>");

            if ($zonasConChats->count() > 0) {
                $this->line('');
                $this->line('📋 <comment>Detalle de asociaciones:</comment>');

                foreach ($zonasConChats as $zona) {
                    $this->line("  • {$zona->nombre}: {$zona->telegramChats->count()} chat(s)");
                }
            }
        }

        // Estadísticas de asociaciones
        $this->line('');
        $this->line('📈 <comment>Estadísticas:</comment>');

        $totalAsociaciones = \DB::table('telegram_chat_zona')->count();
        $this->line("Total de asociaciones chat-zona: <info>{$totalAsociaciones}</info>");

        // Estado de colas
        $this->line('');
        $this->line('⚡ <comment>Estado de Colas:</comment>');

        $jobsPendientes = \DB::table('jobs')->count();
        $jobsFallidos = \DB::table('failed_jobs')->count();

        $this->line("Jobs pendientes: <info>{$jobsPendientes}</info>");
        $this->line("Jobs fallidos: " . ($jobsFallidos > 0 ? "<error>{$jobsFallidos}</error>" : "<info>0</info>"));

        // Recomendaciones
        $this->line('');
        $this->line('💡 <comment>Recomendaciones:</comment>');

        if (!$token) {
            $this->line("  • Configurar TELEGRAM_BOT_TOKEN en .env");
        }

        if ($telegraphBots === 0) {
            $this->line("  • Crear bot: php artisan telegram:create-bot");
        }

        if ($chats->count() === 0) {
            $this->line("  • Los chats se registrarán automáticamente al enviar /start al bot");
        }

        if ($totalAsociaciones === 0 && $chats->count() > 0) {
            $this->line("  • Asociar chats con zonas usando /registrar [zona_id] en el bot");
        }

        if ($jobsPendientes > 0) {
            $this->line("  • Ejecutar: php artisan queue:work para procesar notificaciones");
        }

        $this->line('');
        $this->line('═══════════════════════════════════════════');
        $this->info('✅ Estado del sistema mostrado exitosamente');

        return 0;
    }
}
