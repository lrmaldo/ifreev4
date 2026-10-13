<?php

namespace App\Console\Commands;

use App\Services\AlertasEventoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class VigilarEvento extends Command
{
    protected $signature = 'evento:vigilar {--prueba : Envía un mensaje de prueba a los chats de alertas}';

    protected $description = 'Revisa portal, errores, visitas y registros de las zonas de evento y avisa por Telegram';

    public function handle(AlertasEventoService $alertas): int
    {
        if (!config('ifree.alertas.activas')) {
            return self::SUCCESS;
        }

        $chats = $alertas->destinatarios();

        if ($this->option('prueba')) {
            if (!$chats) {
                $this->error('No hay chats: define IFREE_ALERTAS_CHATS o liga un chat de Telegram a la zona del evento.');
                return self::FAILURE;
            }
            $this->enviar($chats, '🧪 <b>Prueba de alertas de i-Free</b>' . "\nSi ves esto, las alertas del evento llegarán a este chat.");
            $this->info('Mensaje de prueba enviado a ' . count($chats) . ' chat(s).');
            return self::SUCCESS;
        }

        $mensajes = $alertas->revisar();

        foreach ($mensajes as $mensaje) {
            Log::warning('Alerta de evento: ' . strip_tags($mensaje));
            $this->line(strip_tags($mensaje));
        }

        if ($mensajes && $chats) {
            $this->enviar($chats, implode("\n\n", $mensajes));
        }

        return self::SUCCESS;
    }

    protected function enviar(array $chats, string $mensaje): void
    {
        foreach ($chats as $chatId) {
            try {
                (new Api(config('telegram.bots.ifree.token')))->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $mensaje,
                    'parse_mode' => 'HTML',
                ]);
            } catch (\Throwable $e) {
                Log::error("Alertas de evento: no se pudo enviar al chat {$chatId}: " . $e->getMessage());
            }
        }
    }
}
