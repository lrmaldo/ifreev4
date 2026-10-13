<?php

namespace App\Services;

use App\Models\TelegramChat;
use App\Models\Zona;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Liga chats de Telegram a zonas con un código de un solo uso que genera el panel.
 *
 * Antes bastaba "/registrar [ID de zona]": cualquiera que encontrara el bot podía
 * recibir los registros (nombres, teléfonos) de cualquier zona.
 */
class TelegramVinculoService
{
    /** Sin 0/O ni 1/I/L para que se pueda dictar o copiar a mano sin errores */
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const LARGO = 8;
    public const MINUTOS_VIGENCIA = 30;
    private const INTENTOS_POR_HORA = 5;

    /**
     * Genera (o reemplaza) el código de la zona. Devuelve el código con guion: "K7QF-2MXP".
     */
    public function generarCodigo(Zona $zona): string
    {
        do {
            $codigo = '';
            for ($i = 0; $i < self::LARGO; $i++) {
                $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
            }
        } while (Zona::where('telegram_codigo', $codigo)->exists());

        $zona->forceFill([
            'telegram_codigo' => $codigo,
            'telegram_codigo_expira_at' => now()->addMinutes(self::MINUTOS_VIGENCIA),
        ])->save();

        return self::formatear($codigo);
    }

    /**
     * Liga el chat a la zona del código. Devuelve la zona, o un mensaje de error para el chat.
     */
    public function vincular(TelegramChat $chat, string $codigoEscrito): Zona|string
    {
        $clave = 'telegram-vinculo:' . $chat->chat_id;

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS_POR_HORA)) {
            $minutos = (int) ceil(RateLimiter::availableIn($clave) / 60);

            return "⚠️ Demasiados intentos. Vuelve a intentarlo en {$minutos} min.";
        }

        $codigo = self::normalizar($codigoEscrito);
        $zona = strlen($codigo) === self::LARGO
            ? Zona::where('telegram_codigo', $codigo)->where('telegram_codigo_expira_at', '>', now())->first()
            : null;

        if (!$zona) {
            RateLimiter::hit($clave, 3600);

            return '⚠️ Código no válido o vencido. Pide uno nuevo en el panel de i-Free (menú ⋯ de la zona → Vincular Telegram).';
        }

        RateLimiter::clear($clave);
        $chat->zonas()->syncWithoutDetaching([$zona->id]);
        // Un solo uso: para ligar otro chat se genera otro código
        $zona->forceFill(['telegram_codigo' => null, 'telegram_codigo_expira_at' => null])->save();

        return $zona;
    }

    public static function normalizar(string $codigo): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($codigo));
    }

    public static function formatear(string $codigo): string
    {
        return substr($codigo, 0, 4) . '-' . substr($codigo, 4);
    }
}
