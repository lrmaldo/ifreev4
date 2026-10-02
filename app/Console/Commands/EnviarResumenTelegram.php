<?php

namespace App\Console\Commands;

use App\Models\Zona;
use App\Services\ResumenZonaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class EnviarResumenTelegram extends Command
{
    protected $signature = 'telegram:resumen-zonas';

    protected $description = 'Envía a Telegram el resumen periódico de las zonas en modo resumen';

    public function handle(ResumenZonaService $resumenes): int
    {
        $ahora = now();
        $zonas = Zona::whereNotNull('telegram_resumen_minutos')->get()
            ->filter(fn (Zona $zona) => $resumenes->tocaResumen($zona, $ahora));

        foreach ($zonas as $zona) {
            $desde = $zona->telegram_ultimo_resumen_at ?? $ahora->copy()->subMinutes($zona->telegram_resumen_minutos);
            $mensaje = $resumenes->construirMensaje($zona, $desde, $ahora);

            // Se marca aunque no haya actividad, para que la siguiente ventana empiece aquí
            $zona->forceFill(['telegram_ultimo_resumen_at' => $ahora])->save();

            if (!$mensaje) {
                continue;
            }

            foreach ($zona->telegramChats()->where('activo', true)->get() as $chat) {
                try {
                    $this->enviar($chat->chat_id, $mensaje);
                } catch (\Throwable $e) {
                    Log::error("Resumen Telegram: no se pudo enviar al chat {$chat->chat_id}: " . $e->getMessage());
                }
            }

            $this->info("Resumen enviado para zona {$zona->id}");
        }

        return self::SUCCESS;
    }

    protected function enviar(string $chatId, string $mensaje): void
    {
        (new Api(config('telegram.bots.ifree.token')))->sendMessage([
            'chat_id' => $chatId,
            'text' => $mensaje,
            'parse_mode' => 'HTML',
        ]);
    }
}
