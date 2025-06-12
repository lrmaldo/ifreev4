<?php

namespace App\Services;

use App\Models\HotspotMetric;
use App\Models\Zona;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private $telegram;

    public function __construct()
    {
        $botToken = config('app.telegram_bot_token', env('TELEGRAM_BOT_TOKEN'));

        if (!$botToken) {
            Log::warning('Token de Telegram Bot no configurado');
            return;
        }

        try {
            $this->telegram = new BotApi($botToken);
        } catch (Exception $e) {
            Log::error('Error al inicializar Telegram Bot: ' . $e->getMessage());
        }
    }

    /**
     * Envía notificación cuando se crea una nueva métrica de hotspot
     */
    public function notifyNewHotspotMetric(HotspotMetric $metric): void
    {
        if (!$this->telegram) {
            Log::warning('Telegram Bot no inicializado, saltando notificación');
            return;
        }

        // Obtener la zona y sus chats de Telegram asociados
        $zona = $metric->zona()->with('telegramChats')->first();

        if (!$zona) {
            Log::warning("Zona no encontrada para la métrica ID: {$metric->id}");
            return;
        }

        $telegramChats = $zona->telegramChats()->activos()->get();

        if ($telegramChats->isEmpty()) {
            Log::info("No hay chats de Telegram activos configurados para la zona: {$zona->nombre}");
            return;
        }

        // Generar el mensaje
        $message = $this->formatHotspotMetricMessage($metric, $zona);

        // Enviar a cada chat configurado
        foreach ($telegramChats as $chat) {
            $this->sendMessage($chat->chat_id, $message);
        }
    }

    /**
     * Formatea el mensaje de la métrica de hotspot para Telegram
     */
    private function formatHotspotMetricMessage(HotspotMetric $metric, Zona $zona): string
    {
        $message = "🚨 <b>Nueva Conexión Detectada</b>\n\n";

        $message .= "📍 <b>Zona:</b> {$zona->nombre}\n";
        $message .= "🆔 <b>ID Zona:</b> {$zona->id}\n\n";

        $message .= "📱 <b>Detalles del Dispositivo:</b>\n";
        $message .= "• <b>MAC Address:</b> <code>{$metric->mac_address}</code>\n";
        $message .= "• <b>Dispositivo:</b> {$metric->dispositivo}\n";
        $message .= "• <b>Navegador:</b> {$metric->navegador}\n";
        $message .= "• <b>Sistema Operativo:</b> {$metric->sistema_operativo}\n\n";

        $message .= "📊 <b>Métricas de Uso:</b>\n";
        $message .= "• <b>Tipo Visual:</b> {$metric->tipo_visual}\n";
        $message .= "• <b>Duración Visual:</b> {$metric->duracion_visual} segundos\n";
        $message .= "• <b>Clic en Botón:</b> " . ($metric->clic_boton ? '✅ Sí' : '❌ No') . "\n";
        $message .= "• <b>Veces de Entrada:</b> {$metric->veces_entradas}\n\n";

        if ($metric->formulario_id) {
            $message .= "📝 <b>Formulario ID:</b> {$metric->formulario_id}\n\n";
        }

        $message .= "🕒 <b>Fecha:</b> " . $metric->created_at->format('d/m/Y H:i:s') . "\n";

        return $message;
    }

    /**
     * Envía un mensaje a un chat específico
     */
    private function sendMessage(string $chatId, string $message): void
    {
        try {
            $this->telegram->sendMessage(
                $chatId,
                $message,
                'HTML', // Formato HTML para negrita, cursiva, etc.
                false,  // Disable web page preview
                null,   // Reply to message ID
                null    // Reply markup
            );

            Log::info("Mensaje enviado exitosamente al chat: {$chatId}");

        } catch (Exception $e) {
            Log::error("Error al enviar mensaje de Telegram al chat {$chatId}: " . $e->getMessage());
        }
    }

    /**
     * Prueba la conexión enviando un mensaje de test
     */
    public function testConnection(string $chatId): bool
    {
        if (!$this->telegram) {
            return false;
        }

        try {
            $this->telegram->sendMessage(
                $chatId,
                "🤖 <b>Test de Conexión</b>\n\nBot de notificaciones I-Free conectado correctamente.",
                'HTML'
            );

            return true;

        } catch (Exception $e) {
            Log::error("Error en test de conexión Telegram: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene información del bot
     */
    public function getBotInfo(): ?array
    {
        if (!$this->telegram) {
            return null;
        }

        try {
            $me = $this->telegram->getMe();
            return [
                'id' => $me->getId(),
                'username' => $me->getUsername(),
                'first_name' => $me->getFirstName(),
                'is_bot' => $me->isBot()
            ];

        } catch (Exception $e) {
            Log::error("Error al obtener información del bot: " . $e->getMessage());
            return null;
        }
    }
}
