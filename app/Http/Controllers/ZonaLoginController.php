<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Traits\RenderizaFormFields;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ZonaLoginController extends Controller
{
    use RenderizaFormFields;
    /**
     * Maneja las solicitudes POST enviadas desde el portal cautivo Mikrotik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id  ID de la zona (puede ser el ID real o personalizado)
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, $id)
    {
        try {
            // Buscar la zona primero por id_personalizado, luego por id
            $zona = \App\Models\Zona::where('id_personalizado', $id)->first();

            if (!$zona) {
                $zona = \App\Models\Zona::find($id);
            }

            if (!$zona) {
                \Log::warning("Zona no encontrada con ID: {$id}");

                // FALLBACK: Si es ID 10, intentar redirigir a zona por defecto
                if ($id == '10') {
                    $zonaFallback = \App\Models\Zona::first(); // Cualquier zona disponible
                    if ($zonaFallback) {
                        \Log::info("Redirigiendo ID 10 a zona fallback: {$zonaFallback->id}");
                        return redirect()->route('zona.login.mikrotik', ['id' => $zonaFallback->id]);
                    }
                }

                // Mostrar página de error personalizada en lugar de abort 404
                return view('portal.zona-no-encontrada', [
                    'zona_id' => $id,
                    'mensaje' => 'La zona solicitada no existe o ha sido desactivada.',
                    'zonas_disponibles' => \App\Models\Zona::pluck('nombre', 'id')->toArray()
                ]);
            }        } catch (\Exception $e) {
            \Log::error("Error al buscar zona ID {$id}: " . $e->getMessage());

            return view('portal.zona-no-encontrada', [
                'zona_id' => $id,
                'mensaje' => 'Error al acceder al portal cautivo. Por favor contacte al administrador.'
            ]);
        }

        // Comprobar en el controlador que estos valores estén presentes
        $mikrotikData = [
            'link-login-only' => $request->get('link-login-only', ''),
            'link-orig' => $request->get('link-orig', ''),
            'link-orig-esc' => $request->get('link-orig-esc', ''),
            'mac' => $request->get('mac', ''),
            'mac-esc' => $request->get('mac-esc', ''),
            'chap-id' => $request->get('chap-id', ''),
            'chap-challenge' => $request->get('chap-challenge', ''),
            'error' => $request->get('error', '')
        ];

        // Log detallado para debugging en producción
        \Log::info("Acceso al portal cautivo", [
            'zona_id' => $zona->id,
            'zona_nombre' => $zona->nombre,
            'zona_activa' => $zona->activo,
            'ip_cliente' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'mac_address' => $mikrotikData['mac'] ?? 'no-mac',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);

        // Registrar métrica de entrada al portal
        $this->registrarMetricaEntrada($zona, $mikrotikData);

        // Aquí puedes procesar los datos como sea necesario
        // Por ejemplo, guardarlos en una base de datos, verificar si el usuario está autorizado, etc.

        // Preparar información de métrica
        // Preparar información básica de métrica
        $metricaInfo = [
            'zona_id' => $zona->id,
            'mac_address' => $mikrotikData['mac'] ?? 'unknown',
            'dispositivo' => (new \Jenssegers\Agent\Agent())->device() ?: 'Desconocido',
            'navegador' => (new \Jenssegers\Agent\Agent())->browser() . ' ' . (new \Jenssegers\Agent\Agent())->version((new \Jenssegers\Agent\Agent())->browser()),
            'tipo_visual' => 'portal_cautivo', // Valor predeterminado que se actualizará según contenido
            'tiempo_inicio' => now()
        ];

        // Cargar vista unificada del portal cautivo en lugar de redirect
        return $this->mostrarPortalCautivo($zona, $mikrotikData, $metricaInfo);
    }

    /**
     * Mostrar vista unificada del portal cautivo
     *
     * @param  \App\Models\Zona  $zona
     * @param  array  $mikrotikData
     * @param  array  $metricaInfo
     * @return \Illuminate\Http\Response
     */
    protected function mostrarPortalCautivo($zona, $mikrotikData, $metricaInfo)
    {
        // Registrar la entrada al método para depuración
        \Log::info("Entrando a mostrarPortalCautivo para zona: {$zona->id}, MAC: " . ($mikrotikData['mac'] ?? 'no-mac'));

        $macAddress = $mikrotikData['mac'] ?? '';

        // Verificar si la MAC ya tiene respuesta de formulario
        $respuestaExistente = null;
        $mostrarFormulario = false;

        if ($macAddress) {
            $respuestaExistente = \App\Models\FormResponse::where('zona_id', $zona->id)
                ->where('mac_address', $macAddress)
                ->first();
        }

        // NUEVA LÓGICA: Registrar/actualizar métrica independientemente del formulario
        $this->registrarMetricaCompleta($zona->id, $macAddress, $metricaInfo);

        // Determinar si mostrar formulario SOLO si no existe respuesta previa
        if (!$respuestaExistente && $zona->tipo_registro !== 'sin_registro' && $zona->campos->count() > 0) {
            $mostrarFormulario = true;
        }

        // Obtener los campos del formulario con su HTML renderizado solo si es necesario
        $formFields = [];
        $camposHtml = [];

        if ($mostrarFormulario) {
            $formFields = \App\Models\FormField::where('zona_id', $zona->id)->orderBy('orden')->get();

            // Usar el trait RenderizaFormFields para generar el HTML
            foreach ($formFields as $campo) {
                $camposHtml[] = $this->renderizarCampo($campo);
            }
        }

        // Obtener campañas activas para mostrar en el carrusel/video
        $campanasActivas = $zona->getCampanasActivas();
        $imagenes = [];
        $videoUrl = '';
        $campanaSeleccionada = null;

        // Determinar tipo de contenido (imagen, video)
        if (!$campanasActivas->isEmpty()) {
            // Separar videos e imágenes
            $videos = $campanasActivas->where('tipo', 'video')->filter(function($campana) {
                return !empty($campana->archivo_path);
            });

            $imagenesCollection = $campanasActivas->where('tipo', 'imagen')->filter(function($campana) {
                return !empty($campana->archivo_path);
            });

            // Verificar la configuración de la zona y la sesión para alternar entre video e imagen
            $tipoPreferido = $zona->seleccion_campanas ?? 'aleatorio';

            // Implementar un sistema robusto de cookies + sesión para controlar la alternancia
            $cookieKey = 'ultimo_tipo_zona_' . $zona->id;
            $ultimoTipoMostradoCookie = request()->cookie($cookieKey);
            $ultimoTipoMostradoSesion = session('ultimo_tipo_mostrado_' . $zona->id, '');
            $ultimoTipoMostrado = '';

            // Estrategia de prioridad: 1° Cookie, 2° Sesión, 3° Nada (se trata como primera vez)
            if ($ultimoTipoMostradoCookie && in_array($ultimoTipoMostradoCookie, ['video', 'imagen'])) {
                $ultimoTipoMostrado = $ultimoTipoMostradoCookie;
                \Log::info("Usando valor de COOKIE para alternar: {$ultimoTipoMostradoCookie}");

                // Si la sesión está vacía o es diferente, actualizamos la sesión para sincronizarla
                if ($ultimoTipoMostradoSesion !== $ultimoTipoMostrado) {
                    session(['ultimo_tipo_mostrado_' . $zona->id => $ultimoTipoMostrado]);
                    session()->save();
                    \Log::info("Sincronizando sesión con cookie: {$ultimoTipoMostrado}");
                }
            } else if ($ultimoTipoMostradoSesion && in_array($ultimoTipoMostradoSesion, ['video', 'imagen'])) {
                $ultimoTipoMostrado = $ultimoTipoMostradoSesion;
                \Log::info("No se encontró cookie, usando valor de SESIÓN: {$ultimoTipoMostradoSesion}");
            } else {
                \Log::info("No se encontró cookie ni sesión para alternar, se tratará como primera visita");
            }

            // Registrar en log el método de selección para depuración
            \Log::info("Método de selección de campañas: {$tipoPreferido} para zona: {$zona->id}. Último tipo mostrado: {$ultimoTipoMostrado}");

            // Contar campañas disponibles por tipo para diagnóstico
            \Log::info("Videos disponibles: " . $videos->count() . ", Imágenes disponibles: " . $imagenesCollection->count());

            // Decisión de mostrar video o imagen
            $mostrarVideo = false;

            // Algoritmo mejorado para mejor alternancia entre tipos de contenido
            if ($tipoPreferido === 'aleatorio') {
                // En modo aleatorio, garantizamos alternancia estricta
                if (!$videos->isEmpty() && !$imagenesCollection->isEmpty()) {
                    // Antes de decidir, vamos a registrar detalladamente el valor de la sesión
                    $sessionId = session()->getId();
                    $sessionKey = 'ultimo_tipo_mostrado_' . $zona->id;
                    $sessionValue = session($sessionKey, '');
                    \Log::info("SESIÓN ID: {$sessionId}, CLAVE: {$sessionKey}, VALOR ACTUAL: '{$sessionValue}'");

                    // Si hay ambos tipos de contenido disponibles
                    if ($ultimoTipoMostrado === 'video') {
                        $mostrarVideo = false;
                        \Log::info("Alternancia estricta: último fue video, ahora mostramos imagen");
                    } else if ($ultimoTipoMostrado === 'imagen') {
                        $mostrarVideo = true;
                        \Log::info("Alternancia estricta: último fue imagen, ahora mostramos video");
                    } else {
                        // Si es primera visualización o sesión vacía, implementamos verdadera selección aleatoria
                        $mostrarVideo = (mt_rand(0, 1) === 1);
                        $tipoInicial = $mostrarVideo ? "VIDEO" : "IMAGEN";
                        \Log::info("Primera visualización o sesión vacía: selección aleatoria = {$tipoInicial}");
                    }
                } else {
                    // Si solo hay un tipo disponible, usamos lo que haya
                    $mostrarVideo = !$videos->isEmpty();
                    $tipoMostrado = $mostrarVideo ? "videos" : "imágenes";
                    \Log::info("Solo hay un tipo disponible: {$tipoMostrado}");
                }
            } else if ($tipoPreferido === 'prioridad') {
                // En modo prioridad, buscamos la campaña con mayor prioridad
                // pero respetando alternancia cuando sea posible

                // Si hay ambos tipos de contenido, verificamos prioridades
                if (!$videos->isEmpty() && !$imagenesCollection->isEmpty()) {
                    // Obtenemos la prioridad más alta (número más bajo) para cada tipo
                    $mejorVideoP = $videos->min('prioridad') ?? 999;
                    $mejorImagenP = $imagenesCollection->min('prioridad') ?? 999;

                    // Si hay empate en prioridades, alternamos basado en última visualización
                    if ($mejorVideoP == $mejorImagenP) {
                        if ($ultimoTipoMostrado === 'video') {
                            $mostrarVideo = false;
                            \Log::info("Prioridades iguales ({$mejorVideoP}), alternando: último fue video, ahora imagen");
                        } else {
                            $mostrarVideo = true;
                            \Log::info("Prioridades iguales ({$mejorVideoP}), alternando: último fue imagen o ninguno, ahora video");
                        }
                    } else {
                        // Elegimos la mejor prioridad
                        $mostrarVideo = ($mejorVideoP < $mejorImagenP);
                        $mejorPrioridad = $mostrarVideo ? $mejorVideoP : $mejorImagenP;
                        $tipo = $mostrarVideo ? "video" : "imagen";
                        \Log::info("Seleccionando por prioridad: {$tipo} con prioridad {$mejorPrioridad}");
                    }
                } else {
                    // Si solo hay un tipo disponible, usamos lo que haya
                    $mostrarVideo = !$videos->isEmpty();
                    \Log::info("Solo hay un tipo disponible en modo prioridad: " . ($mostrarVideo ? "videos" : "imágenes"));
                }
            } else if ($tipoPreferido === 'video') {
                // Si la preferencia explícita es video
                if (!$videos->isEmpty()) {
                    // Si hay videos disponibles, mostrar video
                    $mostrarVideo = true;
                    \Log::info("Seleccionando video por preferencia explícita de configuración");
                } else if (!$imagenesCollection->isEmpty()) {
                    // Si no hay videos pero hay imágenes, mostrar imágenes como fallback
                    $mostrarVideo = false;
                    \Log::info("No hay videos disponibles, mostrando imágenes como fallback");
                }
            } else if ($tipoPreferido === 'imagen') {
                // Si la preferencia explícita es imagen
                if (!$imagenesCollection->isEmpty()) {
                    // Si hay imágenes disponibles, mostrar imágenes
                    $mostrarVideo = false;
                    \Log::info("Seleccionando imagen por preferencia explícita de configuración");
                } else if (!$videos->isEmpty()) {
                    // Si no hay imágenes pero hay videos, mostrar videos como fallback
                    $mostrarVideo = true;
                    \Log::info("No hay imágenes disponibles, mostrando videos como fallback");
                }
            } else {
                // Cualquier otro caso, intentar alternar lo mejor posible
                if (!$videos->isEmpty() && !$imagenesCollection->isEmpty()) {
                    if ($ultimoTipoMostrado === 'video') {
                        $mostrarVideo = false;
                        \Log::info("Caso desconocido con ambos tipos, alternando: último fue video, ahora imagen");
                    } else {
                        $mostrarVideo = true;
                        \Log::info("Caso desconocido con ambos tipos, alternando: último fue imagen o ninguno, ahora video");
                    }
                } else {
                    // Si solo hay un tipo disponible, usamos lo que haya
                    $mostrarVideo = !$videos->isEmpty();
                    \Log::info("Caso desconocido, solo hay un tipo disponible: " . ($mostrarVideo ? "videos" : "imágenes"));
                }
            }

            // Seleccionar campaña según la decisión
            if ($mostrarVideo && !$videos->isEmpty()) {
                // Si toca video y hay videos disponibles
                if ($tipoPreferido === 'prioridad') {
                    // En modo prioridad, elegimos el video con mejor prioridad (número más bajo)
                    $mejorPrioridad = $videos->min('prioridad');
                    $videosConMejorPrioridad = $videos->where('prioridad', $mejorPrioridad);
                    $campanaSeleccionada = $videosConMejorPrioridad->random();
                } else {
                    // En modo aleatorio o cualquier otro, elegimos un video al azar
                    $campanaSeleccionada = $videos->random();
                }

                $videoUrl = \Storage::url($campanaSeleccionada->archivo_path);

                // Guardar en sesión para persistencia robusta
                $sessionKey = 'ultimo_tipo_mostrado_' . $zona->id;
                session([$sessionKey => 'video']);
                session()->save();

                // Verificar que la sesión se haya guardado correctamente
                $sessionValueVerificacion = session($sessionKey);
                if ($sessionValueVerificacion !== 'video') {
                    \Log::warning("⚠️ Posible problema al guardar sesión - Esperado: 'video', Actual: '{$sessionValueVerificacion}'");
                }

                // Preparar cookie para respuesta final
                $cookieKey = 'ultimo_tipo_zona_' . $zona->id;
                $cookieValue = 'video';
                \Log::info("VIDEO MOSTRADO - Se establecerá cookie {$cookieKey}={$cookieValue}");
                \Log::info("VIDEO MOSTRADO - Guardada sesión '{$sessionValueVerificacion}' para zona {$zona->id}");
                \Log::info("Seleccionado video: ID {$campanaSeleccionada->id}, '{$campanaSeleccionada->nombre}'");

                // Actualizar tipo_visual en la métrica
                $metricaInfo['tipo_visual'] = 'video';
            } else if (!$imagenesCollection->isEmpty()) {
                // Si no hay videos o toca mostrar imágenes
                // Guardar en sesión para persistencia robusta
                $sessionKey = 'ultimo_tipo_mostrado_' . $zona->id;
                session([$sessionKey => 'imagen']);
                session()->save();

                // Verificar que la sesión se haya guardado correctamente
                $sessionValueVerificacion = session($sessionKey);
                if ($sessionValueVerificacion !== 'imagen') {
                    \Log::warning("⚠️ Posible problema al guardar sesión - Esperado: 'imagen', Actual: '{$sessionValueVerificacion}'");
                }

                // Preparar cookie para respuesta final
                $cookieKey = 'ultimo_tipo_zona_' . $zona->id;
                $cookieValue = 'imagen';
                \Log::info("IMAGEN MOSTRADA - Se establecerá cookie {$cookieKey}={$cookieValue}");
                \Log::info("IMAGEN MOSTRADA - Guardada sesión '{$sessionValueVerificacion}' para zona {$zona->id}");

                // Para imágenes, procedemos diferente según el método de selección
                if ($tipoPreferido === 'prioridad') {
                    // En modo prioridad, ordenamos por prioridad y seleccionamos las mejores
                    $mejorPrioridad = $imagenesCollection->min('prioridad');
                    $imagenesConMejorPrioridad = $imagenesCollection->where('prioridad', $mejorPrioridad);

                    // Obtener todas las imágenes con mejor prioridad para el carrusel
                    foreach ($imagenesConMejorPrioridad as $campana) {
                        $imagenes[] = \Storage::url($campana->archivo_path);
                    }
                    $campanaSeleccionada = $imagenesConMejorPrioridad->first();
                    // Actualizar tipo_visual en la métrica
                    $metricaInfo['tipo_visual'] = 'imagen';
                } else {
                    // En modo aleatorio, mostramos todas las imágenes
                    foreach ($imagenesCollection as $campana) {
                        $imagenes[] = \Storage::url($campana->archivo_path);
                    }
                    // Actualizar tipo_visual en la métrica
                    $metricaInfo['tipo_visual'] = 'imagen';
                    $campanaSeleccionada = $imagenesCollection->first();
                }

                \Log::info("Seleccionadas " . count($imagenes) . " imágenes, primera: ID {$campanaSeleccionada->id}, '{$campanaSeleccionada->nombre}'");
            }
        }

        // Verificar si se debe mostrar formulario (ya calculado arriba)
        // $mostrarFormulario ya está definido

        // Tiempo de visualización
        $tiempoVisualizacion = $zona->tiempo_visualizacion ?? 15;

        // DEBUG: Logs para depurar el problema del modal
        \Log::info("=== DEBUG MODAL ENLACE ===");
        \Log::info("Zona ID: {$zona->id}");
        \Log::info("Campaña seleccionada existe: " . ($campanaSeleccionada ? 'SÍ' : 'NO'));
        if ($campanaSeleccionada) {
            \Log::info("Campaña ID: " . ($campanaSeleccionada->id ?? 'N/A'));
            \Log::info("Campaña título: " . ($campanaSeleccionada->titulo ?? 'N/A'));
            \Log::info("Campaña enlace: " . ($campanaSeleccionada->enlace ?? 'VACÍO'));
            \Log::info("Campaña tipo: " . ($campanaSeleccionada->tipo ?? 'N/A'));
        }
        \Log::info("Video URL: " . ($videoUrl ? 'SÍ' : 'NO'));
        \Log::info("Imágenes count: " . count($imagenes));
        \Log::info("Mostrar formulario: " . ($mostrarFormulario ? 'SÍ' : 'NO'));
        \Log::info("=== FIN DEBUG MODAL ===");

        // Preparar la vista
        $view = view('portal.formulario-cautivo', compact(
            'zona',
            'mikrotikData',
            'metricaInfo',
            'formFields',
            'camposHtml',
            'imagenes',
            'videoUrl',
            'campanaSeleccionada',
            'mostrarFormulario',
            'tiempoVisualizacion',
            'respuestaExistente'
        ));

        // Prepara los datos para la vista de depuración
        $viewData = compact(
            'zona',
            'mikrotikData',
            'metricaInfo',
            'formFields',
            'camposHtml',
            'imagenes',
            'videoUrl',
            'campanaSeleccionada',
            'mostrarFormulario',
            'tiempoVisualizacion',
            'respuestaExistente'
        );

        // Verificar si necesitamos establecer la cookie
        if (isset($cookieKey) && isset($cookieValue)) {
            // Crear una cookie que dure 24 horas con configuración robusta
            $cookie = cookie(
                $cookieKey,                // nombre
                $cookieValue,              // valor
                60 * 24,                   // duración en minutos (24 horas)
                '/',                       // path
                null,                      // dominio (null = dominio actual)
                request()->secure(),       // secure - solo HTTPS si la solicitud actual es HTTPS
                false,                     // httpOnly - false para permitir acceso desde JS
                false,                     // raw
                'lax'                      // sameSite
            );
            \Log::info("Estableciendo cookie {$cookieKey}={$cookieValue} en la respuesta (duración: 24 horas)");

            // Usa compact para generar la vista con datos consistentes
            $view = view('portal.formulario-cautivo', $viewData);
            return response($view)->withCookie($cookie);
        }

        // Si no hay cookie, simplemente devuelve la vista con todos los datos
        return view('portal.formulario-cautivo', $viewData);
    }

    /**
     * Registrar métrica completa incluyendo veces de entrada y duración
     */
    protected function registrarMetricaCompleta($zonaId, $macAddress, $metricaInfo)
    {
        if (!$macAddress) {
            return;
        }

        $agent = new \Jenssegers\Agent\Agent();

        // Obtener el user agent
        $ua = request()->header('User-Agent');

        // Procesar la información del dispositivo
        $dispositivo = $agent->device() ?: 'Desconocido';
        if ($dispositivo === 'Desconocido' && $ua) {
            $dispositivo = $this->extraerInformacionDispositivo($ua);
        }

        // Procesar información del navegador
        $navegador = $agent->browser() . ' ' . $agent->version($agent->browser());
        if (!$agent->browser() && $ua) {
            $navegador = $this->extraerInformacionNavegador($ua);
        }

        // Procesar información del sistema operativo
        $sistemaOperativo = $agent->platform() . ' ' . $agent->version($agent->platform());
        if (!$agent->platform() && $ua) {
            $sistemaOperativo = $this->extraerSistemaOperativo($ua);
        }

        $metricaData = [
            'zona_id' => $zonaId,
            'mac_address' => $macAddress,
            'dispositivo' => $dispositivo,
            'navegador' => $navegador,
            'sistema_operativo' => $sistemaOperativo,
            'tipo_visual' => $metricaInfo['tipo_visual'] ?? 'portal_cautivo',
            'duracion_visual' => 0, // Se actualizará desde el frontend
            'clic_boton' => false,  // Se actualizará cuando haga clic
            'veces_entradas' => 1   // Se incrementará automáticamente si ya existe
        ];

        // Usar el método del modelo para registrar/actualizar la métrica
        \App\Models\HotspotMetric::registrarMetrica($metricaData);
    }

    /**
     * Actualizar métrica de visita para usuarios recurrentes
     * @deprecated - Reemplazado por registrarMetricaCompleta
     */
    protected function actualizarMetricaVisita($zonaId, $macAddress)
    {
        if (!$macAddress) {
            return;
        }

        // Buscar métrica existente del día actual
        $metricaHoy = \App\Models\HotspotMetric::where('zona_id', $zonaId)
            ->where('mac_address', $macAddress)
            ->whereDate('created_at', today())
            ->first();

        if ($metricaHoy) {
            // Actualizar métrica existente
            $metricaHoy->increment('veces_entrada');
            $metricaHoy->touch(); // Actualizar timestamp
        } else {
            // Crear nueva métrica para hoy
            \App\Models\HotspotMetric::create([
                'zona_id' => $zonaId,
                'mac_address' => $macAddress,
                'dispositivo' => (new \Jenssegers\Agent\Agent())->device() ?: 'Desconocido',
                'navegador' => (new \Jenssegers\Agent\Agent())->browser(),
                'tipo_visual' => 'portal_entrada',
                'tiempo_activo' => 0,
                'veces_entrada' => 1,
                'clics_botones' => 0,
                'tiempo_visualizacion' => 0,
            ]);
        }
    }

    /**
     * Procesar el envío del formulario y guardar métricas
     */
    public function procesarFormulario(\Illuminate\Http\Request $request)
    {
        try {
            $zona = \App\Models\Zona::findOrFail($request->zona_id);

            // Validar campos obligatorios del formulario
            $reglas = [];
            foreach ($zona->campos()->where('obligatorio', true)->get() as $campo) {
                if ($campo->tipo !== 'checkbox') {
                    $reglas["respuestas.{$campo->campo}"] = 'required';
                }
            }

            if (!empty($reglas)) {
                $request->validate($reglas);
            }

            \DB::transaction(function () use ($request, $zona) {
                // Guardar respuesta del formulario
                $formResponse = \App\Models\FormResponse::create([
                    'zona_id' => $zona->id,
                    'mac_address' => $request->mac_address,
                    'dispositivo' => $request->dispositivo,
                    'navegador' => $request->navegador,
                    'tiempo_activo' => $request->tiempo_activo ?? 0,
                    'formulario_completado' => true,
                    'respuestas' => $request->respuestas ?? []
                ]);

                // Actualizar/crear métrica con la referencia al formulario
                $metricaData = [
                    'zona_id' => $zona->id,
                    'mac_address' => $request->mac_address,
                    'dispositivo' => $request->dispositivo,
                    'navegador' => $request->navegador,
                    'tipo_visual' => $request->tipo_visual ?? 'portal_cautivo',
                    'duracion_visual' => $request->tiempo_activo ?? 0,
                    'clic_boton' => true, // Se considera clic al enviar formulario
                    'formulario_id' => $formResponse->id
                ];

                \App\Models\HotspotMetric::registrarMetrica($metricaData);
            });

            return response()->json([
                'success' => true,
                'message' => 'Formulario enviado correctamente',
                'redirect_url' => $request->mikrotik_redirect ?? null
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor complete los campos obligatorios',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error procesando formulario portal cautivo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el formulario'
            ], 500);
        }
    }

    /**
     * Autenticar al usuario sin requerir registro.
     *
     * @param  \App\Models\Zona  $zona
     * @param  array  $mikrotikData
     * @return \Illuminate\Http\Response
     */
    protected function autenticarSinRegistro($zona, $mikrotikData)
    {
        // Aquí iría la lógica para autenticar al usuario directamente sin registro
        // Por ejemplo, podrías generar una respuesta que redirija al usuario a la URL correcta
        // con los parámetros necesarios para la autenticación en Mikrotik

        // Por ahora, simulamos una respuesta básica
        return view('auth.mikrotik.direct-auth', [
            'zona' => $zona,
            'mikrotikData' => $mikrotikData
        ]);
    }

    /**
     * Registrar métrica de entrada al portal cautivo
     */
    protected function registrarMetricaEntrada($zona, $mikrotikData)
    {
        try {
            $agent = new Agent();

            // Obtener el user agent
            $ua = request()->header('User-Agent');

            // Procesar la información del dispositivo
            $dispositivo = $agent->device() ?: 'Desconocido';
            if ($dispositivo === 'Desconocido' && $ua) {
                $dispositivo = $this->extraerInformacionDispositivo($ua);
            }

            // Procesar información del navegador
            $navegador = $agent->browser() . ' ' . $agent->version($agent->browser());
            if (!$agent->browser() && $ua) {
                $navegador = $this->extraerInformacionNavegador($ua);
            }

            // Procesar información del sistema operativo
            $sistemaOperativo = $agent->platform() . ' ' . $agent->version($agent->platform());
            if (!$agent->platform() && $ua) {
                $sistemaOperativo = $this->extraerSistemaOperativo($ua);
            }

            $data = [
                'zona_id' => $zona->id,
                'mac_address' => $mikrotikData['mac'] ?? 'unknown',
                'dispositivo' => $dispositivo,
                'navegador' => $navegador,
                'sistema_operativo' => $sistemaOperativo,
                'tipo_visual' => 'formulario', // Por defecto
                'duracion_visual' => 0,
                'clic_boton' => false,
            ];

            HotspotMetric::registrarMetrica($data);
        } catch (\Exception $e) {
            \Log::error('Error registrando métrica de entrada: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar métricas desde el frontend (duración visual, clics)
     */
    public function actualizarMetrica(\Illuminate\Http\Request $request)
    {
        try {
            $validador = \Validator::make($request->all(), [
                'zona_id' => 'required|integer',
                'mac_address' => 'required|string',
                'duracion_visual' => 'nullable', // Quitamos la validación integer para procesarla manualmente
                'clic_boton' => 'nullable|boolean',
                'tipo_visual' => 'nullable|string',
                'detalle' => 'nullable|string'
            ]);

            if ($validador->fails()) {
                \Log::warning('Validación fallida en actualizarMetrica: ' . json_encode($validador->errors()));
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validador->errors()
                ], 422);
            }

            // Registrar detalles adicionales en log para análisis
            if ($request->has('detalle')) {
                \Log::info('Detalle métrica', [
                    'zona_id' => $request->zona_id,
                    'mac_address' => $request->mac_address,
                    'detalle' => $request->detalle,
                    'tipo_visual' => $request->tipo_visual,
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ]);
            }

            // Mapear valores de tipo_visual a los permitidos por el esquema
            $tipoVisual = $request->tipo_visual;
            if ($tipoVisual && !in_array($tipoVisual, ['formulario', 'carrusel', 'video', 'portal_cautivo', 'portal_entrada', 'login'])) {
                // Si es un botón de trial o login, lo mapeamos a 'login'
                if (in_array($tipoVisual, ['trial', 'login'])) {
                    $tipoVisual = 'login';
                } elseif (in_array($tipoVisual, ['enlace_campana', 'enlace', 'link_campana'])) {
                    // Los enlaces de campaña los mapeamos a 'carrusel' ya que están relacionados con las campañas
                    $tipoVisual = 'carrusel';
                } else {
                    // Cualquier otro valor no reconocido lo mapeamos a 'formulario'
                    $tipoVisual = 'formulario';
                }
            }

            // Buscar o crear la métrica
            $metrica = \App\Models\HotspotMetric::where('zona_id', $request->zona_id)
                ->where('mac_address', $request->mac_address)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($metrica) {
                $datosActualizar = [];

                if ($request->has('duracion_visual')) {
                    // Asegurarse de que duracion_visual sea un entero válido
                    $duracionVisual = $request->duracion_visual;
                    if ($duracionVisual === null || $duracionVisual === '' || !is_numeric($duracionVisual)) {
                        $duracionVisual = 0; // Valor predeterminado si está vacío o no es numérico
                    } else {
                        $duracionVisual = (int)$duracionVisual; // Convertir a entero
                    }
                    $datosActualizar['duracion_visual'] = $duracionVisual;
                }

                if ($request->has('clic_boton')) {
                    $datosActualizar['clic_boton'] = $request->clic_boton;

                    // También guardamos esta métrica desglosada para análisis detallados
                    if ($request->clic_boton) {
                        \App\Models\MetricaDetalle::create([
                            'metrica_id' => $metrica->id,
                            'tipo_evento' => 'clic',
                            'contenido' => $tipoVisual,
                            'detalle' => $request->detalle ?? '',
                            'fecha_hora' => now()
                        ]);
                    }
                }

                if ($request->has('tipo_visual')) {
                    $datosActualizar['tipo_visual'] = $tipoVisual;
                }

                if (!empty($datosActualizar)) {
                    $metrica->update($datosActualizar);
                }
            } else {
                // Si no existe, crear una nueva métrica
                $metrica = \App\Models\HotspotMetric::create([
                    'zona_id' => $request->zona_id,
                    'mac_address' => $request->mac_address,
                    'dispositivo' => $request->dispositivo ?? 'Desconocido',
                    'navegador' => $request->navegador ?? 'Desconocido',
                    'tipo_visual' => $tipoVisual ?? 'formulario',
                    'duracion_visual' => $this->procesarDuracionVisual($request->duracion_visual),
                    'clic_boton' => $request->clic_boton ?? false,
                    'veces_entradas' => 1
                ]);

                // Registrar un detalle para la nueva métrica
                if ($request->clic_boton) {
                    \App\Models\MetricaDetalle::create([
                        'metrica_id' => $metrica->id,
                        'tipo_evento' => 'clic',
                        'contenido' => $tipoVisual,
                        'detalle' => $request->detalle ?? '',
                        'fecha_hora' => now()
                    ]);
                }
            }

                return response()->json([
                    'success' => true,
                    'message' => 'Métrica actualizada correctamente'
                ]);

        } catch (\Exception $e) {
            \Log::error('Error actualizando métrica: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar métrica'
            ], 500);
        }
    }

    /**
     * Procesa el valor de duración visual para asegurar que sea un entero
     *
     * @param mixed $duracionVisual
     * @return int
     */
    protected function procesarDuracionVisual($duracionVisual)
    {
        if ($duracionVisual === null || $duracionVisual === '' || !is_numeric($duracionVisual)) {
            return 0; // Valor predeterminado si está vacío o no es numérico
        }
        return (int)$duracionVisual; // Convertir a entero
    }

    /**
     * Extrae información del dispositivo desde el user agent
     *
     * @param string $ua User Agent
     * @return string Información del dispositivo
     */
    protected function extraerInformacionDispositivo($ua)
    {
        $dispositivo = 'Desconocido';

        // Extraer modelo de dispositivo móvil Android
        $regexModelo = '/Android[\s\d\.]+;\s([^;)]+)/i';
        preg_match($regexModelo, $ua, $modeloMatch);

        if (!empty($modeloMatch[1])) {
            $modelo = trim($modeloMatch[1]);
            $dispositivo = $modelo;

            // Detectar y formatear dispositivos Xiaomi/POCO
            if (preg_match('/(M2\d{3}|22\d{6}|21\d{6}|SM-[A-Za-z0-9]+)/', $modelo)) {
                if (stripos($ua, 'poco') !== false) {
                    $dispositivo = "POCO $modelo";
                } elseif (stripos($ua, 'redmi') !== false) {
                    $dispositivo = "Redmi $modelo";
                } elseif (stripos($ua, 'samsung') !== false || str_starts_with($modelo, 'SM-')) {
                    $dispositivo = "Samsung $modelo";
                } elseif (stripos($ua, 'xiaomi') !== false) {
                    $dispositivo = "Xiaomi $modelo";
                }
            }
        }
        // Si es iPhone/iPad
        elseif (str_contains($ua, 'iPhone')) {
            $dispositivo = 'iPhone';
        }
        elseif (str_contains($ua, 'iPad')) {
            $dispositivo = 'iPad';
        }
        // Si es un dispositivo Windows
        elseif (str_contains($ua, 'Windows')) {
            $dispositivo = 'PC Windows';
        }
        // Si es un dispositivo Mac
        elseif (str_contains($ua, 'Macintosh')) {
            $dispositivo = 'Mac';
        }

        return $dispositivo;
    }

    /**
     * Extrae información del navegador desde el user agent
     *
     * @param string $ua User Agent
     * @return string Información del navegador
     */
    protected function extraerInformacionNavegador($ua)
    {
        $navegador = 'Desconocido';
        $version = '';

        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg') && !str_contains($ua, 'OPR')) {
            $navegador = 'Chrome';
            preg_match('/Chrome\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Firefox')) {
            $navegador = 'Firefox';
            preg_match('/Firefox\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            $navegador = 'Safari';
            preg_match('/Version\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Edg')) {
            $navegador = 'Edge';
            preg_match('/Edg\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) {
            $navegador = 'Opera';
            preg_match('/(OPR|Opera)\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[2])) $version = $match[2];
        } elseif (str_contains($ua, 'MIUI')) {
            $navegador = 'Navegador MIUI';
            preg_match('/MiuiBrowser\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'SamsungBrowser')) {
            $navegador = 'Samsung Internet';
            preg_match('/SamsungBrowser\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        }

        if (!empty($version)) {
            $navegador .= ' ' . $version;
        }

        return $navegador;
    }

    /**
     * Extrae información del sistema operativo desde el user agent
     *
     * @param string $ua User Agent
     * @return string Información del sistema operativo
     */
    protected function extraerSistemaOperativo($ua)
    {
        $sistemaOperativo = 'Desconocido';

        if (str_contains($ua, 'Android')) {
            preg_match('/Android\s([0-9\.]+)/', $ua, $match);
            $sistemaOperativo = 'Android ' . (!empty($match[1]) ? $match[1] : '');
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iPod')) {
            preg_match('/OS\s([0-9_]+)/', $ua, $match);
            $version = !empty($match[1]) ? str_replace('_', '.', $match[1]) : '';
            $sistemaOperativo = 'iOS ' . $version;
        } elseif (str_contains($ua, 'Windows')) {
            preg_match('/Windows NT\s([0-9\.]+)/', $ua, $match);
            if (!empty($match[1])) {
                // Mapeo de versiones de Windows NT a nombres comerciales
                $windowsVersions = [
                    '10.0' => 'Windows 10/11',
                    '6.3' => 'Windows 8.1',
                    '6.2' => 'Windows 8',
                    '6.1' => 'Windows 7',
                    '6.0' => 'Windows Vista',
                    '5.2' => 'Windows XP x64',
                    '5.1' => 'Windows XP',
                    '5.0' => 'Windows 2000'
                ];
                $sistemaOperativo = isset($windowsVersions[$match[1]]) ? $windowsVersions[$match[1]] : 'Windows ' . $match[1];
            } else {
                $sistemaOperativo = 'Windows';
            }
        } elseif (str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh')) {
            preg_match('/Mac OS X\s?([0-9_\.]+)?/', $ua, $match);
            $version = !empty($match[1]) ? str_replace('_', '.', $match[1]) : '';
            $sistemaOperativo = 'macOS ' . $version;
        } elseif (str_contains($ua, 'Linux')) {
            if (str_contains($ua, 'Ubuntu')) {
                $sistemaOperativo = 'Ubuntu Linux';
            } else if (str_contains($ua, 'Fedora')) {
                $sistemaOperativo = 'Fedora Linux';
            } else if (str_contains($ua, 'Debian')) {
                $sistemaOperativo = 'Debian Linux';
            } else {
                $sistemaOperativo = 'Linux';
            }
        }

        return $sistemaOperativo;
    }
}
