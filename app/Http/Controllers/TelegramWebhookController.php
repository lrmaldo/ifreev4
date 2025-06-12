<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use App\Models\TelegramChat;
use App\Models\Zona;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends WebhookHandler
{
    /**
     * Maneja el comando /start
     */
    public function start(): void
    {
        $this->chat->message("🤖 <b>¡Bienvenido al Bot de I-Free!</b>\n\n")
            ->message("Este bot te notificará sobre eventos importantes del sistema de hotspots.\n\n")
            ->message("📋 <b>Comandos disponibles:</b>\n")
            ->message("/start - Mostrar este mensaje\n")
            ->message("/zonas - Ver zonas disponibles\n")
            ->message("/registrar [zona_id] - Asociar chat con una zona\n")
            ->message("/ayuda - Mostrar ayuda detallada\n\n")
            ->message("🔧 Para empezar, usa /zonas para ver las zonas disponibles.")
            ->send();

        $this->registerChat();
    }

    /**
     * Maneja el comando /zonas
     */
    public function zonas(): void
    {
        $zonas = Zona::all();

        if ($zonas->isEmpty()) {
            $this->chat->message("❌ No hay zonas configuradas en el sistema.")
                ->send();
            return;
        }

        $message = "📍 <b>Zonas disponibles:</b>\n\n";

        foreach ($zonas as $zona) {
            $message .= "🏷️ <b>ID:</b> {$zona->id}\n";
            $message .= "📌 <b>Nombre:</b> {$zona->nombre}\n";

            if ($zona->id_personalizado) {
                $message .= "🆔 <b>ID Personalizado:</b> {$zona->id_personalizado}\n";
            }

            $message .= "\n";
        }

        $message .= "💡 <i>Para asociar este chat con una zona, usa:</i>\n";
        $message .= "<code>/registrar [zona_id]</code>";

        $this->chat->message($message)->send();
    }

    /**
     * Maneja el comando /registrar
     */
    public function registrar(): void
    {
        $messageText = $this->message->text();
        $parts = explode(' ', $messageText);

        if (count($parts) < 2) {
            $this->chat->message("❌ <b>Formato incorrecto</b>\n\n")
                ->message("Uso: <code>/registrar [zona_id]</code>\n")
                ->message("Ejemplo: <code>/registrar 1</code>\n\n")
                ->message("💡 Usa /zonas para ver las zonas disponibles.")
                ->send();
            return;
        }

        $zonaId = (int) $parts[1];
        $zona = Zona::find($zonaId);

        if (!$zona) {
            $this->chat->message("❌ <b>Zona no encontrada</b>\n\n")
                ->message("La zona con ID <b>{$zonaId}</b> no existe.\n")
                ->message("Usa /zonas para ver las zonas disponibles.")
                ->send();
            return;
        }

        // Registrar o obtener el chat
        $telegramChat = $this->registerChat();

        // Verificar si ya está asociado
        if ($telegramChat->zonas()->where('zona_id', $zonaId)->exists()) {
            $this->chat->message("⚠️ <b>Ya registrado</b>\n\n")
                ->message("Este chat ya está asociado con la zona <b>{$zona->nombre}</b>.")
                ->send();
            return;
        }

        // Asociar chat con zona
        $telegramChat->zonas()->attach($zonaId);

        $this->chat->message("✅ <b>¡Registro exitoso!</b>\n\n")
            ->message("Chat asociado con la zona: <b>{$zona->nombre}</b>\n")
            ->message("🔔 Ahora recibirás notificaciones de esta zona.")
            ->send();

        Log::info("Chat {$this->chat->chat_id} asociado con zona {$zona->nombre} (ID: {$zonaId})");
    }

    /**
     * Maneja el comando /ayuda
     */
    public function ayuda(): void
    {
        $telegramChat = TelegramChat::where('chat_id', $this->chat->chat_id)->first();
        $zonasAsociadas = $telegramChat ? $telegramChat->zonas->count() : 0;

        $message = "📚 <b>Ayuda del Bot I-Free</b>\n\n";

        $message .= "🎯 <b>Propósito:</b>\n";
        $message .= "Este bot envía notificaciones automáticas cuando se detectan nuevas conexiones en las zonas de hotspot.\n\n";

        $message .= "📋 <b>Comandos disponibles:</b>\n\n";

        $message .= "🚀 <code>/start</code>\n";
        $message .= "Mostrar mensaje de bienvenida\n\n";

        $message .= "📍 <code>/zonas</code>\n";
        $message .= "Listar todas las zonas disponibles\n\n";

        $message .= "📝 <code>/registrar [zona_id]</code>\n";
        $message .= "Asociar este chat con una zona específica\n";
        $message .= "Ejemplo: <code>/registrar 1</code>\n\n";

        $message .= "❓ <code>/ayuda</code>\n";
        $message .= "Mostrar esta ayuda\n\n";

        $message .= "📊 <b>Estado actual:</b>\n";
        $message .= "• Zonas asociadas: <b>{$zonasAsociadas}</b>\n";
        $message .= "• Chat ID: <code>{$this->chat->chat_id}</code>\n";
        $message .= "• Tipo de chat: <b>" . $this->getChatType() . "</b>\n\n";

        $message .= "💡 <b>Consejos:</b>\n";
        $message .= "• Puedes asociar este chat con múltiples zonas\n";
        $message .= "• Las notificaciones incluyen detalles del dispositivo conectado\n";
        $message .= "• Funciona tanto en grupos como en chats privados";

        $this->chat->message($message)->send();
    }

    /**
     * Maneja mensajes no reconocidos
     */
    public function handleChatMessage(): void
    {
        // Solo registrar el chat si envía un mensaje directo
        $this->registerChat();

        // Responder solo si el mensaje contiene texto específico
        $text = strtolower($this->message->text() ?? '');

        if (str_contains($text, 'hola') || str_contains($text, 'ayuda') || str_contains($text, 'help')) {
            $this->chat->message("👋 ¡Hola! Usa /start para comenzar o /ayuda para ver los comandos disponibles.")
                ->send();
        }
    }

    /**
     * Registra o actualiza el chat en la base de datos
     */
    private function registerChat(): TelegramChat
    {
        $chatData = [
            'chat_id' => $this->chat->chat_id,
            'nombre' => $this->getChatName(),
            'tipo' => $this->getChatType(),
            'activo' => true
        ];

        $telegramChat = TelegramChat::updateOrCreate(
            ['chat_id' => $this->chat->chat_id],
            $chatData
        );

        if ($telegramChat->wasRecentlyCreated) {
            Log::info("Nuevo chat registrado: {$telegramChat->nombre} (ID: {$telegramChat->chat_id})");

            $this->chat->message("✅ <b>Chat registrado correctamente</b>\n\n")
                ->message("Tu chat ha sido añadido a nuestro sistema.\n")
                ->message("Usa /zonas para ver las zonas disponibles.")
                ->send();
        }

        return $telegramChat;
    }

    /**
     * Obtiene el nombre del chat
     */
    private function getChatName(): string
    {
        $update = $this->data;

        if (isset($update['message']['chat']['title'])) {
            // Es un grupo
            return $update['message']['chat']['title'];
        }

        if (isset($update['message']['from'])) {
            // Es un chat privado
            $from = $update['message']['from'];
            $name = $from['first_name'] ?? '';

            if (isset($from['last_name'])) {
                $name .= ' ' . $from['last_name'];
            }

            if (isset($from['username'])) {
                $name .= ' (@' . $from['username'] . ')';
            }

            return trim($name) ?: 'Usuario desconocido';
        }

        return 'Chat desconocido';
    }

    /**
     * Obtiene el tipo de chat
     */
    private function getChatType(): string
    {
        $update = $this->data;

        if (isset($update['message']['chat']['type'])) {
            return $update['message']['chat']['type'];
        }

        return 'private';
    }
}
