<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use App\Models\Zona;

class TestTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:test-webhook {chat_id : ID del chat para probar}';

    /**
     * The console command description.
     */
    protected $description = 'Prueba el webhook enviando un mensaje de test';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chatId = $this->argument('chat_id');

        // Buscar el bot
        $bot = TelegraphBot::first();

        if (!$bot) {
            $this->error('❌ No hay bots configurados. Usa: php artisan telegram:create-bot');
            return 1;
        }

        $this->info("🤖 Usando bot: {$bot->name}");

        try {
            // Buscar o crear el chat
            $chat = TelegraphChat::firstOrCreate([
                'chat_id' => $chatId,
                'telegraph_bot_id' => $bot->id,
            ], [
                'name' => 'Test Chat'
            ]);

            $this->info("💬 Chat encontrado/creado: {$chat->name}");

            // Enviar mensaje de prueba
            $message = "🧪 <b>Mensaje de Prueba</b>\n\n";
            $message .= "Bot funcionando correctamente desde Laravel 12\n";
            $message .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n\n";

            $zonas = Zona::count();
            $message .= "📊 Zonas en sistema: <b>{$zonas}</b>\n\n";
            $message .= "Comandos disponibles:\n";
            $message .= "/start - Inicializar\n";
            $message .= "/zonas - Ver zonas\n";
            $message .= "/registrar [zona_id] - Asociar zona\n";
            $message .= "/ayuda - Mostrar ayuda";

            $chat->message($message)->send();

            $this->info("✅ Mensaje de prueba enviado al chat: {$chatId}");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
