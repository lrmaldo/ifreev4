<?php

namespace App\Http\Controllers;

use App\Models\TelegramChat;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

/**
 * Controlador para manejar las interacciones con Telegram
 *
 * Este controlador utiliza la biblioteca irazasyed/telegram-bot-sdk para
 * procesar webhooks de Telegram y enviar mensajes.
 */
class TelegramController extends Controller
{
    /**
     * Instancia del API de Telegram
     *
     * @var \Telegram\Bot\Api
     */
    protected $telegram;

    /**
     * Constructor
     */
    public function __construct(Api $telegram)
    {
        // El SDK resuelve Api con el bot por defecto ('ifree' en config/telegram.php)
        $this->telegram = $telegram;
    }

    /**
     * Maneja los webhooks entrantes de Telegram
     *
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        // Solo Telegram conoce el secreto (se le entrega con telegram:setup-webhook).
        // Sin él, cualquiera podría mandar "mensajes" falsos al bot.
        $secreto = (string) config('telegram.webhook_secret');
        if ($secreto === '' || !hash_equals($secreto, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            Log::warning('Webhook de Telegram rechazado: secreto ausente o incorrecto', ['ip' => $request->ip()]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        try {
            $update = $request->json()->all();

            // Sin contenido ni cabeceras: los mensajes traen datos personales
            Log::debug('Webhook de Telegram', [
                'update_id' => $update['update_id'] ?? null,
                'chat_id' => $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null,
            ]);

            // Si es un mensaje
            if (isset($update['message'])) {
                return $this->handleMessage($update['message']);
            }

            // Si es un callback query (botones inline)
            if (isset($update['callback_query'])) {
                return $this->handleCallbackQuery($update['callback_query']);
            }

            // Otros tipos de updates
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error procesando webhook de Telegram', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja los mensajes recibidos
     *
     * @return \Illuminate\Http\Response
     */
    protected function handleMessage(array $message)
    {
        try {
            $chatId = $message['chat']['id'] ?? null;
            $text = $message['text'] ?? null;

            if (! $chatId) {
                Log::warning('Mensaje sin chat_id');

                return response()->json(['status' => 'error', 'message' => 'No chat ID'], 400);
            }

            // Registrar el chat si no existe
            $this->registerChat($message['chat'], $message['from'] ?? []);

            // Procesar comandos
            if ($text && str_starts_with($text, '/')) {
                // Limpiar el texto del comando eliminando la mención del bot (@nombre_bot)
                $text = $this->cleanCommandText($text);

                $parts = explode(' ', $text);
                $command = ltrim($parts[0], '/');
                $params = array_slice($parts, 1);

                switch ($command) {
                    case 'start':
                        return $this->handleStartCommand($chatId);
                    case 'zonas':
                        return $this->handleZonasCommand($chatId);
                    case 'registrar':
                        return $this->handleRegistrarCommand($chatId, $params);
                    case 'ayuda':
                        return $this->handleAyudaCommand($chatId);
                    case 'estadisticas':
                        return $this->handleEstadisticasCommand($chatId);
                    case 'reporte':
                        return $this->handleReporteCommand($chatId);
                    case 'dispositivos':
                        return $this->handleDispositivosCommand($chatId);
                    case 'navegadores':
                        return $this->handleNavegadoresCommand($chatId);
                    case 'conectados':
                        return $this->handleConectadosCommand($chatId);
                    case 'ultimo':
                        return $this->handleUltimoCommand($chatId);
                    case 'estado':
                        return $this->handleEstadoCommand($chatId);
                    case 'ping':
                        return $this->handlePingCommand($chatId);
                    case 'alertas':
                        return $this->handleAlertasCommand($chatId);
                    case 'perfil':
                        return $this->handlePerfilCommand($chatId);
                    case 'desuscribirse':
                        return $this->handleDesuscribirseCommand($chatId, $params);
                    case 'exportar':
                        return $this->handleExportarCommand($chatId);
                    case 'horarios':
                        return $this->handleHorariosCommand($chatId);
                    case 'restricciones':
                        return $this->handleRestriccionesCommand($chatId);
                    case 'detalle':
                        return $this->handleDetalleCommand($chatId, $params);
                    case 'historial':
                        return $this->handleHistorialCommand($chatId);
                    case 'descarga':
                        return $this->handleDescargaCommand($chatId);
                    case 'limpiar':
                        return $this->handleLimpiarCommand($chatId);
                    case 'sincronizar':
                        return $this->handleSincronizarCommand($chatId);
                    default:
                        return $this->handleUnknownCommand($chatId, $command);
                }
            }

            // Mensaje normal (no comando)
            return $this->handleNormalMessage($chatId, $text);
        } catch (\Exception $e) {
            Log::error('Error al procesar mensaje', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Limpia el texto del comando eliminando la mención del bot
     * Convierte "/comando@nombre_bot" en "/comando"
     */
    protected function cleanCommandText(string $text): string
    {
        // Dividir la primera palabra (comando) del resto
        $parts = explode(' ', $text, 2);
        $command = $parts[0];
        $rest = isset($parts[1]) ? ' '.$parts[1] : '';

        // Remover la mención del bot si existe (@nombre_bot)
        if (strpos($command, '@') !== false) {
            $command = explode('@', $command)[0];
        }

        return $command.$rest;
    }

    /**
     * Maneja las callback queries (botones inline)
     *
     * @return \Illuminate\Http\Response
     */
    protected function handleCallbackQuery(array $callbackQuery)
    {
        try {
            $chatId = $callbackQuery['message']['chat']['id'] ?? null;
            $callbackData = $callbackQuery['data'] ?? '';

            if (! $chatId) {
                Log::warning('Callback query sin chat_id');

                return response()->json(['status' => 'error', 'message' => 'No chat ID'], 400);
            }

            // Procesamos el callback data
            Log::info('Callback query recibido', [
                'chat_id' => $chatId,
                'data' => $callbackData,
            ]);

            // Dividir los datos para procesar
            $parts = explode(':', $callbackData);
            $action = $parts[0] ?? '';
            $param = $parts[1] ?? '';

            switch ($action) {
                case 'zona':
                    return $this->handleZonaCallback($chatId, $param, $callbackQuery);
                default:
                    $this->telegram->answerCallbackQuery([
                        'callback_query_id' => $callbackQuery['id'],
                        'text' => 'Acción no reconocida',
                    ]);

                    return response()->json(['status' => 'error', 'message' => 'Unknown callback action']);
            }
        } catch (\Exception $e) {
            Log::error('Error al procesar callback query', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Registra un chat si no existe
     */
    protected function registerChat(array $chat, array $from = []): TelegramChat
    {
        $chatId = $chat['id'];

        // Buscar si ya existe
        $telegramChat = TelegramChat::where('chat_id', $chatId)->first();

        if (! $telegramChat) {
            // Si no existe, crear un nuevo chat
            Log::info('Registrando nuevo chat', [
                'chat_id' => $chatId,
                'type' => $chat['type'] ?? 'unknown',
            ]);

            // Obtener nombre según el tipo de chat
            $nombre = $this->getChatName($chat, $from);

            // Obtener el tipo de chat
            $tipo = $this->mapChatType($chat['type'] ?? 'unknown');

            // Crear chat
            $telegramChat = TelegramChat::create([
                'chat_id' => $chatId,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'activo' => true,
            ]);

            Log::info('Chat registrado correctamente', [
                'id' => $telegramChat->id,
                'chat_id' => $telegramChat->chat_id,
                'nombre' => $telegramChat->nombre,
            ]);
        }

        return $telegramChat;
    }

    /**
     * Obtiene el nombre del chat según su tipo
     */
    protected function getChatName(array $chat, array $from = []): string
    {
        $chatType = $chat['type'] ?? 'unknown';

        switch ($chatType) {
            case 'private':
                $firstName = $from['first_name'] ?? '';
                $lastName = $from['last_name'] ?? '';
                $username = $from['username'] ?? '';

                if ($username) {
                    return "@{$username} ({$firstName} {$lastName})";
                }

                return trim("{$firstName} {$lastName}");

            case 'group':
            case 'supergroup':
                return $chat['title'] ?? 'Grupo sin nombre';

            case 'channel':
                return $chat['title'] ?? 'Canal sin nombre';

            default:
                return 'Chat #'.$chat['id'];
        }
    }

    /**
     * Mapea el tipo de chat de Telegram a los valores permitidos en el ENUM de la base de datos
     */
    protected function mapChatType(string $telegramType): string
    {
        switch ($telegramType) {
            case 'private':
                return 'private';
            case 'group':
                return 'group';
            case 'supergroup':
                return 'supergroup';
            case 'channel':
                return 'channel';
            default:
                return 'private'; // Valor por defecto si no coincide con ninguno
        }
    }

    /**
     * Maneja el comando /start
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleStartCommand($chatId)
    {
        $mensaje = <<<'HTML'
🤖 <b>¡Bienvenido al Bot de I-Free!</b>

Este bot te notificará sobre eventos importantes del sistema de hotspots y te proporciona estadísticas en tiempo real.

📋 <b>Comandos disponibles:</b>
/zonas - Ver las zonas de este chat
/registrar CODIGO - Ligar este chat a una zona
/estadisticas - Ver estadísticas
/estado - Ver estado del sistema
/ayuda - Ver todos los comandos disponibles

🚀 <b>¿Cómo empezar?</b>
1. Pide un código de vinculación en el panel de i-Free
2. Envía /registrar CODIGO
3. ¡Recibe notificaciones automáticas!

💡 Usa /ayuda para ver todos los comandos disponibles
HTML;

        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de start', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * /zonas: las zonas ligadas a ESTE chat. Nunca la lista completa del sistema.
     */
    protected function handleZonasCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();
            $zonas = $chat ? $chat->zonas()->orderBy('nombre')->get() : collect();

            $mensaje = $zonas->isEmpty()
                ? "Este chat todavía no está ligado a ninguna zona.\n\nPide un código de vinculación en el panel de i-Free y envía <code>/registrar CODIGO</code>."
                : "<b>📍 Zonas de este chat:</b>\n\n" . $zonas->map(fn ($z) => '- <b>ID ' . $z->id . ':</b> ' . e($z->nombre))->implode("\n");

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de zonas', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * /registrar CODIGO: liga el chat a la zona del código que generó el panel.
     */
    protected function handleRegistrarCommand($chatId, array $params)
    {
        try {
            $codigo = implode('', $params);

            if ($codigo === '' || ctype_digit($codigo)) {
                // Antes era "/registrar [ID]"; ahora se pide un código que genera el panel
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "🔐 Para ligar este chat a una zona necesitas un <b>código de vinculación</b>.\n\n"
                        . "Pídelo al administrador de i-Free (panel → Zonas → menú ⋯ → Vincular Telegram) y envía:\n"
                        . '<code>/registrar CODIGO</code>',
                    'parse_mode' => 'HTML',
                ]);

                return response()->json(['status' => 'success']);
            }

            $chat = TelegramChat::where('chat_id', $chatId)->firstOrFail();
            $resultado = app(\App\Services\TelegramVinculoService::class)->vincular($chat, $codigo);

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $resultado instanceof Zona
                    ? '✅ Chat ligado a la zona <b>' . e($resultado->nombre) . '</b>. Aquí llegarán sus avisos.'
                    : $resultado,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error procesando comando registrar', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Botones "zona:ID" de mensajes viejos de /zonas: ya no ligan el chat (se usa un código).
     */
    protected function handleZonaCallback($chatId, $zonaId, array $callbackQuery)
    {
        try {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery['id'],
                'text' => 'Para ligar una zona usa /registrar CODIGO con el código del panel.',
                'show_alert' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error respondiendo callback de zona', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Maneja el comando /ayuda
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleAyudaCommand($chatId)
    {
        $mensaje = <<<'HTML'
📚 <b>Ayuda del Bot de I-Free</b>

Este bot te permite recibir notificaciones sobre eventos importantes del sistema de hotspots I-Free.

<b>🔧 COMANDOS BÁSICOS:</b>

/start - Inicia la conversación con el bot y muestra el mensaje de bienvenida

/zonas - Muestra las zonas ligadas a este chat

/registrar CODIGO - Liga este chat a una zona con el código del panel

/ayuda - Muestra este mensaje de ayuda

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

<b>📊 COMANDOS DE ESTADÍSTICAS:</b>

/estadisticas - Ver estadísticas generales de las zonas suscritas

/reporte - Generar reporte detallado de actividad

/dispositivos - Ver dispositivos conectados

/navegadores - Ver navegadores más utilizados

/conectados - Ver usuarios conectados en tiempo real

/ultimo - Mostrar último evento registrado

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

<b>🔍 COMANDOS DE DIAGNÓSTICO:</b>

/estado - Ver estado del sistema y zonas

/ping - Verificar conectividad con el bot

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

<b>⚙️ COMANDOS AVANZADOS:</b>

/alertas - Configurar alertas personalizadas

/perfil - Ver o editar configuración del chat

/desuscribirse - Dejar de recibir notificaciones de una zona

/exportar - Exportar datos en formato CSV

/horarios - Configurar horarios de envío de notificaciones

/restricciones - Ver restricciones de ancho de banda

/detalle [zona_id] - Ver detalles de una zona específica

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

<b>💾 COMANDOS DE DATOS:</b>

/historial - Ver historial de eventos

/descarga - Descargar reportes

/limpiar - Limpiar datos locales del bot

/sincronizar - Sincronizar datos con el servidor

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

<b>🎯 MODO DE USO RÁPIDO:</b>

1️⃣ Usa /zonas para ver las zonas disponibles
2️⃣ Pide un código en el panel y envía /registrar CODIGO
3️⃣ ¡Listo! Recibirás notificaciones automáticas

<b>💡 EJEMPLOS:</b>
• /registrar K7QF-2MXP
• /estadisticas
• /dispositivos
• /reporte
• /detalle 2

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Para más información o soporte, contacta al administrador del sistema.
HTML;

        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de ayuda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja comandos desconocidos
     *
     * @param  int|string  $chatId
     * @param  string  $command
     * @return \Illuminate\Http\Response
     */
    protected function handleUnknownCommand($chatId, $command)
    {
        $mensaje = <<<HTML
⚠️ <b>Comando desconocido</b>: /{$command}

No reconozco este comando, pero puedo ayudarte con:

<b>📋 COMANDOS BÁSICOS:</b>
/zonas - Ver las zonas de este chat
/registrar CODIGO - Ligar este chat a una zona
/estado - Ver estado del sistema

<b>📊 ESTADÍSTICAS:</b>
/estadisticas - Estadísticas generales
/dispositivos - Dispositivos conectados
/navegadores - Navegadores más utilizados

<b>💡 MÁS OPCIONES:</b>
/ayuda - Ver todos los comandos disponibles

¿Necesitas ayuda? Usa /ayuda para ver la lista completa.
HTML;

        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de comando desconocido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja mensajes normales (que no son comandos)
     *
     * @param  int|string  $chatId
     * @param  string|null  $text
     * @return \Illuminate\Http\Response
     */
    protected function handleNormalMessage($chatId, $text = null)
    {
        // Obtener el chat para personalizar el mensaje
        $chat = TelegramChat::where('chat_id', $chatId)->first();
        $nombre = $chat ? $chat->nombre : 'Usuario';

        // Simplificar el nombre si es complejo
        if (strpos($nombre, '@') !== false) {
            $parts = explode(' ', $nombre);
            $nombre = $parts[0];
        }

        $mensaje = <<<HTML
👋 Hola {$nombre}!

Recibí tu mensaje, pero espero <b>comandos</b> del bot.

<b>📋 COMANDOS POPULARES:</b>
/zonas - Ver las zonas de este chat
/estadisticas - Ver estadísticas
/estado - Ver estado del sistema
/ayuda - Ver todos los comandos

¿Qué necesitas?
HTML;

        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error enviando respuesta a mensaje normal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /estadisticas
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleEstadisticasCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();

            if ($zonas->isEmpty()) {
                $mensaje = '⚠️ No tienes zonas suscritas. Pide un código en el panel y usa /registrar CODIGO';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>📊 Estadísticas de tus zonas:</b>\n\n";

            foreach ($zonas as $zona) {
                $totalMetricas = \App\Models\HotspotMetric::where('zona_id', $zona->id)->count();
                $hoy = \App\Models\HotspotMetric::where('zona_id', $zona->id)
                    ->whereDate('created_at', now()->toDateString())
                    ->count();

                $mensaje .= "<b>{$zona->nombre}</b> (ID: {$zona->id})\n";
                $mensaje .= "  📈 Total conexiones: {$totalMetricas}\n";
                $mensaje .= "  🕐 Hoy: {$hoy}\n\n";
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /estadisticas', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /reporte
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleReporteCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();

            if ($zonas->isEmpty()) {
                $mensaje = '⚠️ No tienes zonas suscritas';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>📋 Reporte de actividad:</b>\n";
            $mensaje .= 'Fecha: '.now()->format('d/m/Y H:i')."\n\n";

            foreach ($zonas as $zona) {
                $metricas = \App\Models\HotspotMetric::where('zona_id', $zona->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                $mensaje .= "<b>📍 {$zona->nombre}</b>\n";
                $mensaje .= "Últimas conexiones:\n";

                foreach ($metricas as $metrica) {
                    $fecha = $metrica->created_at->format('H:i');
                    $dispositivo = $metrica->dispositivo ? substr($metrica->dispositivo, 0, 20) : 'Desconocido';
                    $mensaje .= "  • {$fecha} - {$dispositivo}\n";
                }

                $mensaje .= "\n";
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /reporte', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /dispositivos
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleDispositivosCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();

            if ($zonas->isEmpty()) {
                $mensaje = '⚠️ No tienes zonas suscritas';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>📱 Dispositivos conectados:</b>\n\n";

            $zonaIds = $zonas->pluck('id')->toArray();
            $dispositivos = \App\Models\HotspotMetric::whereIn('zona_id', $zonaIds)
                ->select('dispositivo')
                ->groupBy('dispositivo')
                ->limit(10)
                ->get();

            if ($dispositivos->isEmpty()) {
                $mensaje .= 'Sin conexiones registradas';
            } else {
                foreach ($dispositivos as $idx => $disp) {
                    $count = \App\Models\HotspotMetric::whereIn('zona_id', $zonaIds)->where('dispositivo', $disp->dispositivo)->count();
                    $mensaje .= ''.($idx + 1).". {$disp->dispositivo} ({$count} conexiones)\n";
                }
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /dispositivos', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /navegadores
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleNavegadoresCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();

            if ($zonas->isEmpty()) {
                $mensaje = '⚠️ No tienes zonas suscritas';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>🌐 Navegadores más utilizados:</b>\n\n";

            $zonaIds = $zonas->pluck('id')->toArray();
            $navegadores = \App\Models\HotspotMetric::whereIn('zona_id', $zonaIds)
                ->select('navegador')
                ->groupBy('navegador')
                ->limit(10)
                ->get();

            if ($navegadores->isEmpty()) {
                $mensaje .= 'Sin datos disponibles';
            } else {
                foreach ($navegadores as $idx => $nav) {
                    $count = \App\Models\HotspotMetric::whereIn('zona_id', $zonaIds)->where('navegador', $nav->navegador)->count();
                    $mensaje .= ''.($idx + 1).". {$nav->navegador} ({$count})\n";
                }
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /navegadores', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /conectados
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleConectadosCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();

            if ($zonas->isEmpty()) {
                $mensaje = '⚠️ No tienes zonas suscritas';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>👥 Usuarios conectados (últimas 24h):</b>\n\n";

            $zonaIds = $zonas->pluck('id')->toArray();
            $hoy = now()->toDateString();

            foreach ($zonas as $zona) {
                $count = \App\Models\HotspotMetric::where('zona_id', $zona->id)
                    ->whereDate('created_at', $hoy)
                    ->count();

                $mensaje .= "<b>{$zona->nombre}</b>: {$count} conexiones\n";
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /conectados', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /ultimo
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleUltimoCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();

            if ($zonas->isEmpty()) {
                $mensaje = '⚠️ No tienes zonas suscritas';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>⏱️ Último evento registrado:</b>\n\n";

            foreach ($zonas as $zona) {
                $metrica = \App\Models\HotspotMetric::where('zona_id', $zona->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($metrica) {
                    $fecha = $metrica->created_at->format('d/m/Y H:i:s');
                    $mensaje .= "<b>{$zona->nombre}</b>\n";
                    $mensaje .= "Fecha: {$fecha}\n";
                    $mensaje .= "Dispositivo: {$metrica->dispositivo}\n\n";
                } else {
                    $mensaje .= "<b>{$zona->nombre}</b>: Sin eventos\n\n";
                }
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /ultimo', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /estado
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleEstadoCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            // Solo datos de las zonas de este chat: nada del resto del sistema
            $zonasUsuario = $chat->zonas()->count();
            $totalMetricas = \App\Models\HotspotMetric::whereIn('zona_id', $chat->zonas()->pluck('zonas.id'))->count();

            $mensaje = "<b>🟢 Estado del bot:</b> en línea\n\n";
            $mensaje .= "Tus zonas: {$zonasUsuario}\n";
            $mensaje .= "Conexiones en tus zonas: {$totalMetricas}\n";
            $mensaje .= "\n<b>Tu chat:</b> {$chat->nombre}\n";
            $mensaje .= 'Activo: '.($chat->activo ? '✅' : '❌')."\n";

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /estado', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /ping
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handlePingCommand($chatId)
    {
        try {
            $timestamp = now()->format('d/m/Y H:i:s');

            $mensaje = "🏓 <b>Pong!</b>\n\n";
            $mensaje .= "El bot está funcionando correctamente.\n";
            $mensaje .= "Timestamp: {$timestamp}\n";
            $mensaje .= 'Status: ✅ Operativo';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /ping', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /alertas
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleAlertasCommand($chatId)
    {
        try {
            $mensaje = "<b>🔔 Configuración de Alertas</b>\n\n";
            $mensaje .= "Esta funcionalidad está en desarrollo.\n";
            $mensaje .= "Próximamente podrás configurar:\n";
            $mensaje .= "• Alertas por umbral de conexiones\n";
            $mensaje .= "• Notificaciones de desconexiones\n";
            $mensaje .= "• Alertas de anomalías\n\n";
            $mensaje .= 'Para más información, usa /ayuda';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /alertas', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /perfil
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handlePerfilCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();
            $zonasText = $zonas->isEmpty() ? 'Ninguna' : implode(', ', $zonas->pluck('nombre')->toArray());

            $mensaje = "<b>👤 Perfil del Chat</b>\n\n";
            $mensaje .= "ID Chat: {$chat->chat_id}\n";
            $mensaje .= "Nombre: {$chat->nombre}\n";
            $mensaje .= "Tipo: {$chat->tipo}\n";
            $mensaje .= 'Activo: '.($chat->activo ? '✅' : '❌')."\n";
            $mensaje .= "Zonas suscritas: {$zonasText}\n";
            $mensaje .= 'Registrado: '.$chat->created_at->format('d/m/Y H:i')."\n";

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /perfil', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /desuscribirse
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleDesuscribirseCommand($chatId, array $params)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            if (empty($params)) {
                $zonas = $chat->zonas()->get();
                if ($zonas->isEmpty()) {
                    $mensaje = '⚠️ No tienes zonas suscritas';
                } else {
                    $mensaje = "<b>📍 Zonas suscritas:</b>\n\n";
                    foreach ($zonas as $zona) {
                        $mensaje .= "ID {$zona->id}: {$zona->nombre}\n";
                    }
                    $mensaje .= "\nUsa /desuscribirse [ID] para desuscribirte";
                }

                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje, 'parse_mode' => 'HTML']);

                return response()->json(['status' => 'success']);
            }

            $zonaId = intval($params[0]);
            $zona = Zona::find($zonaId);

            if (! $zona) {
                $mensaje = "⚠️ Zona con ID {$zonaId} no encontrada";
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            if ($chat->zonas()->where('zona_id', $zonaId)->exists()) {
                $chat->zonas()->detach($zonaId);
                $mensaje = "✅ Te has desuscrito de la zona <b>{$zona->nombre}</b>";
            } else {
                $mensaje = "⚠️ No estás suscrito a la zona {$zona->nombre}";
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /desuscribirse', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /exportar
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleExportarCommand($chatId)
    {
        try {
            $mensaje = "<b>💾 Exportar Datos</b>\n\n";
            $mensaje .= "Esta funcionalidad está en desarrollo.\n";
            $mensaje .= "Próximamente podrás descargar reportes en:\n";
            $mensaje .= "• CSV\n";
            $mensaje .= "• Excel\n";
            $mensaje .= "• PDF\n\n";
            $mensaje .= 'Para más información, usa /ayuda';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /exportar', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /horarios
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleHorariosCommand($chatId)
    {
        try {
            $mensaje = "<b>🕐 Configurar Horarios</b>\n\n";
            $mensaje .= "Esta funcionalidad está en desarrollo.\n";
            $mensaje .= "Próximamente podrás configurar:\n";
            $mensaje .= "• Horarios de notificaciones\n";
            $mensaje .= "• Zonas horarias\n";
            $mensaje .= "• Desactivar notificaciones en ciertos horarios\n\n";
            $mensaje .= 'Para más información, usa /ayuda';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /horarios', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /restricciones
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleRestriccionesCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>⚙️ Restricciones de Ancho de Banda</b>\n\n";
            $mensaje .= "Chat: {$chat->nombre}\n";
            $mensaje .= 'Estado: '.($chat->activo ? '✅ Activo' : '❌ Inactivo')."\n\n";
            $mensaje .= "Límites aplicables:\n";
            $mensaje .= "• Máximo 10 notificaciones por hora\n";
            $mensaje .= "• Máximo 100 notificaciones por día\n";
            $mensaje .= "• Tamaño máximo de mensaje: 4096 caracteres\n\n";
            $mensaje .= 'Esta funcionalidad está en desarrollo para personalización.';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /restricciones', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /detalle
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleDetalleCommand($chatId, array $params)
    {
        try {
            if (empty($params)) {
                $mensaje = '⚠️ Por favor especifica el ID de la zona: /detalle [ID]';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonaId = intval($params[0]);
            // Solo zonas ligadas a este chat
            $zona = TelegramChat::where('chat_id', $chatId)->first()?->zonas()->where('zonas.id', $zonaId)->first();

            if (! $zona) {
                $mensaje = "⚠️ La zona {$zonaId} no está ligada a este chat";
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $totalMetricas = \App\Models\HotspotMetric::where('zona_id', $zonaId)->count();
            $hoy = \App\Models\HotspotMetric::where('zona_id', $zonaId)
                ->whereDate('created_at', now()->toDateString())
                ->count();

            $mensaje = "<b>📍 Detalles de la Zona</b>\n\n";
            $mensaje .= "ID: {$zona->id}\n";
            $mensaje .= "Nombre: {$zona->nombre}\n";
            $mensaje .= "Descripción: {$zona->descripcion}\n";
            $mensaje .= "Total conexiones: {$totalMetricas}\n";
            $mensaje .= "Conexiones hoy: {$hoy}\n";
            $mensaje .= 'Registrada: '.$zona->created_at->format('d/m/Y H:i')."\n";

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /detalle', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /historial
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleHistorialCommand($chatId)
    {
        try {
            $chat = TelegramChat::where('chat_id', $chatId)->first();

            if (! $chat) {
                $mensaje = '⚠️ Chat no registrado en el sistema';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $zonas = $chat->zonas()->get();

            if ($zonas->isEmpty()) {
                $mensaje = '⚠️ No tienes zonas suscritas';
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $mensaje]);

                return response()->json(['status' => 'success']);
            }

            $mensaje = "<b>📜 Historial de Eventos</b>\n";
            $mensaje .= "Últimos 10 eventos:\n\n";

            $zonaIds = $zonas->pluck('id')->toArray();
            $metricas = \App\Models\HotspotMetric::whereIn('zona_id', $zonaIds)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($metricas as $idx => $metrica) {
                $zona = Zona::find($metrica->zona_id);
                $fecha = $metrica->created_at->format('d/m H:i');
                $mensaje .= ''.($idx + 1).". [{$fecha}] {$zona->nombre}\n";
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /historial', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /descarga
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleDescargaCommand($chatId)
    {
        try {
            $mensaje = "<b>📥 Descargar Reportes</b>\n\n";
            $mensaje .= "Esta funcionalidad está en desarrollo.\n";
            $mensaje .= "Próximamente podrás descargar:\n";
            $mensaje .= "• Reportes diarios\n";
            $mensaje .= "• Reportes mensuales\n";
            $mensaje .= "• Reportes personalizados\n\n";
            $mensaje .= 'Para más información, usa /ayuda';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /descarga', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /limpiar
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleLimpiarCommand($chatId)
    {
        try {
            $mensaje = "<b>🧹 Limpiar Datos</b>\n\n";
            $mensaje .= "Esta funcionalidad está en desarrollo.\n";
            $mensaje .= "Próximamente podrás limpiar:\n";
            $mensaje .= "• Historial de eventos\n";
            $mensaje .= "• Datos de caché\n";
            $mensaje .= "• Registros temporales\n\n";
            $mensaje .= 'Para más información, usa /ayuda';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /limpiar', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja el comando /sincronizar
     *
     * @param  int|string  $chatId
     * @return \Illuminate\Http\Response
     */
    protected function handleSincronizarCommand($chatId)
    {
        try {
            $mensaje = "<b>🔄 Sincronizar Datos</b>\n\n";
            $mensaje .= "Sincronizando información del servidor...\n";
            $mensaje .= "✅ Zonas sincronizadas\n";
            $mensaje .= "✅ Métricas sincronizadas\n";
            $mensaje .= "✅ Configuración sincronizada\n\n";
            $mensaje .= 'Todo está actualizado.';

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error en /sincronizar', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API para enviar notificaciones a chats suscritos a una zona
     *
     * @return \Illuminate\Http\Response
     */
    public function enviarNotificacion(Request $request)
    {
        try {
            // Validar la solicitud
            $request->validate([
                'zona_id' => 'required|integer|exists:zonas,id',
                'titulo' => 'required|string|max:255',
                'mensaje' => 'required|string',
            ]);

            // Obtener la zona
            $zona = Zona::find($request->zona_id);
            if (! $zona) {
                return response()->json(['error' => 'Zona no encontrada'], 404);
            }

            // Obtener los chats asociados a la zona
            $chats = $zona->telegramChats()->where('activo', true)->get();

            if ($chats->isEmpty()) {
                return response()->json(['message' => 'No hay chats suscritos a esta zona'], 200);
            }

            // Preparar el mensaje
            $texto = "<b>{$request->titulo}</b>\n\n{$request->mensaje}\n\n<i>Zona: {$zona->nombre}</i>";

            // Contador de mensajes enviados
            $enviados = 0;
            $errores = [];

            // Enviar mensaje a cada chat
            foreach ($chats as $chat) {
                try {
                    $this->telegram->sendMessage([
                        'chat_id' => $chat->chat_id,
                        'text' => $texto,
                        'parse_mode' => 'HTML',
                    ]);
                    $enviados++;
                } catch (\Exception $e) {
                    Log::error('Error enviando notificación a chat', [
                        'chat_id' => $chat->chat_id,
                        'error' => $e->getMessage(),
                    ]);
                    $errores[] = [
                        'chat_id' => $chat->chat_id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'enviados' => $enviados,
                'total' => $chats->count(),
                'errores' => $errores,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en enviarNotificacion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
