<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Services\SeleccionCampanaService;
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

        // La métrica de la visita se registra en mostrarPortalCautivo(), una sola vez por visita
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
     * @param  array  $opciones  ['preview' => bool, 'forzarTipo' => 'video'|'imagen'|null]
     *   En preview no se registran métricas, no hay token ni cookie y siempre se muestra el formulario.
     * @return \Illuminate\Http\Response
     */
    public function mostrarPortalCautivo($zona, $mikrotikData, $metricaInfo, array $opciones = [])
    {
        $modoPreview = (bool) ($opciones['preview'] ?? false);
        $forzarTipo = $opciones['forzarTipo'] ?? null;
        $macAddress = $mikrotikData['mac'] ?? '';

        // Verificar si la MAC ya tiene respuesta de formulario
        $respuestaExistente = null;
        $mostrarFormulario = false;

        if ($macAddress && !$modoPreview) {
            $respuestaExistente = \App\Models\FormResponse::where('zona_id', $zona->id)
                ->where('mac_address', $macAddress)
                ->first();
        }

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

        // Último tipo mostrado (video/imagen) para alternar: 1° cookie, 2° la métrica de esa MAC.
        // No se usa la sesión: estas rutas no llevan middleware de sesión y los navegadores
        // de portal cautivo (p. ej. el CNA de iOS) no conservan cookies.
        $cookieKey = 'ultimo_tipo_zona_' . $zona->id;
        $ultimoTipoMostrado = request()->cookie($cookieKey);
        if (!in_array($ultimoTipoMostrado, ['video', 'imagen'], true) && $macAddress) {
            $ultimoTipoMostrado = \App\Models\HotspotMetric::where('zona_id', $zona->id)
                ->where('mac_address', $macAddress)
                ->value('ultimo_contenido');
        }
        if (!in_array($ultimoTipoMostrado, ['video', 'imagen'], true)) {
            $ultimoTipoMostrado = null;
        }

        // Seleccionar la campaña a mostrar (ver App\Services\SeleccionCampanaService)
        $servicioCampanas = app(SeleccionCampanaService::class);
        $seleccion = in_array($forzarTipo, ['video', 'imagen'], true)
            ? $servicioCampanas->seleccionarDe($servicioCampanas->campanasActivas($zona), $forzarTipo)
            : $servicioCampanas->seleccionar($zona, $ultimoTipoMostrado);
        $campanaSeleccionada = $seleccion['campana'];
        $videoUrl = $seleccion['videoUrl'];
        $imagenes = $seleccion['imagenes'];
        $titulosCampanas = $seleccion['titulos'];

        if ($seleccion['tipo'] && !$modoPreview) {
            $cookieValue = $seleccion['tipo'];
            // En las métricas las imágenes se registran como 'carrusel'
            $metricaInfo['tipo_visual'] = $seleccion['tipo'] === 'video' ? 'video' : 'carrusel';
            $metricaInfo['ultimo_contenido'] = $seleccion['tipo'];
        }

        // Token firmado para las llamadas del portal a métricas y formulario
        $portalToken = $modoPreview ? '' : \App\Services\PortalToken::generar($zona->id, $macAddress);

        // Registrar/actualizar la métrica ya con el tipo de contenido que se va a mostrar
        if (!$modoPreview) {
            $this->registrarMetricaCompleta($zona->id, $macAddress, $metricaInfo);
        }

        // Tiempo de visualización
        $tiempoVisualizacion = $zona->tiempo_visualizacion ?? 15;

        \Log::debug("Portal zona {$zona->id}: último tipo " . ($ultimoTipoMostrado ?? 'ninguno') . ", mostrado " . ($seleccion['tipo'] ?? 'nada') . ", campaña " . ($campanaSeleccionada->id ?? 'N/A') . ", formulario " . ($mostrarFormulario ? 'sí' : 'no'));

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
            'respuestaExistente',
            'portalToken',
            'modoPreview',
            'titulosCampanas'
        );

        // Verificar si necesitamos establecer la cookie
        if (isset($cookieValue)) {
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
            return response(view('portal.formulario-cautivo', $viewData))->withCookie($cookie);
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
            'veces_entradas' => 1,  // Se incrementará automáticamente si ya existe
            'ultimo_contenido' => $metricaInfo['ultimo_contenido'] ?? null,
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

            // Validar campos obligatorios del formulario y la aceptación del aviso de privacidad
            $reglas = ['acepta_privacidad' => 'accepted'];
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
                    'respuestas' => $request->respuestas ?? [],
                    'acepto_privacidad_at' => now(),
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

                // Enviar el formulario es parte de la misma visita: no cuenta otra entrada
                \App\Models\HotspotMetric::registrarMetrica($metricaData, false);
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
                \Log::debug('Detalle métrica', [
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
