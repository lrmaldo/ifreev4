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
            Log::info('Comando /start recibido', [
                'chat_id' => $this->chat->chat_id,
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
                'chat_id' => $this->chat->chat_id,
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

            // Obtener la instancia de Telegraph y configurarla
            $telegraph = app(\DefStudio\Telegraph\Telegraph::class);
            $telegraph = $telegraph->bot($this->bot); // Aseguramos que se use el bot correcto y guardamos la instancia

            Log::info('Enviando mensaje con Telegraph', [
                'método' => 'sendMessage',
                'chat_id' => $this->chat->chat_id,
                'texto_longitud' => strlen($mensaje),
                'parse_mode' => config('telegraph.default_parse_mode'),
            ]);

            // Preparamos la API URL completa para diagnóstico
            $apiUrl = $telegramUrl . 'bot' . $this->bot->token . '/sendMessage';
            Log::debug('API URL para diagnóstico', ['url' => $apiUrl]);

            // Crear un ID único para rastrear esta operación
            $operationId = uniqid('msg_');
            Log::info("Inicializando operación: {$operationId}");

            // Enviar el mensaje utilizando el cliente Telegraph
            $startTime = microtime(true);
            $response = $telegraph->chat($this->chat->chat_id)
                ->html($mensaje)
                ->send();
            $elapsedTime = microtime(true) - $startTime;

            // Log detallado de la respuesta
            \Illuminate\Support\Facades\Log::info('Respuesta API Telegram', [
                'operation_id' => $operationId,
                'tiempo_ms' => round($elapsedTime * 1000, 2),
                'response' => $response,
                'response_type' => gettype($response),
                'response_class' => is_object($response) ? get_class($response) : 'no es objeto',
                'response_json' => json_encode($response),
                'chat_id' => $this->chat->chat_id,
                'bot_id' => $this->bot->id,
                'bot_name' => $this->bot->name,
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            // Capturar cualquier error durante el envío
            \Illuminate\Support\Facades\Log::error('Error enviando mensaje start', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        try {
            // Registramos el chat después de enviar el mensaje inicial
            Log::info('Registrando chat después de comando start');
            $this->registerChat();
        } catch (\Exception $e) {
            Log::error('Error registrando chat después de comando start', [
                'error' => $e->getMessage(),
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
