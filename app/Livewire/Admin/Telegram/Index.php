<?php

namespace App\Livewire\Admin\Telegram;

use App\Models\TelegramChat;
use App\Models\Zona;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Página de Telegram: estado real del bot y del webhook, y los chats con sus zonas.
 * Los chats se dan de alta solos al escribirle al bot; aquí se revisan y se ajustan.
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    /** Resultado de "Revisar conexión": datos de getMe y getWebhookInfo, o el error */
    public ?array $diagnostico = null;

    /** Zona elegida para agregar, por id de chat */
    public array $zonaParaAgregar = [];

    public ?int $eliminandoId = null;

    public static function token(): string
    {
        $token = (string) config('telegram.bots.ifree.token');

        return $token === 'YOUR-BOT-TOKEN' ? '' : $token;
    }

    public function revisarConexion(): void
    {
        $token = self::token();
        if ($token === '') {
            $this->diagnostico = ['error' => 'Falta TELEGRAM_BOT_TOKEN en el .env del servidor.'];
            return;
        }

        try {
            $base = "https://api.telegram.org/bot{$token}";
            $bot = Http::timeout(8)->get("{$base}/getMe")->json();
            $webhook = Http::timeout(8)->get("{$base}/getWebhookInfo")->json();

            if (!($bot['ok'] ?? false)) {
                $this->diagnostico = ['error' => 'Telegram rechazó el token (' . ($bot['description'] ?? 'sin detalle') . '). Revisa TELEGRAM_BOT_TOKEN.'];
                return;
            }

            $info = $webhook['result'] ?? [];
            $esperada = (string) config('telegram.bots.ifree.webhook_url');

            $this->diagnostico = [
                'usuario' => $bot['result']['username'] ?? null,
                'nombre' => $bot['result']['first_name'] ?? null,
                'webhook' => $info['url'] ?? '',
                'webhook_ok' => ($info['url'] ?? '') === $esperada,
                'esperada' => $esperada,
                'pendientes' => (int) ($info['pending_update_count'] ?? 0),
                'ultimo_error' => $info['last_error_message'] ?? null,
                'ultimo_error_at' => isset($info['last_error_date']) ? now()->setTimestamp($info['last_error_date'])->format('d/m/Y H:i') : null,
            ];
        } catch (\Throwable $e) {
            // El mensaje de la excepción puede incluir la URL con el token: no se muestra
            Log::warning('Revisar conexión de Telegram falló: ' . class_basename($e));
            $this->diagnostico = ['error' => 'No se pudo contactar a Telegram desde el servidor.'];
        }
    }

    public function enviarPrueba(int $chatId): void
    {
        $chat = TelegramChat::findOrFail($chatId);
        $token = self::token();

        try {
            $ok = $token !== '' && Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chat->chat_id,
                'text' => '🧪 Prueba desde el panel de i-Free: este chat recibe los avisos.',
            ])->json('ok');
        } catch (\Throwable) {
            $ok = false;
        }

        session()->flash($ok ? 'message' : 'error', $ok
            ? "Mensaje de prueba enviado a \"{$chat->nombre}\"."
            : "No se pudo enviar a \"{$chat->nombre}\". ¿El bot sigue en ese chat?");
    }

    public function alternarActivo(int $chatId): void
    {
        $chat = TelegramChat::findOrFail($chatId);
        $chat->update(['activo' => !$chat->activo]);
    }

    public function agregarZona(int $chatId): void
    {
        $zonaId = (int) ($this->zonaParaAgregar[$chatId] ?? 0);
        if (!$zonaId || !Zona::whereKey($zonaId)->exists()) {
            return;
        }

        TelegramChat::findOrFail($chatId)->zonas()->syncWithoutDetaching([$zonaId]);
        unset($this->zonaParaAgregar[$chatId]);
    }

    public function quitarZona(int $chatId, int $zonaId): void
    {
        TelegramChat::findOrFail($chatId)->zonas()->detach($zonaId);
    }

    public function confirmarEliminar(int $chatId): void
    {
        $this->eliminandoId = $chatId;
    }

    public function eliminar(): void
    {
        $chat = TelegramChat::find($this->eliminandoId);
        $this->eliminandoId = null;

        if ($chat) {
            $chat->delete();
            session()->flash('message', "Chat \"{$chat->nombre}\" eliminado. Si alguien vuelve a escribirle al bot desde ahí, aparecerá de nuevo sin zonas.");
        }
    }

    public function render()
    {
        $chats = TelegramChat::with(['zonas' => fn ($q) => $q->orderBy('nombre')])->orderByDesc('activo')->orderBy('nombre')->get();

        return view('livewire.admin.telegram.index', [
            'chats' => $chats,
            'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
            'tokenConfigurado' => self::token() !== '',
            'secretoConfigurado' => strlen((string) config('telegram.webhook_secret')) >= 16,
            'zonasConAviso' => Zona::has('telegramChats')->count(),
            'chatsAlertas' => array_values(array_filter(array_map('trim', explode(',', (string) config('ifree.alertas.chats'))))),
            'eliminando' => $this->eliminandoId ? TelegramChat::find($this->eliminandoId) : null,
        ])->title('Telegram');
    }
}
