<?php

namespace App\Http\Controllers;

use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
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

        // Intentar detectar con AgentDetector
        $dispositivoDetectado = trim($agent->device() . ' ' . $agent->deviceModel());
        $navegadorDetectado = trim($agent->browser() . ' ' . $agent->browserVersion());
        $sistemaOperativoDetectado = trim($agent->platform() . ' ' . $agent->platformVersion());

        // Procesar información del dispositivo
        $dispositivo = $dispositivoDetectado;
        if (empty($dispositivo) || $dispositivo === 'Desconocido') {
            // Si el cliente envió un dispositivo específico, usarlo
            if ($request->has('dispositivo') && $request->dispositivo !== 'Desconocido' && !str_contains($request->dispositivo, 'Mozilla/5.0')) {
                $dispositivo = $request->dispositivo;
            }
            // Si tenemos user_agent, intentar extraer el modelo
            elseif ($request->has('user_agent')) {
                $ua = $request->user_agent;
                $regexModelo = '/Android[\s\d\.]+;\s([^;)]+)/i';
                preg_match($regexModelo, $ua, $modeloMatch);

                if (!empty($modeloMatch[1])) {
                    $modelo = trim($modeloMatch[1]);
                    $dispositivo = $modelo;

                    // Detectar y formatear dispositivos Xiaomi/POCO
                    if (preg_match('/(M2\d{3}|22\d{6}|21\d{6})/', $modelo)) {
                        if (stripos($ua, 'poco') !== false) {
                            $dispositivo = "POCO $modelo";
                        } elseif (stripos($ua, 'redmi') !== false) {
                            $dispositivo = "Redmi $modelo";
                        } else {
                            $dispositivo = "Xiaomi $modelo";
                        }
                    }
                }
                elseif (str_contains($ua, 'iPhone')) {
                    $dispositivo = 'iPhone';
                }
                elseif (str_contains($ua, 'iPad')) {
                    $dispositivo = 'iPad';
                }
            }
        }

        // Procesar información del navegador
        $navegador = $navegadorDetectado;
        if (empty($navegador) || $navegador === 'Desconocido') {
            // Si el cliente envió un navegador específico, usarlo
            if ($request->has('navegador') && $request->navegador !== 'Desconocido' && !str_contains($request->navegador, 'Mozilla/5.0')) {
                $navegador = $request->navegador;
            }
            // Si tenemos user_agent, intentar extraer el navegador
            elseif ($request->has('user_agent')) {
                $ua = $request->user_agent;
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
            }
        }

        $data = [
            'zona_id' => $request->zona_id,
            'mac_address' => $request->mac_address,
            'formulario_id' => $request->formulario_id,
            'dispositivo' => $dispositivo ?: 'Desconocido',
            'navegador' => $navegador ?: 'Desconocido',
            'tipo_visual' => $request->tipo_visual ?? 'formulario',
            'duracion_visual' => $request->duracion_visual ?? 0,
            'clic_boton' => $request->boolean('clic_boton', false),
            'sistema_operativo' => $sistemaOperativoDetectado ?: ($request->sistema_operativo ?: 'Desconocido'),
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
            $agent = new AgentDetector();            // Intentar detectar con AgentDetector
            $dispositivoDetectado = trim($agent->device() . ' ' . $agent->deviceModel());
            $navegadorDetectado = trim($agent->browser() . ' ' . $agent->browserVersion());
            $sistemaOperativoDetectado = trim($agent->platform() . ' ' . $agent->platformVersion());

            // Procesar información del dispositivo
            $dispositivo = $dispositivoDetectado;
            if (empty($dispositivo) || $dispositivo === 'Desconocido') {
                // Si el cliente envió un dispositivo específico, usarlo
                if ($request->has('dispositivo') && $request->dispositivo !== 'Desconocido' && !str_contains($request->dispositivo, 'Mozilla/5.0')) {
                    $dispositivo = $request->dispositivo;
                }
                // Si tenemos user_agent, intentar extraer el modelo
                elseif ($request->has('user_agent')) {
                    $ua = $request->user_agent;
                    $regexModelo = '/Android[\s\d\.]+;\s([^;)]+)/i';
                    preg_match($regexModelo, $ua, $modeloMatch);

                    if (!empty($modeloMatch[1])) {
                        $modelo = trim($modeloMatch[1]);
                        $dispositivo = $modelo;

                        // Detectar y formatear dispositivos Xiaomi/POCO
                        if (preg_match('/(M2\d{3}|22\d{6}|21\d{6})/', $modelo)) {
                            if (stripos($ua, 'poco') !== false) {
                                $dispositivo = "POCO $modelo";
                            } elseif (stripos($ua, 'redmi') !== false) {
                                $dispositivo = "Redmi $modelo";
                            } else {
                                $dispositivo = "Xiaomi $modelo";
                            }
                        }
                    }
                    elseif (str_contains($ua, 'iPhone')) {
                        $dispositivo = 'iPhone';
                    }
                    elseif (str_contains($ua, 'iPad')) {
                        $dispositivo = 'iPad';
                    }
                }
            }

            // Procesar información del navegador
            $navegador = $navegadorDetectado;
            if (empty($navegador) || $navegador === 'Desconocido') {
                // Si el cliente envió un navegador específico, usarlo
                if ($request->has('navegador') && $request->navegador !== 'Desconocido' && !str_contains($request->navegador, 'Mozilla/5.0')) {
                    $navegador = $request->navegador;
                }
                // Si tenemos user_agent, intentar extraer el navegador
                elseif ($request->has('user_agent')) {
                    $ua = $request->user_agent;
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
                }
            }

            // Procesar información del sistema operativo
            $sistemaOperativo = $sistemaOperativoDetectado;
            if (empty($sistemaOperativo) || $sistemaOperativo === 'Desconocido') {
                // Si el cliente envió un sistema operativo específico, usarlo
                if ($request->has('sistema_operativo') && $request->sistema_operativo !== 'Desconocido' && $request->sistema_operativo !== 'Win32') {
                    $sistemaOperativo = $request->sistema_operativo;
                }
                // Si tenemos user_agent, intentar extraer el sistema operativo
                elseif ($request->has('user_agent')) {
                    $ua = $request->user_agent;

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
                }
            }

            // Asegurarse de que duracion_visual sea un entero válido
            $duracionVisual = $request->duracion_visual;
            if ($duracionVisual === null || $duracionVisual === '' || !is_numeric($duracionVisual)) {
                $duracionVisual = 0; // Valor predeterminado si está vacío o no es numérico
            } else {
                $duracionVisual = (int)$duracionVisual; // Convertir a entero
            }

            // Usar valores detectados o los proporcionados en la solicitud, o valores por defecto
            $data = [
                'zona_id' => $request->zona_id,
                'mac_address' => $request->mac_address,
                'formulario_id' => $request->formulario_id,
                'dispositivo' => $dispositivo ?: 'Desconocido',
                'navegador' => $navegador ?: 'Desconocido',
                'tipo_visual' => $request->tipo_visual ?? 'formulario',
                'duracion_visual' => $duracionVisual,
                'clic_boton' => $request->boolean('clic_boton', false),
                'sistema_operativo' => $sistemaOperativo ?: 'Desconocido',
            ];

            $metrica = HotspotMetric::registrarMetrica($data);

            // Registrar un evento de vista en los detalles
            if ($metrica) {
                MetricaDetalle::create([
                    'metrica_id' => $metrica->id,
                    'tipo_evento' => 'vista',
                    'contenido' => $request->tipo_visual ?? 'formulario',
                    'detalle' => 'Entrada al portal',
                    'fecha_hora' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'metric_id' => $metrica->id ?? null,
                'veces_entradas' => $metrica->veces_entradas ?? 1
            ]);
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
