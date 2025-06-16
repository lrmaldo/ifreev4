<?php

namespace App\Listeners;

use App\Events\HotspotMetricCreated;
use App\Services\TelegramNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class SendTelegramFormMetricNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $telegram;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        // Instanciamos la API de Telegram usando la configuración
        $this->telegram = new Api(config('telegram.bots.ifree.token'));
    }

    /**
     * Handle the event.
     */
    public function handle(HotspotMetricCreated $event): void
    {
        try {
            $metric = $event->hotspotMetric;
            $zona = $metric->zona;

            // Verificamos si la zona tiene el tipo de registro de formulario
            if ($zona && $zona->tipo_registro === 'formulario' && $zona->campos->count() > 0) {
                Log::info("Procesando notificación Telegram para métrica de formulario ID: {$metric->id} en zona {$zona->nombre}");

                // Obtenemos los chats asociados a la zona
                $chats = $zona->telegramChats()->activos()->get();

                if ($chats->isEmpty()) {
                    Log::info("No hay chats de Telegram activos asociados a la zona {$zona->id}");
                    return;
                }

                // Preparamos el mensaje con la información de la métrica
                $mensaje = $this->prepararMensaje($metric);

                // Enviamos la notificación a cada chat
                foreach ($chats as $chat) {
                    try {
                        $this->telegram->sendMessage([
                            'chat_id' => $chat->chat_id,
                            'text' => $mensaje,
                            'parse_mode' => 'HTML',
                        ]);

                        Log::info("Notificación enviada exitosamente al chat {$chat->chat_id} para zona {$zona->nombre}");
                    } catch (\Exception $e) {
                        Log::error("Error al enviar notificación a chat {$chat->chat_id}: " . $e->getMessage());
                    }
                }
            } else {
                Log::info("La zona {$zona->id} no tiene formulario o es otro tipo de registro: {$zona->tipo_registro}");
            }
        } catch (\Exception $e) {
            Log::error("Error general al procesar notificación Telegram para formularios: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(HotspotMetricCreated $event, \Throwable $exception): void
    {
        Log::error("Falló el envío de notificación Telegram para métrica de formulario ID: {$event->hotspotMetric->id}. Error: " . $exception->getMessage());
    }

    /**
     * Prepara el mensaje con la información de la métrica
     */
    protected function prepararMensaje($metric): string
    {
        $zona = $metric->zona;

        // Obtenemos información del formulario si existe
        $formularioInfo = '';
        if ($metric->respondio_formulario && $metric->formulario) {
            $formularioInfo = "\n\n<b>📝 Datos del formulario:</b>\n";

            // Obtenemos las respuestas formateadas
            $respuestasFormateadas = $metric->formulario->respuestasFormateadas();

            foreach ($respuestasFormateadas as $respuesta) {
                $formularioInfo .= "- <b>{$respuesta['etiqueta']}:</b> {$respuesta['valor']}\n";
            }
        }

        // Construimos el mensaje completo
        $mensaje = "<b>🆕 Nueva conexión en {$zona->nombre}</b>\n\n" .
                   "<b>📱 Dispositivo:</b> {$metric->dispositivo}\n" .
                   "<b>🌐 Navegador:</b> {$metric->navegador}\n" .
                   "<b>⏱️ Fecha:</b> " . $metric->created_at->format('d/m/Y H:i:s') . "\n" .
                   "<b>🔄 Visitas:</b> {$metric->veces_entradas}" .
                   $formularioInfo;

        return $mensaje;
    }
}
