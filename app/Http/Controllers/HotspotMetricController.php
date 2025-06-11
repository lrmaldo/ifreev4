<?php

namespace App\Http\Controllers;

use App\Models\HotspotMetric;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Karmendra\LaravelAgentDetector\AgentDetector;

class HotspotMetricController extends Controller
{
    /**
     * Mostrar el dashboard de métricas
     */
    public function index()
    {
        return view('hotspot-metrics.index');
    }

    /**
     * Registrar una nueva métrica desde el portal cautivo
     */
    public function store(Request $request)
    {
        $agent = new AgentDetector();

        $data = [
            'zona_id' => $request->zona_id,
            'mac_address' => $request->mac_address,
            'formulario_id' => $request->formulario_id,
            'dispositivo' => trim($agent->device() . ' ' . $agent->deviceModel()) ?: 'Desconocido',
            'navegador' => trim($agent->browser() . ' ' . $agent->browserVersion()),
            'tipo_visual' => $request->tipo_visual ?? 'formulario',
            'duracion_visual' => $request->duracion_visual ?? 0,
            'clic_boton' => $request->boolean('clic_boton', false),
            'sistema_operativo' => trim($agent->platform() . ' ' . $agent->platformVersion()),
        ];

        $metrica = HotspotMetric::registrarMetrica($data);

        return response()->json([
            'success' => true,
            'metric_id' => $metrica->id,
            'veces_entradas' => $metrica->veces_entradas
        ]);
    }

    /**
     * Obtener estadísticas para el dashboard
     */
    public function analytics(Request $request)
    {
        $zona_id = $request->zona_id;
        $fecha_inicio = $request->fecha_inicio ? Carbon::parse($request->fecha_inicio) : Carbon::now()->subDays(30);
        $fecha_fin = $request->fecha_fin ? Carbon::parse($request->fecha_fin) : Carbon::now();

        $query = HotspotMetric::query()
            ->byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin);

        // Estadísticas generales
        $totalVisitas = $query->sum('veces_entradas');
        $dispositivosUnicos = $query->distinct('mac_address')->count();
        $formulariosCompletados = $query->whereNotNull('formulario_id')->count();
        $tasaConversion = $dispositivosUnicos > 0 ? round(($formulariosCompletados / $dispositivosUnicos) * 100, 2) : 0;

