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
        // Buscar la zona primero por id_personalizado, luego por id
        $zona = \App\Models\Zona::where('id_personalizado', $id)->first();

        if (!$zona) {
            $zona = \App\Models\Zona::find($id);
        }

        if (!$zona) {
            abort(404, 'Zona no encontrada');
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

        // Registrar métrica de entrada al portal
        $this->registrarMetricaEntrada($zona, $mikrotikData);

        // Aquí puedes procesar los datos como sea necesario
        // Por ejemplo, guardarlos en una base de datos, verificar si el usuario está autorizado, etc.

        // Preparar información de métrica
        $metricaInfo = [
            'zona_id' => $zona->id,
            'mac_address' => $mikrotikData['mac'] ?? 'unknown',
            'dispositivo' => (new \Jenssegers\Agent\Agent())->device() ?: 'Desconocido',
            'navegador' => (new \Jenssegers\Agent\Agent())->browser() . ' ' . (new \Jenssegers\Agent\Agent())->version((new \Jenssegers\Agent\Agent())->browser()),
            'tipo_visual' => 'portal_cautivo',
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
            $ultimoTipoMostrado = session('ultimo_tipo_mostrado_' . $zona->id, '');

            // Registrar en log el método de selección para depuración
            \Log::info("Método de selección de campañas: {$tipoPreferido} para zona: {$zona->id}. Último tipo mostrado: {$ultimoTipoMostrado}");

            // Decisión de mostrar video o imagen
            $mostrarVideo = false;

            // Algoritmo mejorado para mejor alternancia entre tipos de contenido
            if ($tipoPreferido === 'aleatorio') {
                // En modo aleatorio, garantizamos alternancia estricta
                if (!$videos->isEmpty() && !$imagenesCollection->isEmpty()) {
                    // Si hay ambos tipos de contenido disponibles, alternamos estrictamente
                    if ($ultimoTipoMostrado === 'video') {
                        $mostrarVideo = false;
                        \Log::info("Alternancia estricta: último fue video, ahora mostramos imagen");
                    } else if ($ultimoTipoMostrado === 'imagen') {
                        $mostrarVideo = true;
                        \Log::info("Alternancia estricta: último fue imagen, ahora mostramos video");
                    } else {
                        // Si es primera visualización, comenzamos con video (si hay disponible)
                        $mostrarVideo = true;
                        \Log::info("Primera visualización: comenzamos con video");
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
            } else if ($tipoPreferido === 'video' && !$videos->isEmpty()) {
                // Si la preferencia explícita es video y hay videos, mostrar video
                $mostrarVideo = true;
                \Log::info("Seleccionando video por preferencia explícita");
            } else if ($tipoPreferido === 'imagen' && !$imagenesCollection->isEmpty()) {
                // Si la preferencia explícita es imagen y hay imágenes, mostrar imagen
                $mostrarVideo = false;
                \Log::info("Seleccionando imagen por preferencia explícita");
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
                session(['ultimo_tipo_mostrado_' . $zona->id => 'video']);
                \Log::info("Seleccionado video: ID {$campanaSeleccionada->id}, '{$campanaSeleccionada->nombre}'");
            } else if (!$imagenesCollection->isEmpty()) {
                // Si no hay videos o toca mostrar imágenes
                session(['ultimo_tipo_mostrado_' . $zona->id => 'imagen']);

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
                } else {
                    // En modo aleatorio, mostramos todas las imágenes
                    foreach ($imagenesCollection as $campana) {
                        $imagenes[] = \Storage::url($campana->archivo_path);
                    }
                    $campanaSeleccionada = $imagenesCollection->first();
                }

                \Log::info("Seleccionadas " . count($imagenes) . " imágenes, primera: ID {$campanaSeleccionada->id}, '{$campanaSeleccionada->nombre}'");
            }
        }

        // Verificar si se debe mostrar formulario (ya calculado arriba)
        // $mostrarFormulario ya está definido

        // Tiempo de visualización
        $tiempoVisualizacion = $zona->tiempo_visualizacion ?? 15;

        return view('portal.formulario-cautivo', compact(
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
}
