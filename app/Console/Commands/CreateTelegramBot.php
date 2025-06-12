<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

class CreateTelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:create-bot {--name=I-Free Bot : Nombre del bot}';

    /**
     * The console command description.
     */
    protected $description = 'Crea un bot de Telegraph y configura el webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('app.telegram_bot_token', env('TELEGRAM_BOT_TOKEN'));

        if (!$token) {
            $this->error('❌ Token de Telegram no configurado en TELEGRAM_BOT_TOKEN');
            return 1;
        }

        $botName = $this->option('name');

        // Verificar si ya existe un bot con este token
        $existingBot = TelegraphBot::where('token', $token)->first();

        if ($existingBot) {
            $this->info("✅ Bot ya existe: {$existingBot->name}");
            $bot = $existingBot;
        } else {
            // Crear nuevo bot
            $bot = TelegraphBot::create([
                'token' => $token,
                'name' => $botName,
            ]);

            $this->info("✅ Bot creado: {$botName}");
        }

        // Configurar webhook
        $webhookUrl = $this->ask('Ingresa la URL del webhook (ej: https://tudominio.com/telegram/webhook)');

        if ($webhookUrl) {
            try {
                $bot->registerWebhook($webhookUrl)->send();
                $this->info("✅ Webhook configurado: {$webhookUrl}");
            } catch (\Exception $e) {
                $this->error("❌ Error configurando webhook: " . $e->getMessage());
            }
        }

        // Configurar comandos usando la API directa
        try {
            $commands = [
                ['command' => 'start', 'description' => 'Inicializar el bot y mostrar bienvenida'],
                ['command' => 'zonas', 'description' => 'Listar zonas disponibles del sistema'],
                ['command' => 'registrar', 'description' => 'Asociar chat con una zona específica'],
                ['command' => 'ayuda', 'description' => 'Mostrar ayuda y comandos disponibles']
            ];

            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
                'commands' => json_encode($commands)
            ]);

            // Forma alternativa si la anterior no funciona
            if (!$response->successful()) {
                $this->warn("Intentando configurar comandos con formato alternativo...");

                $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
                    'commands' => $commands
                ]);
            }

            if ($response->successful()) {
                $this->info("✅ Comandos configurados");
            } else {
                $this->warn("⚠️  Error configurando comandos: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->warn("⚠️  Error configurando comandos: " . $e->getMessage());
        }

        // Mostrar información del bot
        $this->line('');
        $this->line('📊 <comment>Información del Bot:</comment>');
        $this->line("ID: {$bot->id}");
        $this->line("Nombre: {$bot->name}");
        $this->line("Token: " . substr($bot->token, 0, 10) . "...");

        $this->line('');
        $this->info('🎉 Bot configurado exitosamente');
        $this->line('💡 Usa el webhook URL en tu bot de Telegram: ' . ($webhookUrl ?? 'No configurado'));

        return 0;
    }
}
