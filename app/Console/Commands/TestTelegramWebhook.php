<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use App\Models\Zona;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:test-webhook
                          {chat_id? : ID del chat para probar}
                          {--diagnose : Ejecutar diagnóstico completo del webhook}
                          {--reset : Eliminar y volver a configurar el webhook}
                          {--verify : Verificar configuración actual sin enviar mensajes}
                          {--debug : Mostrar información detallada para depuración}';

    /**
     * The console command description.
     */
    protected $description = 'Prueba y diagnóstica el webhook de Telegram';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('app.telegram_bot_token', env('TELEGRAM_BOT_TOKEN'));

        if (!$token) {
            $this->error('❌ No se encontró el token de Telegram en la configuración.');
            return 1;
        }

        // Si se solicita diagnóstico completo
        if ($this->option('diagnose')) {
            return $this->runFullDiagnostics($token);
        }

        // Si se solicita resetear el webhook
        if ($this->option('reset')) {
            return $this->resetWebhook($token);
        }

        // Si se solicita verificación
        if ($this->option('verify')) {
            return $this->verifyWebhook($token);
        }

        // Si llegamos aquí, se trata de enviar un mensaje de prueba
        $chatId = $this->argument('chat_id');

        if (!$chatId) {
            $this->error('❌ Debes proporcionar un chat_id para enviar un mensaje de prueba.');
            $this->line('');
            $this->line('Ejemplos:');
            $this->line('  php artisan telegram:test-webhook 123456789');
            $this->line('  php artisan telegram:test-webhook --diagnose');
            $this->line('  php artisan telegram:test-webhook --verify');
            return 1;
        }

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

    /**
     * Ejecuta un diagnóstico completo del webhook
     */
    private function runFullDiagnostics($token)
    {
        $this->info('🤖 Iniciando diagnóstico del webhook de Telegram...');
        $this->newLine();

        // 1. Verificar información del bot
        $this->info('1️⃣ Verificando información del bot...');
        $botInfo = $this->getBotInfo($token);

        if (!$botInfo) {
            $this->error('❌ No se pudo obtener información del bot. Verifica el token.');
            return 1;
        }

        $this->line("   ✅ Bot encontrado: <info>{$botInfo['first_name']}</info>");
        if (isset($botInfo['username'])) {
            $this->line("   👤 Username: @{$botInfo['username']}");
        }
        $this->line("   🆔 ID: {$botInfo['id']}");
        $this->newLine();

        // 2. Verificar webhook actual
        $this->info('2️⃣ Verificando configuración actual del webhook...');
        $webhookInfo = $this->getWebhookInfo($token);

        if (!$webhookInfo) {
            $this->error('❌ No se pudo obtener información del webhook.');
            return 1;
        }

        if (empty($webhookInfo['url'])) {
            $this->warn('⚠️ No hay webhook configurado actualmente.');
        } else {
            $this->line("   🔗 URL actual: <info>{$webhookInfo['url']}</info>");
            $this->line("   📊 Solicitudes pendientes: {$webhookInfo['pending_update_count']}");

            if ($webhookInfo['has_custom_certificate'] ?? false) {
                $this->line("   🔒 Usa certificado personalizado: Sí");
            }

            if (isset($webhookInfo['last_error_date'], $webhookInfo['last_error_message'])) {
                $date = date('Y-m-d H:i:s', $webhookInfo['last_error_date']);
                $this->warn("   ⚠️ Último error: {$date} - {$webhookInfo['last_error_message']}");
            }
        }
        $this->newLine();

        // 3. Verificar comandos configurados
        $this->info('3️⃣ Verificando comandos configurados...');
        $commands = $this->getCommands($token);

        if ($commands === false) {
            $this->error('❌ No se pudieron obtener los comandos configurados.');
        } elseif (empty($commands)) {
            $this->warn('⚠️ No hay comandos configurados.');
        } else {
            $this->line("   ✅ Comandos configurados:");
            foreach ($commands as $command) {
                $this->line("   • /{$command['command']} - {$command['description']}");
            }
        }
        $this->newLine();

        // 4. Verificar configuración de routes y controllers
        $this->info('4️⃣ Verificando configuración local...');
        $this->line("   📱 Ruta del webhook: " . route('telegram.webhook'));
        $this->line("   🧩 Handler configurado: " . config('telegraph.webhook.handler'));
        $this->line("   🔗 URL definida en config: " . config('telegraph.webhook.url', 'No configurada'));
        $this->newLine();

        // 5. Probar envío de solicitud HTTP al webhook local
        $this->info('5️⃣ Simulando una actualización HTTP al webhook local...');
        $url = route('telegram.webhook');

        // Simular una actualización de Telegram
        $testUpdate = [
            'update_id' => rand(1000000, 9999999),
            'message' => [
                'message_id' => rand(1000, 9999),
                'from' => [
                    'id' => $botInfo['id'],
                    'first_name' => 'Test',
                    'username' => 'test_user',
                    'is_bot' => false
                ],
                'chat' => [
                    'id' => $botInfo['id'],
                    'first_name' => 'Test Chat',
                    'type' => 'private'
                ],
                'date' => time(),
                'text' => '/test'
            ]
        ];

        try {
            // Enviamos una solicitud ficticia al webhook local para probar su funcionamiento
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Telegram-Bot-Api-Secret-Token' => config('telegraph.webhook.secret', '')
                ])
                ->post($url, $testUpdate);

            if ($response->successful()) {
                $this->line("   ✅ El webhook local respondió correctamente (HTTP " . $response->status() . ")");
            } else {
                $this->warn("   ⚠️ El webhook respondió con error: HTTP " . $response->status());
                if ($this->option('debug')) {
                    $this->line("   📄 Contenido: " . $response->body());
                }
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error al probar el webhook local: " . $e->getMessage());
            if ($this->option('debug')) {
                Log::error("Error probando webhook", ['exception' => $e]);
            }
        }
        $this->newLine();

        // 6. Sugerencias
        $this->info('6️⃣ Sugerencias y soluciones:');

        if (empty($webhookInfo['url'])) {
            $this->line("   • Configura el webhook: <info>php artisan telegraph:set-webhook</info>");
            $this->warn("   • El webhook no está configurado. Esta es la causa principal del problema.");
        } elseif ($webhookInfo['url'] !== route('telegram.webhook')) {
            $this->warn("   • La URL registrada en Telegram ({$webhookInfo['url']}) no coincide con la ruta local (" . route('telegram.webhook') . ")");
            $this->line("   • Actualiza el webhook: <info>php artisan telegraph:set-webhook " . route('telegram.webhook') . "</info>");
        }

        if (empty($commands)) {
            $this->line("   • Configura los comandos: <info>php artisan telegram:create-bot</info>");
        }

        if (isset($webhookInfo['last_error_message']) && !empty($webhookInfo['last_error_message'])) {
            $this->line("   • Resuelve el error del webhook: " . $webhookInfo['last_error_message']);
        }

        $this->line("   • Para reiniciar la configuración del webhook: <info>php artisan telegram:test-webhook --reset</info>");
        $this->line("   • Para probar un mensaje a un chat: <info>php artisan telegram:test-webhook 123456789</info>");

        return 0;
    }

    /**
     * Reinicia el webhook eliminándolo y configurándolo nuevamente
     */
    private function resetWebhook($token)
    {
        $this->info('🔄 Reiniciando configuración del webhook...');

        // 1. Eliminar webhook actual
        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/deleteWebhook");
            if ($response->successful()) {
                $this->info("✅ Webhook anterior eliminado correctamente");
            } else {
                $this->warn("⚠️ Error eliminando webhook: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        // 2. Configurar nuevo webhook
        $webhookUrl = route('telegram.webhook');
        $this->line("🔗 Configurando nuevo webhook en: {$webhookUrl}");

        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'max_connections' => 40,
                'drop_pending_updates' => true
            ]);

            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                $this->info("✅ Nuevo webhook configurado correctamente");
                $this->line("📋 Respuesta: " . $response->body());
            } else {
                $this->error("❌ Error configurando webhook: " . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        // 3. Verificar configuración de comandos
        $this->info("🔄 Reconfigurando comandos del bot...");

        try {
            $commands = [
                ['command' => 'start', 'description' => 'Inicializar el bot y mostrar bienvenida'],
                ['command' => 'zonas', 'description' => 'Listar zonas disponibles del sistema'],
                ['command' => 'registrar', 'description' => 'Asociar chat con una zona específica'],
                ['command' => 'ayuda', 'description' => 'Mostrar ayuda y comandos disponibles']
            ];

            // Probar ambos formatos (por si uno falla)
            $response = Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
                'commands' => $commands
            ]);

            if (!$response->successful()) {
                // Intentar con formato alternativo
                $response = Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
                    'commands' => json_encode($commands)
                ]);
            }

            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                $this->info("✅ Comandos configurados correctamente");
            } else {
                $this->warn("⚠️ Error configurando comandos: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ Error configurando comandos: " . $e->getMessage());
        }

        $this->newLine();
        $this->info("🎉 Configuración de webhook completada!");
        $this->line("🔍 Ejecuta <info>php artisan telegram:test-webhook --verify</info> para verificar el estado");

        return 0;
    }

    /**
     * Verifica el estado actual del webhook sin modificarlo
     */
    private function verifyWebhook($token)
    {
        $this->info('🔍 Verificando estado del webhook...');

        // Obtener información del webhook
        $webhookInfo = $this->getWebhookInfo($token);

        if (!$webhookInfo) {
            $this->error('❌ No se pudo obtener información del webhook.');
            return 1;
        }

        $this->line('');
        $this->info('📊 Estado actual del webhook:');

        if (empty($webhookInfo['url'])) {
            $this->warn('⚠️ No hay webhook configurado actualmente.');
            $this->line("🔗 URL local esperada: " . route('telegram.webhook'));
        } else {
            $this->line("🔗 URL configurada: <info>{$webhookInfo['url']}</info>");
            $this->line("🔗 URL local esperada: " . route('telegram.webhook'));

            // Verificar si coinciden
            if ($webhookInfo['url'] === route('telegram.webhook')) {
                $this->info("✅ La URL coincide con la configuración local");
            } else {
                $this->warn("⚠️ La URL no coincide con la configuración local");
            }
        }

        // Verificar errores
        if (isset($webhookInfo['last_error_date'])) {
            $date = date('Y-m-d H:i:s', $webhookInfo['last_error_date']);
            $this->line('');
            $this->warn("⚠️ Último error: {$date}");
            $this->warn("   Mensaje: {$webhookInfo['last_error_message']}");

            // Sugerir soluciones según el tipo de error
            $errorMsg = strtolower($webhookInfo['last_error_message']);
            $this->line('');
            $this->info('💡 Posibles soluciones:');

            if (str_contains($errorMsg, 'certificate')) {
                $this->line("   • Problema con el certificado SSL. Verifica que el dominio tenga un certificado válido.");
                $this->line("   • Puedes probar con un autofirmado usando: php artisan telegraph:set-webhook --self-signed");
            } elseif (str_contains($errorMsg, 'not found')) {
                $this->line("   • La URL del webhook no es accesible. Verifica que el servidor sea accesible desde internet.");
                $this->line("   • Si estás en desarrollo, considera usar ngrok: https://ngrok.com/");
            } elseif (str_contains($errorMsg, 'timeout')) {
                $this->line("   • Timeout al intentar conectar con el webhook. Tu servidor podría estar sobrecargado.");
                $this->line("   • Verifica la configuración del timeout en config/telegraph.php");
            } else {
                $this->line("   • Reinicia el webhook: <info>php artisan telegram:test-webhook --reset</info>");
                $this->line("   • Verifica los logs en storage/logs/laravel.log");
            }
        } else {
            $this->info("✅ No se han detectado errores recientes");
        }

        // Información adicional
        $this->line('');
        $this->info('📑 Información adicional:');
        $this->line("   • Solicitudes pendientes: {$webhookInfo['pending_update_count']}");
        $this->line("   • SSL activado: " . ($webhookInfo['has_custom_certificate'] ? 'Sí' : 'No'));
        $this->line("   • Máx. conexiones: " . ($webhookInfo['max_connections'] ?? 'No configurado'));

        return 0;
    }

    private function getBotInfo($token)
    {
        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getMe");
            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                return $response->json()['result'] ?? [];
            }
        } catch (\Exception $e) {
            if ($this->option('debug')) {
                $this->error("Error obteniendo información del bot: " . $e->getMessage());
            }
        }
        return false;
    }

    private function getWebhookInfo($token)
    {
        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                return $response->json()['result'] ?? [];
            }
        } catch (\Exception $e) {
            if ($this->option('debug')) {
                $this->error("Error obteniendo información del webhook: " . $e->getMessage());
            }
        }
        return false;
    }

    private function getCommands($token)
    {
        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getMyCommands");
            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                return $response->json()['result'] ?? [];
            }
        } catch (\Exception $e) {
            if ($this->option('debug')) {
                $this->error("Error obteniendo comandos: " . $e->getMessage());
            }
        }
        return false;
    }
}
