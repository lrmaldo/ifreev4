<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envía mensajes HTML a chats de Telegram con tiempos de espera cortos:
 * si Telegram tarda, el aviso se pierde pero nada se queda colgado.
 */
class TelegramMensajero
{
    public function enviar(string $chatId, string $html): bool
    {
        $token = (string) config('telegram.bots.ifree.token');
        if ($token === '' || $token === 'YOUR-BOT-TOKEN') {
            return false;
        }

        try {
            $ok = (bool) Http::timeout(5)->connectTimeout(3)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $html,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ])
                ->json('ok');
        } catch (\Throwable $e) {
            // El mensaje de la excepción puede llevar la URL con el token
            $ok = false;
        }

        if (!$ok) {
            Log::warning("Telegram: no se pudo enviar al chat {$chatId}");
        }

        return $ok;
    }
}
