<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use App\Models\TelegramChat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use DefStudio\Telegraph\Telegraph;

class DebugTelegramInfrastructure extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:debug-infrastructure';

    /**
     * The console command description.
     */
    protected $description = 'Depura la infraestructura de Telegram para identificar problemas de integración';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando depuración de infraestructura Telegram...');
        $this->line('──────────────────────────────────────────────────────');
        $this->newLine();

        // 1. Verificar configuración de bibliotecas y dependencias
        $this->info('1️⃣ Verificando bibliotecas y dependencias...');

        // Verificar Telegraph
        if (class_exists(Telegraph::class)) {
            $this->line("   ✅ Biblioteca Telegraph instalada correctamente");
        } else {
            $this->error("   ❌ Biblioteca Telegraph no encontrada");
        }

        // Verificar configuración de Telegram Bot API
        if (class_exists(\TelegramBot\Api\BotApi::class)) {
            $this->line("   ✅ Biblioteca telegram-bot/api instalada correctamente");
        } else {
            $this->warn("   ⚠️ Biblioteca telegram-bot/api no encontrada");
        }

        // Verificar configuración de token
        $token = config('app.telegram_bot_token', env('TELEGRAM_BOT_TOKEN'));
        if ($token) {
            $this->line("   ✅ Token de Telegram configurado");
        } else {
            $this->error("   ❌ Token de Telegram no configurado");
        }
        $this->newLine();

        // 2. Verificar configuración de modelos
        $this->info('2️⃣ Verificando modelos y tablas en base de datos...');

        // Modelos de Telegraph
        $telegraphBotsCount = TelegraphBot::count();
        $this->line("   📊 Bots de Telegraph: {$telegraphBotsCount}");

        $telegraphChatsCount = TelegraphChat::count();
        $this->line("   📱 Chats de Telegraph: {$telegraphChatsCount}");

        // Modelos propios
        $telegramChatsCount = TelegramChat::count();
        $this->line("   💬 Chats del sistema I-Free: {$telegramChatsCount}");

        if ($telegramChatsCount > 0) {
            $chatTypes = TelegramChat::selectRaw('tipo, count(*) as total')
                                    ->groupBy('tipo')
                                    ->pluck('total', 'tipo')
                                    ->toArray();

            foreach ($chatTypes as $tipo => $total) {
                $this->line("   ├─ {$tipo}: {$total}");
            }

            $zonaAssociations = TelegramChat::withCount('zonas')->get()->sum('zonas_count');
            $this->line("   └─ Asociaciones con zonas: {$zonaAssociations}");
        }
        $this->newLine();

        // 3. Verificar configuración de rutas
        $this->info('3️⃣ Verificando rutas configuradas...');

        // Rutas de webhook
        $telegraphRouteFound = false;
        $telegramWebhookRouteFound = false;

        foreach (Route::getRoutes() as $route) {
            $routeUri = $route->uri();
            $routeMethods = $route->methods();

            if (str_contains($routeUri, 'telegraph') && in_array('POST', $routeMethods)) {
                $telegraphRouteFound = true;
                $this->line("   ✅ Ruta Telegraph encontrada: {$routeUri}");
                $this->line("      └─ Action: " . $route->getActionName());
            } elseif (str_contains($routeUri, 'telegram/webhook') && in_array('POST', $routeMethods)) {
                $telegramWebhookRouteFound = true;
                $this->line("   ✅ Ruta Telegram Webhook encontrada: {$routeUri}");
                $this->line("      └─ Action: " . $route->getActionName());
            }
        }

        if (!$telegraphRouteFound) {
            $this->warn("   ⚠️ No se encontró la ruta de Telegraph");
        }

        if (!$telegramWebhookRouteFound) {
            $this->warn("   ⚠️ No se encontró la ruta de Telegram Webhook");
        }
        $this->newLine();

        // 4. Verificar configuración de eventos y listeners
        $this->info('4️⃣ Verificando eventos y listeners...');

        // Verificar la existencia de clases de eventos
        if (class_exists(\App\Events\HotspotMetricCreated::class)) {
            $this->line("   ✅ Evento HotspotMetricCreated encontrado");
        } else {
            $this->warn("   ⚠️ Evento HotspotMetricCreated no encontrado");
        }

        if (class_exists(\App\Listeners\SendTelegramNotification::class)) {
            $this->line("   ✅ Listener SendTelegramNotification encontrado");
        } else {
            $this->warn("   ⚠️ Listener SendTelegramNotification no encontrado");
        }

        // Verificar servicios
        if (class_exists(\App\Services\TelegramNotificationService::class)) {
            $this->line("   ✅ Servicio TelegramNotificationService encontrado");
        } else {
            $this->warn("   ⚠️ Servicio TelegramNotificationService no encontrado");
        }
        $this->newLine();

        // 5. Verificar configuración de Telegraph
        $this->info('5️⃣ Verificando configuración de Telegraph...');

        // Configuración del webhook
        $webhookUrl = config('telegraph.webhook.url');
        $this->line("   🔗 URL de webhook: {$webhookUrl}");

        $webhookHandler = config('telegraph.webhook.handler');
        $this->line("   🧩 Handler de webhook: {$webhookHandler}");

        if ($webhookHandler !== 'App\\Http\\Controllers\\TelegramWebhookController') {
            $this->warn("   ⚠️ El handler no coincide con TelegramWebhookController");
        }

        $webhookSecret = config('telegraph.webhook.secret');
        $this->line("   🔑 Webhook secret: " . ($webhookSecret ? "Configurado" : "No configurado"));

        $parseMode = config('telegraph.default_parse_mode');
        $this->line("   📝 Modo de parseo: {$parseMode}");
        $this->newLine();

        // 6. Probar configuración de entrega de mensajes al servicio
        $this->info('6️⃣ Simulando entrega de notificación...');

        try {
            // Intentar generar una notificación simulada
            if (class_exists(\App\Services\TelegramNotificationService::class)) {
                $telegramService = app(\App\Services\TelegramNotificationService::class);

                // Método para verificar si el servicio está listo para enviar
                $this->line("   🔄 Verificando servicio de notificaciones...");

                if (method_exists($telegramService, 'isConfigured') && $telegramService->isConfigured()) {
                    $this->line("   ✅ Servicio TelegramNotificationService configurado correctamente");
                } else {
                    $this->warn("   ⚠️ Servicio TelegramNotificationService no está listo para enviar");
                }
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Error al probar el servicio: " . $e->getMessage());
            Log::error("Error al probar el servicio Telegram", ['exception' => $e]);
        }

        // 7. Diagnóstico y sugerencias
        $this->newLine();
        $this->info('7️⃣ Diagnóstico y sugerencias:');

        $issues = [];

        if (!$token) {
            $issues[] = "- Token de Telegram no configurado";
        }

        if ($telegraphBotsCount == 0) {
            $issues[] = "- No hay bots configurados en Telegraph";
        }

        if ($webhookHandler !== 'App\\Http\\Controllers\\TelegramWebhookController') {
            $issues[] = "- Handler de webhook incorrecto en config/telegraph.php";
        }

        if (!$telegraphRouteFound && !$telegramWebhookRouteFound) {
            $issues[] = "- No se encontraron rutas para el webhook";
        }

        if (count($issues) > 0) {
            $this->warn("   Se encontraron varios problemas:");
            foreach ($issues as $issue) {
                $this->line("   {$issue}");
            }
            $this->newLine();

            $this->info("   🛠️ Acciones recomendadas:");
            $this->line("   1. Ejecuta: php artisan telegram:create-bot");
            $this->line("   2. Ejecuta: php artisan telegram:test-webhook --reset");
            $this->line("   3. Verifica la configuración en .env y config/telegraph.php");
            $this->line("   4. Asegúrate de que el servidor sea accesible desde Internet");
        } else {
            $this->info("   ✅ No se detectaron problemas estructurales");
            $this->line("   💡 Para probar completamente, ejecuta:");
            $this->line("      php artisan telegram:test-webhook --diagnose");
        }

        return 0;
    }
}
