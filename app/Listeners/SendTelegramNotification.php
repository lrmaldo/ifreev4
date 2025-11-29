<?php

namespace App\Listeners;

use App\Events\HotspotMetricCreated;
use App\Services\TelegramNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTelegramNotification implements ShouldQueue
{
    use InteractsWithQueue;

    private $telegramService;

    /**
     * Create the event listener.
     */
    public function __construct(TelegramNotificationService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle the event.
     */
    public function handle(HotspotMetricCreated $event): void
    {
        try {
            Log::info("Procesando notificación Telegram para métrica ID: {$event->hotspotMetric->id}");

            $metric = $event->hotspotMetric;
            $zona = $metric->zona;

            // Obtener los chats asociados a la zona
            if ($zona) {
                $chats = $zona->telegramChats()->where('activo', true)->get();

                if ($chats->isEmpty()) {
                    Log::info("No hay chats de Telegram activos asociados a la zona {$zona->id}");

                    return;
                }

                // Preparar el mensaje con la información de la métrica
                $mensaje = $this->prepararMensaje($metric);

                // Enviar a cada chat
                foreach ($chats as $chat) {
                    try {
                        $this->telegramService->sendMessage($chat->chat_id, $mensaje);
                        Log::info("Notificación enviada exitosamente al chat {$chat->chat_id}");
                    } catch (\Exception $e) {
                        Log::error("Error al enviar notificación al chat {$chat->chat_id}: ".$e->getMessage());
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al procesar notificación Telegram: '.$e->getMessage());

            // Re-lanzar la excepción para que el job falle y se reintente si es necesario
            throw $e;
        }
    }

    /**
     * Prepara el mensaje con la información de la métrica
     */
    private function prepararMensaje($metric): string
    {
        $zona = $metric->zona;
        $formularioInfo = '';

        // Si tiene respuesta de formulario, incluirla
        if ($metric->respondio_formulario && $metric->formulario) {
            $formularioInfo = "\n\n<b>📝 Datos del formulario:</b>\n";

            // Obtener las respuestas formateadas
            if (method_exists($metric->formulario, 'respuestasFormateadas')) {
                $respuestasFormateadas = $metric->formulario->respuestasFormateadas();
                foreach ($respuestasFormateadas as $respuesta) {
                    $formularioInfo .= "- <b>{$respuesta['etiqueta']}:</b> {$respuesta['valor']}\n";
                }
            }
        }

        // Construir el mensaje
        $mensaje = "<b>🆕 Nueva conexión en {$zona->nombre}</b>\n\n".
                   "<b>📱 Dispositivo:</b> {$metric->dispositivo}\n".
                   "<b>🌐 Navegador:</b> {$metric->navegador}\n".
                   '<b>⏱️ Fecha:</b> '.$metric->created_at->format('d/m/Y H:i:s')."\n".
                   "<b>🔄 Visitas:</b> {$metric->veces_entradas}".
                   $formularioInfo;

        return $mensaje;
    }

    /**
     * Handle a job failure.
     */
    public function failed(HotspotMetricCreated $event, \Throwable $exception): void
    {
        Log::error("Falló el envío de notificación Telegram para métrica ID: {$event->hotspotMetric->id}. Error: ".$exception->getMessage());
    }
}
