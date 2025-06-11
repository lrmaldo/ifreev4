<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HotspotMetric;
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
            // Filtrar por tipo según configuración de la zona
            $tipoPreferido = $zona->seleccion_campanas === 'video' ? 'video' : 'imagen';
            $campanasPreferidas = $campanasActivas->where('tipo', $tipoPreferido);

            if ($campanasPreferidas->isEmpty()) {
                $campanasPreferidas = $campanasActivas;
            }

            // Seleccionar campaña
            $campanaSeleccionada = $campanasPreferidas->random();

            if ($campanaSeleccionada->tipo === 'video' && $campanaSeleccionada->archivo_path) {
                $videoUrl = \Storage::url($campanaSeleccionada->archivo_path);
            } else {
                // Obtener imágenes de campañas
                foreach ($campanasActivas->where('tipo', 'imagen') as $campana) {
                    if ($campana->archivo_path) {
                        $imagenes[] = \Storage::url($campana->archivo_path);
                    }
                }
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

        $metricaData = [
            'zona_id' => $zonaId,
            'mac_address' => $macAddress,
            'dispositivo' => $agent->device() ?: 'Desconocido',
            'navegador' => $agent->browser() . ' ' . $agent->version($agent->browser()),
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

            $data = [
                'zona_id' => $zona->id,
                'mac_address' => $mikrotikData['mac'] ?? 'unknown',
                'dispositivo' => $agent->device() ?: 'Desconocido',
                'navegador' => $agent->browser() . ' ' . $agent->version($agent->browser()),
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
            $request->validate([
                'zona_id' => 'required|integer',
                'mac_address' => 'required|string',
                'duracion_visual' => 'nullable|integer',
                'clic_boton' => 'nullable|boolean',
                'tipo_visual' => 'nullable|string',
                'detalle' => 'nullable|string'
            ]);

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

            $metrica = \App\Models\HotspotMetric::where('zona_id', $request->zona_id)
                ->where('mac_address', $request->mac_address)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($metrica) {
                $datosActualizar = [];

                if ($request->has('duracion_visual')) {
                    $datosActualizar['duracion_visual'] = $request->duracion_visual;
                }

                if ($request->has('clic_boton')) {
                    $datosActualizar['clic_boton'] = $request->clic_boton;

                    // También guardamos esta métrica desglosada para análisis detallados
                    if ($request->clic_boton) {
                        \App\Models\MetricaDetalle::create([
                            'metrica_id' => $metrica->id,
                            'tipo_evento' => 'clic',
                            'contenido' => $request->tipo_visual,
                            'detalle' => $request->detalle ?? '',
                            'fecha_hora' => now()
                        ]);
                    }
                }

                if ($request->has('tipo_visual')) {
                    $datosActualizar['tipo_visual'] = $request->tipo_visual;
                }

                if (!empty($datosActualizar)) {
                    $metrica->update($datosActualizar);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Métrica actualizada correctamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Métrica no encontrada'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error actualizando métrica: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar métrica'
            ], 500);
        }
    }
}
