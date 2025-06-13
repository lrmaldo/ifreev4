<?php
/**
 * TelegramWebhookController - Controlador para manejar webhooks de Telegram
 *
 * Este controlador extiende DefStudio\Telegraph\Handlers\WebhookHandler para
 * manejar las solicitudes webhook de Telegram. Incluye métodos para:
 *
 * - Procesar los webhooks entrantes
 * - Manejar el comando /start
 * - Manejar mensajes generales que no son comandos
 * - Manejar comandos desconocidos o no implementados
 * - Registrar y configurar chats
 * - Enviar mensajes con fallback en caso de fallo de Telegraph
 *
 * CONFIGURACIONES IMPORTANTES:
 * - La URL del webhook debe estar configurada como: /telegraph/{bot}/webhook
 * - El debug de webhooks se puede activar en config/telegraph.php o con TELEGRAM_WEBHOOK_DEBUG=true
 * - El registro de chats desconocidos se maneja automáticamente
 *
 * @package App\Http\Controllers
 */

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
     * Maneja las solicitudes webhook entrantes
     * Este método recibe el webhook y delega a los métodos correspondientes
     *
     * @param Request $request
     * @param DefStudio\Telegraph\Models\TelegraphBot $bot
     * @return void
     */
    public function handle(Request $request, \DefStudio\Telegraph\Models\TelegraphBot $bot): void
    {
        // Registrar recepción del webhook para diagnóstico con información detallada
        Log::info('Webhook recibido - INICIO PROCESAMIENTO', [
            'content' => $request->getContent(),
            'headers' => $request->headers->all(),
            'bot_id' => $bot->id,
            'bot_name' => $bot->name,
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
            'webhook_url_configurada' => $bot->webhook_url ?? 'no configurada',
        ]);

        // Marca de tiempo para medir la duración del procesamiento
        $startTime = microtime(true);

        try {
            // Si es una solicitud de diagnóstico especial, responder directamente
            if ($request->has('diagnostic') && $request->get('diagnostic') === 'true') {
                // No podemos retornar una respuesta directamente debido a la firma del método
                // Guardaremos un registro para diagnóstico
                Log::info('Solicitud de diagnóstico recibida', [
                    'status' => 'ok',
                    'message' => 'Webhook endpoint funcional',
                    'timestamp' => now()->toIso8601String(),
                    'handler' => get_class($this)
                ]);

                // Detener el procesamiento adicional pero sin retornar respuesta
                return;
            }

            if ($this->shouldDebug()) {
                $this->debugWebhook($request);
            }

            // Log antes de pasar el webhook al manejador base
            Log::info('Webhook pasando a manejador base Telegraph', [
                'handler_class' => get_parent_class($this),
                'bot_token' => substr($bot->token, 0, 5) . '...' . substr($bot->token, -5),
            ]);

            // Delegar al manejador base de Telegraph
            parent::handle($request, $bot);

            // Log después de procesar el webhook
            $elapsedTime = microtime(true) - $startTime;
            Log::info('Webhook procesado con éxito', [
                'tiempo_procesamiento_ms' => round($elapsedTime * 1000, 2),
                'timestamp_fin' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            // Log detallado en caso de error
            $elapsedTime = microtime(true) - $startTime;
            Log::error('Error procesando webhook de Telegram', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'tiempo_procesamiento_ms' => round($elapsedTime * 1000, 2),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determina si se debe activar el modo debug para webhooks
     */
    protected function shouldDebug(): bool
    {
        return config('telegraph.webhook.debug', false) ||
               config('app.debug', false) ||
               env('TELEGRAM_WEBHOOK_DEBUG', false);
    }

    /**
     * Registra información de depuración detallada sobre el webhook
     */
    protected function debugWebhook(Request $request): void
    {
        try {
            $update = json_decode($request->getContent(), true);

            $debugData = [
                'timestamp' => now()->toIso8601String(),
                'ip' => $request->ip(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'query_parameters' => $request->query(),
                'raw_content' => $request->getContent(),
                'parsed_content' => $update,
                'update_id' => $update['update_id'] ?? null,
            ];

            // Analizar el tipo de actualización
            if (isset($update['message'])) {
                $message = $update['message'];
                $debugData['update_type'] = 'message';
                $debugData['chat_id'] = $message['chat']['id'] ?? 'unknown';
                $debugData['chat_type'] = $message['chat']['type'] ?? 'unknown';
                $debugData['from_id'] = $message['from']['id'] ?? 'unknown';
                $debugData['from_username'] = $message['from']['username'] ?? null;
                $debugData['from_first_name'] = $message['from']['first_name'] ?? null;
                $debugData['text'] = $message['text'] ?? 'no text';
                $debugData['date'] = $message['date'] ?? null;
                $debugData['message_id'] = $message['message_id'] ?? null;

                // Detectar si es un comando
                if (isset($message['text']) && str_starts_with($message['text'], '/')) {
                    $parts = explode(' ', $message['text']);
                    $command = ltrim($parts[0], '/');
                    $debugData['command'] = $command;
                    $debugData['arguments'] = array_slice($parts, 1);

                    // Verificar si hay un método correspondiente
                    $methodExists = method_exists($this, $command);
                    $debugData['handler_method_exists'] = $methodExists;
                    $debugData['handler_method'] = $methodExists ? get_class($this) . '::' . $command : 'not found';

                    // Verificar la configuración de logs y los métodos disponibles en el controlador
                    $debugData['available_commands'] = get_class_methods($this);
                    $debugData['log_channels'] = config('logging.channels');
                    $debugData['telegraph_config'] = config('telegraph');
                }

                // Verificar si hay contenido multimedia
                if (isset($message['photo'])) {
                    $debugData['media_type'] = 'photo';
                    $debugData['photo_sizes'] = count($message['photo']);
                } elseif (isset($message['document'])) {
                    $debugData['media_type'] = 'document';
                    $debugData['document_name'] = $message['document']['file_name'] ?? null;
                    $debugData['document_mime'] = $message['document']['mime_type'] ?? null;
                } elseif (isset($message['voice'])) {
                    $debugData['media_type'] = 'voice';
                } elseif (isset($message['video'])) {
                    $debugData['media_type'] = 'video';
                } elseif (isset($message['audio'])) {
                    $debugData['media_type'] = 'audio';
                }
            } elseif (isset($update['callback_query'])) {
                $callbackQuery = $update['callback_query'];
                $debugData['update_type'] = 'callback_query';
                $debugData['from_id'] = $callbackQuery['from']['id'] ?? 'unknown';
                $debugData['from_username'] = $callbackQuery['from']['username'] ?? null;
                $debugData['callback_id'] = $callbackQuery['id'] ?? null;
                $debugData['data'] = $callbackQuery['data'] ?? 'no data';
                $debugData['chat_instance'] = $callbackQuery['chat_instance'] ?? null;

                if (isset($callbackQuery['message'])) {
                    $debugData['message_id'] = $callbackQuery['message']['message_id'] ?? null;
                    $debugData['chat_id'] = $callbackQuery['message']['chat']['id'] ?? null;
                }
            } elseif (isset($update['edited_message'])) {
                $debugData['update_type'] = 'edited_message';
            } elseif (isset($update['channel_post'])) {
                $debugData['update_type'] = 'channel_post';
            } elseif (isset($update['edited_channel_post'])) {
                $debugData['update_type'] = 'edited_channel_post';
            } elseif (isset($update['inline_query'])) {
                $debugData['update_type'] = 'inline_query';
            } elseif (isset($update['chosen_inline_result'])) {
                $debugData['update_type'] = 'chosen_inline_result';
            } elseif (isset($update['shipping_query'])) {
                $debugData['update_type'] = 'shipping_query';
            } elseif (isset($update['pre_checkout_query'])) {
                $debugData['update_type'] = 'pre_checkout_query';
            } elseif (isset($update['poll'])) {
                $debugData['update_type'] = 'poll';
            } elseif (isset($update['poll_answer'])) {
                $debugData['update_type'] = 'poll_answer';
            } elseif (isset($update['my_chat_member'])) {
                $debugData['update_type'] = 'my_chat_member';
            } elseif (isset($update['chat_member'])) {
                $debugData['update_type'] = 'chat_member';
            } elseif (isset($update['chat_join_request'])) {
                $debugData['update_type'] = 'chat_join_request';
            } else {
                $debugData['update_type'] = 'unknown';
            }

            Log::info('Telegram Webhook Debug Detallado', $debugData);

        } catch (\Exception $e) {
            Log::error('Error depurando webhook', [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Maneja el comando /start
     */
    public function start(): void
    {
        try {
            // Primero registramos el chat para evitar problemas
            try {
                $telegraphChat = $this->registerChat();
                Log::info('Chat registrado correctamente antes del inicio', [
                    'chat_id' => $telegraphChat->chat_id,
                    'db_id' => $telegraphChat->id
                ]);
            } catch (\Exception $e) {
                Log::error('Error registrando chat antes del inicio', [
                    'error' => $e->getMessage()
                ]);
                // Continuamos con el proceso aunque falle el registro
            }

            // Obtener información del mensaje y chat
            $chatId = $this->chat->chat_id ?? ($this->message ? $this->message->chat()->id() : null);

            if (!$chatId) {
                Log::error('No se pudo determinar el ID del chat');
                return;
            }

            Log::info('Comando /start recibido', [
                'chat_id' => $chatId,
                'from' => $this->message->from()->username ?? $this->message->from()->firstName ?? 'unknown',
                'timestamp' => now()->toIso8601String(),
                'mensaje_original' => $this->message->text()
            ]);

            $mensaje = <<<HTML
🤖 <b>¡Bienvenido al Bot de I-Free!</b>

Este bot te notificará sobre eventos importantes del sistema de hotspots.

📋 <b>Comandos disponibles:</b>
/start - Mostrar este mensaje
/zonas - Ver zonas disponibles
/registrar [zona_id] - Asociar chat con una zona
/ayuda - Mostrar ayuda detallada

🔧 Para empezar, usa /zonas para ver las zonas disponibles.
HTML;
            // Log para diagnóstico
            \Illuminate\Support\Facades\Log::info('Preparando envío de mensaje start', [
                'chat_id' => $chatId,
                'mensaje' => $mensaje,
                'bot_id' => $this->bot->id,
                'bot_token' => substr($this->bot->token, 0, 5) . '...' . substr($this->bot->token, -5)
            ]);

            // Definimos la URL explícitamente para diagnóstico
            $telegramUrl = config('telegraph.telegram_api_url', 'https://api.telegram.org/');
            \Illuminate\Support\Facades\Log::debug('Configuración Telegram', [
                'api_url' => $telegramUrl,
                'bot_token' => substr($this->bot->token, 0, 5) . '...' . substr($this->bot->token, -5),
                'parse_mode' => config('telegraph.default_parse_mode'),
                'webhook_url' => $this->bot->webhook_url ?? 'no configurada',
                'telegraph_version' => $this->getTelegraphVersion()
            ]);

            // Intentar con Telegraph primero
            try {
                // Obtener la instancia de Telegraph y configurarla
                $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
                $telegraph = $telegraph->bot($this->bot); // Aseguramos que se use el bot correcto y guardamos la instancia

                Log::info('Enviando mensaje con Telegraph', [
                    'método' => 'sendMessage',
                    'chat_id' => $chatId,
                    'texto_longitud' => strlen($mensaje),
                    'parse_mode' => config('telegraph.default_parse_mode'),
                ]);

                // Crear un ID único para rastrear esta operación
                $operationId = uniqid('msg_');
                Log::info("Inicializando operación: {$operationId}");

                // Enviar el mensaje utilizando el cliente Telegraph
                $startTime = microtime(true);
                $response = $telegraph->chat($chatId)
                    ->html($mensaje)
                    ->send();
                $elapsedTime = microtime(true) - $startTime;

                // Log detallado de la respuesta
                \Illuminate\Support\Facades\Log::info('Respuesta API Telegram vía Telegraph', [
                    'operation_id' => $operationId,
                    'tiempo_ms' => round($elapsedTime * 1000, 2),
                    'response' => $response,
                    'response_type' => gettype($response),
                    'response_class' => is_object($response) ? get_class($response) : 'no es objeto',
                    'response_json' => json_encode($response),
                    'chat_id' => $chatId,
                    'bot_id' => $this->bot->id,
                    'bot_name' => $this->bot->name,
                    'timestamp' => now()->toIso8601String()
                ]);

                // Verificar si la respuesta indica éxito
                $success = false;
                if (is_array($response) && isset($response['ok'])) {
                    $success = $response['ok'] === true;
                } elseif (is_object($response) && method_exists($response, 'successful')) {
                    $success = $response->successful();
                }

                if (!$success) {
                    throw new \Exception('La respuesta de Telegraph no indica éxito');
                }
            } catch (\Exception $e) {
                // Si falla Telegraph, intentar con método directo
                Log::warning('Telegraph falló, intentando método directo', [
                    'error' => $e->getMessage()
                ]);

                // Enviar usando método directo
                $response = $this->sendMessageDirect($chatId, $mensaje);

                if (isset($response['ok']) && $response['ok'] === true) {
                    Log::info('Mensaje enviado correctamente usando método directo');
                } else {
                    Log::error('Ambos métodos de envío fallaron', [
                        'telegraph_error' => $e->getMessage(),
                        'direct_error' => $response['description'] ?? 'Sin descripción'
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Capturar cualquier error durante el envío
            \Illuminate\Support\Facades\Log::error('Error general enviando mensaje start', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Obtiene la versión de Telegraph instalada
     *
     * @return string
     */
    protected function getTelegraphVersion(): string
    {
        try {
            $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

            if (is_array($composerLock) && isset($composerLock['packages'])) {
                foreach ($composerLock['packages'] as $package) {
                    if ($package['name'] === 'defstudio/telegraph') {
                        return $package['version'] ?? 'desconocida';
                    }
                }
            }

            return 'no encontrada';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    /**
     * Registra un chat si no existe y lo asocia con el bot actual
     *
     * @return \DefStudio\Telegraph\Models\TelegraphChat
     */
    protected function registerChat(): \DefStudio\Telegraph\Models\TelegraphChat
    {
        try {
            Log::info('Iniciando registro de chat', [
                'chat_id' => $this->chat->chat_id ?? ($this->message ? $this->message->chat()->id() : null),
                'bot_id' => $this->bot->id ?? null
            ]);

            // Verificar si el chat ya existe en la base de datos
            $chatId = $this->chat->chat_id ?? ($this->message ? $this->message->chat()->id() : null);

            if (!$chatId) {
                throw new \Exception('No se pudo determinar el ID del chat');
            }

            $telegraphChat = \DefStudio\Telegraph\Models\TelegraphChat::where('chat_id', $chatId)
                ->where('telegraph_bot_id', $this->bot->id)
                ->first();

            // Si no existe, crear un nuevo chat
            if (!$telegraphChat) {
                Log::info('Creando nuevo chat', [
                    'chat_id' => $chatId,
                    'bot_id' => $this->bot->id
                ]);

                // Obtener nombre del chat
                $chatName = $this->getChatName();

                // Crear el chat en la base de datos
                $telegraphChat = \DefStudio\Telegraph\Models\TelegraphChat::create([
                    'chat_id' => $chatId,
                    'name' => $chatName,
                    'telegraph_bot_id' => $this->bot->id,
                ]);

                Log::info('Chat creado correctamente', [
                    'chat_id' => $telegraphChat->chat_id,
                    'name' => $telegraphChat->name,
                    'id' => $telegraphChat->id
                ]);
            } else {
                Log::info('Chat ya existente', [
                    'chat_id' => $telegraphChat->chat_id,
                    'name' => $telegraphChat->name,
                    'id' => $telegraphChat->id
                ]);
            }

            // Verificar también si existe en la tabla personalizada telegram_chats
            $customChat = TelegramChat::where('chat_id', $chatId)->first();

            if (!$customChat) {
                Log::info('Creando entrada en tabla personalizada telegram_chats', [
                    'chat_id' => $chatId
                ]);

                $tipo = $this->getChatType();

                // Crear el registro en la tabla personalizada
                $customChat = TelegramChat::create([
                    'chat_id' => $chatId,
                    'nombre' => $chatName,
                    'tipo' => $tipo,
                ]);

                Log::info('Entrada en tabla personalizada creada', [
                    'id' => $customChat->id,
                    'tipo' => $customChat->tipo
                ]);
            }

            return $telegraphChat;
        } catch (\Exception $e) {
            Log::error('Error en registerChat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-lanzar la excepción para que se maneje en el nivel superior
        }
    }

    /**
     * Obtiene el nombre del chat basado en su tipo
     *
     * @return string
     */
    protected function getChatName(): string
    {
        try {
            $chat = $this->message->chat();

            if ($chat->type() === 'private') {
                $firstName = $this->message->from()->firstName() ?? '';
                $lastName = $this->message->from()->lastName() ?? '';
                $username = $this->message->from()->username() ?? '';

                if ($username) {
                    return "@{$username} ({$firstName} {$lastName})";
                }

                return trim("{$firstName} {$lastName}");
            }

            if ($chat->type() === 'group' || $chat->type() === 'supergroup') {
                return $chat->title() ?? 'Grupo sin nombre';
            }

            if ($chat->type() === 'channel') {
                return $chat->title() ?? 'Canal sin nombre';
            }

            return 'Chat #' . $chat->id();
        } catch (\Exception $e) {
            Log::error('Error obteniendo nombre de chat', [
                'error' => $e->getMessage()
            ]);

            return 'Chat sin nombre';
        }
    }

    /**
     * Obtiene el tipo de chat
     *
     * @return string
     */
    protected function getChatType(): string
    {
        try {
            $type = $this->message->chat()->type();

            // Mapear el tipo de chat de Telegram a nuestros tipos personalizados
            switch ($type) {
                case 'private':
                    return 'personal';
                case 'group':
                case 'supergroup':
                    return 'grupo';
                case 'channel':
                    return 'canal';
                default:
                    return 'otro';
            }
        } catch (\Exception $e) {
            Log::error('Error obteniendo tipo de chat', [
                'error' => $e->getMessage()
            ]);

            return 'desconocido';
        }
    }

    /**
     * Método de respaldo para enviar mensajes usando CURL directamente
     * Usar cuando Telegraph falle al enviar mensajes
     *
     * @param string $chatId ID del chat donde enviar el mensaje
     * @param string $text Texto del mensaje a enviar
     * @param string $parseMode Modo de parseo (HTML, Markdown, MarkdownV2)
     * @return array Respuesta de la API
     */
    protected function sendMessageDirect(string $chatId, string $text, string $parseMode = 'HTML'): array
    {
        try {
            Log::info('Enviando mensaje directo con CURL', [
                'chat_id' => $chatId,
                'text_length' => strlen($text),
                'parse_mode' => $parseMode
            ]);

            // Construir la URL de la API
            $telegramApiUrl = config('telegraph.telegram_api_url', 'https://api.telegram.org/');
            $apiUrl = rtrim($telegramApiUrl, '/') . '/bot' . $this->bot->token . '/sendMessage';

            // Preparar los datos para la solicitud
            $data = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode
            ];

            // Hacer la solicitud mediante curl
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            // Medición de tiempo
            $startTime = microtime(true);
            $response = curl_exec($ch);
            $elapsedTime = microtime(true) - $startTime;

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                curl_close($ch);

                Log::error('Error enviando mensaje directo con CURL', [
                    'error' => "({$errno}) {$error}",
                    'chat_id' => $chatId
                ]);

                return [
                    'ok' => false,
                    'error_code' => $errno,
                    'description' => $error
                ];
            }

            curl_close($ch);

            // Parsear la respuesta
            $result = json_decode($response, true);

            Log::info('Respuesta de envío directo con CURL', [
                'http_code' => $httpCode,
                'tiempo_ms' => round($elapsedTime * 1000, 2),
                'response' => $result
            ]);

            return $result ?: [
                'ok' => false,
                'description' => 'Respuesta vacía o inválida'
            ];
        } catch (\Exception $e) {
            Log::error('Excepción enviando mensaje directo con CURL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'ok' => false,
                'description' => 'Excepción: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sobrescribe el método setupChat de Telegraph para asegurarse de que siempre existe un chat
     * Este método es llamado directamente por WebhookHandler y puede causar NotFoundHttpException
     */
    protected function setupChat(): void
    {
        try {
            Log::info('Ejecutando setupChat');

            // Intentar obtener el chat desde la solicitud webhook
            if (!$this->message) {
                Log::warning('Ejecutando setupChat sin mensaje disponible');
                return;
            }

            $chatId = $this->message->chat()->id();
            if (!$chatId) {
                Log::warning('No se pudo determinar el ID del chat en setupChat');
                return;
            }

            // Verificar si el chat existe y configurarlo
            $telegraphChat = $this->registerChat();  // Usamos nuestro método personalizado de registro

            if ($telegraphChat) {
                // Configurar el chat en la instancia actual
                $this->chat = $telegraphChat;

                Log::info('Chat configurado correctamente en setupChat', [
                    'chat_id' => $telegraphChat->chat_id,
                    'db_id' => $telegraphChat->id,
                    'name' => $telegraphChat->name
                ]);
            } else {
                Log::error('No se pudo configurar el chat en setupChat');
            }
        } catch (\Exception $e) {
            Log::error('Error en setupChat', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Sobreescribe el método message para manejar mensajes generales
     * Este método es llamado por Telegraph cuando se recibe un mensaje que no es un comando
     */
    public function message($data): void
    {
        try {
            Log::info('Método message invocado', [
                'data' => $data
            ]);

            // Procesar el mensaje general
            $this->handleChatMessage();
        } catch (\Exception $e) {
            Log::error('Error en el método message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Maneja mensajes generales que no son comandos específicos
     * Este método se llama cuando se recibe un mensaje que no es un comando conocido
     */
    protected function handleChatMessage(): void
    {
        try {
            // Verificamos que tengamos un mensaje para procesar
            if (!$this->message) {
                Log::warning('Intento de procesar mensaje sin contenido');
                return;
            }

            // Aseguramos que el chat esté correctamente configurado
            try {
                // Primero intentamos usar setupChat que es el método que está fallando
                $this->setupChat();

                // Adicionalmente registramos el chat si no existe
                $telegraphChat = $this->registerChat();
                Log::info('Chat registrado para mensaje genérico', [
                    'chat_id' => $telegraphChat->chat_id,
                    'db_id' => $telegraphChat->id
                ]);
            } catch (\Exception $e) {
                Log::error('Error configurando chat para mensaje genérico', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                // Continuamos aunque falle el registro
            }

            // Obtener el texto del mensaje
            $texto = $this->message->text();
            $chatId = $this->message->chat()->id();
            $username = $this->message->from()->username() ?? $this->message->from()->firstName() ?? 'usuario';

            Log::info('Mensaje general recibido', [
                'texto' => $texto,
                'chat_id' => $chatId,
                'from' => $username,
                'chat_configurado' => isset($this->chat) ? 'sí' : 'no'
            ]);

            // Preparar respuesta
            $respuesta = <<<HTML
👋 Hola <b>{$username}</b>!

Has enviado: "<i>{$texto}</i>"

Puedo ayudarte con los siguientes comandos:

📋 <b>Comandos disponibles:</b>
/start - Mensaje de bienvenida
/zonas - Ver zonas disponibles
/registrar [zona_id] - Asociar chat con una zona
/ayuda - Mostrar ayuda detallada
HTML;

            // Intentar enviar respuesta
            try {
                // Verificamos si la instancia Telegraph está disponible o usamos método directo
                if (isset($this->chat) && $this->chat) {
                    try {
                        // Intentar con Telegraph primero
                        $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
                        $telegraph = $telegraph->bot($this->bot);

                        Log::info('Enviando respuesta a mensaje genérico con Telegraph', [
                            'chat_id' => $chatId
                        ]);

                        $response = $telegraph->chat($chatId)
                            ->html($respuesta)
                            ->send();

                        if (isset($response['ok']) && $response['ok'] === true) {
                            Log::info('Respuesta enviada correctamente con Telegraph');
                            return;
                        } else {
                            throw new \Exception('Falló el envío con Telegraph');
                        }
                    } catch (\Exception $e) {
                        // Si falla Telegraph, continuamos con método directo
                        Log::warning('Falló envío con Telegraph, usando método directo', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Usar método directo como respaldo o principal si no hay chat configurado
                Log::info('Enviando respuesta con método directo', [
                    'chat_id' => $chatId
                ]);

                $response = $this->sendMessageDirect($chatId, $respuesta);

                if (isset($response['ok']) && $response['ok'] === true) {
                    Log::info('Respuesta enviada correctamente a mensaje genérico');
                } else {
                    Log::error('Error enviando respuesta a mensaje genérico', [
                        'error' => $response['description'] ?? 'Sin descripción'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Excepción enviando respuesta a mensaje genérico', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error general procesando mensaje de chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Método que intercepta mensajes no reconocidos como comandos
     * Este método es llamado por Telegraph cuando no encuentra un comando específico
     */
    public function handleUnknownCommand(): void
    {
        try {
            if (!$this->message) {
                Log::warning('Intento de manejar comando desconocido sin mensaje');
                return;
            }

            // Extraer el comando desconocido para proporcionar ayuda específica
            $texto = $this->message->text();
            $comando = '';

            if (str_starts_with($texto, '/')) {
                $parts = explode(' ', $texto);
                $comando = ltrim($parts[0], '/');
            }

            $chatId = $this->message->chat()->id();

            Log::info('Interceptando comando desconocido', [
                'texto' => $texto,
                'comando' => $comando,
                'chat_id' => $chatId
            ]);

            // Verificamos si el chat está configurado
            try {
                $this->setupChat();
            } catch (\Exception $e) {
                Log::warning('Error configurando chat en handleUnknownCommand', [
                    'error' => $e->getMessage()
                ]);
            }

            $username = $this->message->from()->username() ??
                      $this->message->from()->firstName() ??
                      'usuario';

            // Personalizar la respuesta según el comando
            if (!empty($comando)) {
                $respuesta = <<<HTML
⚠️ <b>Comando desconocido</b>: /{$comando}

Hola <b>{$username}</b>, el comando que intentas utilizar no está disponible.

📋 <b>Comandos disponibles:</b>
/start - Mensaje de bienvenida
/zonas - Ver zonas disponibles
/registrar [zona_id] - Asociar chat con una zona
/ayuda - Mostrar ayuda detallada
HTML;
            } else {
                // Si no es un comando específico, manejar como mensaje general
                $this->handleChatMessage();
                return;
            }

            // Intentar enviar respuesta directamente (más confiable)
            try {
                $response = $this->sendMessageDirect($chatId, $respuesta);

                if (isset($response['ok']) && $response['ok'] === true) {
                    Log::info('Respuesta enviada correctamente para comando desconocido');
                } else {
                    Log::error('Error enviando respuesta a comando desconocido', [
                        'error' => $response['description'] ?? 'Sin descripción'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Excepción enviando respuesta a comando desconocido', [
                    'error' => $e->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error en handleUnknownCommand', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Este método interceptará comandos específicos que no están implementados
     * Se llama mediante magia de PHP cuando se invoca un método que no existe
     */
    public function __call($method, $parameters): void
    {
        try {
            // Verificar si parece ser un comando de Telegram
            if (ctype_lower($method[0]) && !str_contains($method, '_')) {
                Log::info('Interceptando llamada a comando no implementado', [
                    'método' => $method
                ]);

                // Obtener información del chat si está disponible
                $chatId = null;
                $username = 'usuario';

                if ($this->message) {
                    $chatId = $this->message->chat()->id();
                    $username = $this->message->from()->username() ??
                               $this->message->from()->firstName() ??
                               'usuario';
                }

                if ($chatId) {
                    // Enviar respuesta informando que el comando está en desarrollo
                    $respuesta = <<<HTML
🔧 <b>Comando en desarrollo</b>: /{$method}

Hola <b>{$username}</b>, este comando está siendo implementado actualmente.

Por favor, utiliza uno de los comandos disponibles:
/start - Mensaje de bienvenida
/zonas - Ver zonas disponibles
/ayuda - Mostrar ayuda detallada
HTML;

                    $this->sendMessageDirect($chatId, $respuesta);
                }

                // No lanzar una excepción, solo registrar
                Log::info("Comando /{$method} no implementado aún");
                return;
            }

            // Si no es un comando, pasar al manejador predeterminado
            parent::__call($method, $parameters);
        } catch (\Exception $e) {
            Log::error('Error en __call para método ' . $method, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
