<?php

namespace App\Services;

use App\Models\User;

/**
 * Avisa por Telegram a Sattlink que alguien creó una cuenta y espera aprobación.
 * Va a los mismos chats que las alertas (IFREE_ALERTAS_CHATS o los de zonas Evento).
 */
class AvisoCuentaNueva
{
    public function __construct(
        private AlertasEventoService $alertas,
        private TelegramMensajero $mensajero,
    ) {
    }

    public function avisar(User $user): int
    {
        $mensaje = "👤 <b>Cuenta nueva por aprobar</b>\n\n"
            . '• <b>Nombre:</b> ' . e($user->name) . "\n"
            . '• <b>Correo:</b> ' . e($user->email) . "\n\n"
            . 'Apruébala o elimínala en ' . e(route('admin.users.index', ['rol' => 'sin_rol']));

        $enviados = 0;
        foreach ($this->alertas->destinatarios() as $chatId) {
            $enviados += $this->mensajero->enviar((string) $chatId, $mensaje) ? 1 : 0;
        }

        return $enviados;
    }
}
