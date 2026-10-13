<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetupTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:setup-webhook {--url= : La URL del webhook (opcional)}';

    /**
     * The console command description.
     */
    protected $description = 'Configura el webhook para el bot de Telegram';

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

        // Obtener URL del webhook
        $webhookUrl = $this->option('url') ?: config('telegram.bots.ifree.webhook_url');

        // Telegram lo manda en cada webhook (X-Telegram-Bot-Api-Secret-Token) y el servidor lo exige
        $secreto = (string) config('telegram.webhook_secret');
        if (!preg_match('/^[A-Za-z0-9_-]{16,256}$/', $secreto)) {
            $this->error('❌ Define TELEGRAM_WEBHOOK_SECRET en el .env (16 a 256 caracteres: letras, números, _ o -) y ejecuta php artisan optimize.');
            return 1;
        }

        if (!$webhookUrl) {
            $this->error('❌ URL del webhook es requerida');
            return 1;
        }

        $this->info("🔧 Configurando webhook para: {$webhookUrl}");

        try {
            // Configurar el webhook
            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true,
                'secret_token' => $secreto,
            ]);

            $result = $response->json();

            if ($result['ok']) {
                $this->info('✅ Webhook configurado exitosamente');

                // Configurar comandos del bot
                $this->setupBotCommands($token);

                // Mostrar información del webhook
                $this->getWebhookInfo($token);

            } else {
                $this->error('❌ Error al configurar webhook: ' . $result['description']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Excepción al configurar webhook: ' . $e->getMessage());
            Log::error('Error configurando webhook Telegram: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Configura los comandos del bot
     */
    private function setupBotCommands($token)
    {
        $this->info('🤖 Configurando comandos del bot...');

        $commands = [
            [
                'command' => 'start',
                'description' => 'Inicializar el bot y mostrar bienvenida'
            ],
            [
                'command' => 'zonas',
                'description' => 'Ver las zonas ligadas a este chat'
            ],
            [
                'command' => 'registrar',
                'description' => 'Ligar este chat a una zona con el código del panel'
            ],
            [
                'command' => 'ayuda',
                'description' => 'Mostrar ayuda y comandos disponibles'
            ]
        ];

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
                'commands' => json_encode($commands)
            ]);

            $result = $response->json();

            if ($result['ok']) {
                $this->info('✅ Comandos configurados exitosamente');
            } else {
                $this->warn('⚠️  No se pudieron configurar los comandos: ' . $result['description']);
            }

        } catch (\Exception $e) {
            $this->warn('⚠️  Error configurando comandos: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene información del webhook actual
     */
    private function getWebhookInfo($token)
    {
        $this->info('📋 Obteniendo información del webhook...');

        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $result = $response->json();

            if ($result['ok']) {
                $info = $result['result'];

                $this->line('');
                $this->line('📊 <comment>Información del Webhook:</comment>');
                $this->line("URL: {$info['url']}");
                $this->line("Actualizaciones pendientes: {$info['pending_update_count']}");

                if (isset($info['last_error_date'])) {
                    $this->line("Último error: " . date('Y-m-d H:i:s', $info['last_error_date']));
                    $this->line("Mensaje de error: {$info['last_error_message']}");
                }

                $this->line('');
            }

        } catch (\Exception $e) {
            $this->warn('⚠️  No se pudo obtener información del webhook: ' . $e->getMessage());
        }
    }
}
