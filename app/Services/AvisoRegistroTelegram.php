<?php

namespace App\Services;

use App\Models\FormResponse;

/**
 * Aviso inmediato por Telegram cuando alguien se registra en el portal
 * (zonas en modo "al instante"; las de modo resumen se reportan cada N minutos).
 *
 * Se llama con defer(): corre después de responder al celular, sin cola ni
 * queue:work. Antes el aviso iba en una cola que nadie procesaba y salía al
 * aparecer el dispositivo, antes de que llenara el formulario.
 */
class AvisoRegistroTelegram
{
    public function __construct(private TelegramMensajero $mensajero)
    {
    }

    public function avisar(FormResponse $registro): int
    {
        $zona = $registro->zona;
        if (!$zona || $zona->telegram_resumen_minutos) {
            return 0;
        }

        $chats = $zona->telegramChats()->where('activo', true)->pluck('chat_id');
        if ($chats->isEmpty()) {
            return 0;
        }

        $mensaje = $this->mensaje($registro);
        $enviados = 0;
        foreach ($chats as $chatId) {
            $enviados += $this->mensajero->enviar((string) $chatId, $mensaje) ? 1 : 0;
        }

        return $enviados;
    }

    public function mensaje(FormResponse $registro): string
    {
        $zona = $registro->zona;
        $hoy = FormResponse::where('zona_id', $zona->id)->where('created_at', '>=', now()->startOfDay())->count();

        $lineas = collect($registro->respuestas_formateadas)
            ->map(fn ($r) => '• <b>' . e($r['etiqueta']) . ':</b> ' . e(is_array($r['valor']) ? implode(', ', $r['valor']) : (string) $r['valor']))
            ->implode("\n");

        return '📝 <b>Nuevo registro · ' . e($zona->nombre) . "</b>\n\n"
            . ($lineas !== '' ? $lineas . "\n\n" : '')
            . '🕒 ' . $registro->created_at->format('d/m/Y H:i')
            . " · Registros hoy: <b>{$hoy}</b>";
    }
}
