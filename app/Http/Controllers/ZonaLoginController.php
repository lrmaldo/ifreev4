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

        // Capturar todos los parámetros enviados por Mikrotik
        $mikrotikData = $request->only([
            'mac', 'ip', 'username', 'link-login', 'link-orig', 'error',
            'chap-id', 'chap-challenge', 'link-login-only', 'link-orig-esc', 'mac-esc'
        ]);

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
        // Obtener los campos del formulario con su HTML renderizado
        $formFields = \App\Models\FormField::where('zona_id', $zona->id)->orderBy('orden')->get();
        $camposHtml = [];

        // Usar el trait RenderizaFormFields para generar el HTML
        foreach ($formFields as $campo) {
            $camposHtml[] = $this->renderizarCampo($campo);
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

        // Verificar si se debe mostrar formulario
        $mostrarFormulario = $zona->tipo_registro !== 'sin_registro' && $zona->campos->count() > 0;

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
            'tiempoVisualizacion'
        ));
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
}
