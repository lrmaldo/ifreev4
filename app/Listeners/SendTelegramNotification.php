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

            $this->telegramService->notifyNewHotspotMetric($event->hotspotMetric);

        } catch (\Exception $e) {
            Log::error("Error al procesar notificación Telegram: " . $e->getMessage());

            // Re-lanzar la excepción para que el job falle y se reintente si es necesario
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(HotspotMetricCreated $event, \Throwable $exception): void
    {
        Log::error("Falló el envío de notificación Telegram para métrica ID: {$event->hotspotMetric->id}. Error: " . $exception->getMessage());
    }
}