        // Visitas por día (últimos 30 días)
        $visitasPorDia = HotspotMetric::selectRaw('DATE(created_at) as fecha, SUM(veces_entradas) as total')
            ->byZona($zona_id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->fecha => $item->total];
            });

        // Dispositivos más utilizados
        $dispositivosPopulares = HotspotMetric::selectRaw('dispositivo, COUNT(*) as total')
            ->byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin)
            ->groupBy('dispositivo')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Navegadores más utilizados
        $navegadoresPopulares = HotspotMetric::selectRaw('navegador, COUNT(*) as total')
            ->byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin)
            ->groupBy('navegador')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Sistemas operativos más utilizados
        $sistemasOperativosPopulares = HotspotMetric::selectRaw('sistema_operativo, COUNT(*) as total')
            ->byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin)
            ->whereNotNull('sistema_operativo')
            ->groupBy('sistema_operativo')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Tipos de contenido visual
        $tiposVisuales = HotspotMetric::selectRaw('tipo_visual, COUNT(*) as total')
            ->byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin)
            ->groupBy('tipo_visual')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tipo_visual => $item->total];
            });

        // Duración promedio de visualización
        $duracionPromedio = HotspotMetric::byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin)
            ->avg('duracion_visual');

        // Clicks en botones CTA
        $clicsBoton = HotspotMetric::byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin)
            ->where('clic_boton', true)
            ->count();

        // Usuarios recurrentes (con más de 1 entrada)
        $usuariosRecurrentes = HotspotMetric::byZona($zona_id)
            ->byDateRange($fecha_inicio, $fecha_fin)
            ->where('veces_entradas', '>', 1)
            ->count();

        return response()->json([
            'estadisticas_generales' => [
                'total_visitas' => $totalVisitas,
                'dispositivos_unicos' => $dispositivosUnicos,
                'formularios_completados' => $formulariosCompletados,
                'tasa_conversion' => $tasaConversion,
                'duracion_promedio' => round($duracionPromedio ?? 0, 2),
                'clics_boton' => $clicsBoton,
                'usuarios_recurrentes' => $usuariosRecurrentes
            ],
            'visitas_por_dia' => $visitasPorDia,
            'dispositivos_populares' => $dispositivosPopulares,
            'navegadores_populares' => $navegadoresPopulares,
            'sistemas_operativos_populares' => $sistemasOperativosPopulares,
            'tipos_visuales' => $tiposVisuales,
            'fecha_inicio' => $fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $fecha_fin->format('Y-m-d')
        ]);
    }

    /**
     * Obtener métricas detalladas con paginación
     */
    public function show(Request $request)
    {
        $query = HotspotMetric::with(['zona', 'formulario'])
            ->byZona($request->zona_id)
            ->byDateRange($request->fecha_inicio, $request->fecha_fin)
            ->byMac($request->mac_address);

        // Ordenamiento
        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        $metricas = $query->paginate(20);

        return response()->json($metricas);
    }

    /**
     * Obtener detalles de una métrica específica
     */
    public function detalles(Request $request, $id)
    {
        $metrica = HotspotMetric::with(['zona', 'formulario'])->findOrFail($id);

        // Cargamos los detalles relacionados
        $detalles = MetricaDetalle::where('metrica_id', $id)
            ->orderBy('fecha_hora', 'asc')
            ->get();

        // Si se solicita formato JSON
        if ($request->wantsJson()) {
            return response()->json([
                'metrica' => $metrica,
                'detalles' => $detalles,
                'eventos_por_tipo' => $detalles->groupBy('tipo_evento'),
                'timeline' => $detalles->map(function ($detalle) {
                    return [
                        'tiempo' => $detalle->fecha_hora->format('H:i:s'),
                        'tipo' => $detalle->tipo_evento,
                        'contenido' => $detalle->contenido,
                        'detalle' => $detalle->detalle
                    ];
                })
            ]);
        }

        // Vista HTML
        return view('hotspot-metrics.detalles', compact('metrica', 'detalles'));
    }

    /**
     * Registrar métrica desde JavaScript del portal
     */
    public function track(Request $request)
    {
        try {
            $agent = new AgentDetector();

            $data = [
                'zona_id' => $request->zona_id,
                'mac_address' => $request->mac_address,
                'formulario_id' => $request->formulario_id,
                'dispositivo' => trim($agent->device() . ' ' . $agent->deviceModel()) ?: 'Desconocido',
                'navegador' => trim($agent->browser() . ' ' . $agent->browserVersion()),
                'tipo_visual' => $request->tipo_visual ?? 'formulario',
                'duracion_visual' => $request->duracion_visual ?? 0,
                'clic_boton' => $request->boolean('clic_boton', false),
                'sistema_operativo' => trim($agent->platform() . ' ' . $agent->platformVersion()),
            ];

            HotspotMetric::registrarMetrica($data);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error registrando métrica hotspot: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Exportar métricas a CSV
     */
    public function export(Request $request)
    {
        $this->authorize('gestionar metricas hotspot');

        $query = HotspotMetric::with(['zona', 'formulario'])
            ->byZona($request->zona_id)
            ->byDateRange($request->fecha_inicio, $request->fecha_fin)
            ->orderBy('created_at', 'desc');

        $metricas = $query->get();

        $fileName = 'metricas_hotspot_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($metricas) {
            $file = fopen('php://output', 'w');

            // Encabezados CSV
            fputcsv($file, [
                'ID',
                'Zona',
                'MAC Address',
                'Dispositivo',
                'Navegador',
                'Sistema Operativo',
                'Tipo Visual',
                'Duración (seg)',
                'Clic Botón',
                'Veces Entradas',
                'Formulario Completado',
                'Fecha Registro'
            ]);

            // Datos
            foreach ($metricas as $metrica) {
                fputcsv($file, [
                    $metrica->id,
                    $metrica->zona->nombre ?? 'N/A',
                    $metrica->mac_address,
                    $metrica->dispositivo,
                    $metrica->navegador,
                    $metrica->sistema_operativo ?? 'Desconocido',
                    $metrica->tipo_visual,
                    $metrica->duracion_visual,
                    $metrica->clic_boton ? 'Sí' : 'No',
                    $metrica->veces_entradas,
                    $metrica->formulario_id ? 'Sí' : 'No',
                    $metrica->created_at->format('d-m-Y H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
