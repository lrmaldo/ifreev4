<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\HotspotMetric;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HotspotMetricsDashboard extends Component
{
    use WithPagination;

    public $zona_id = '';
    public $mac_address = '';
    public $fecha_inicio = '';
    public $fecha_fin = '';
    public $order_by = 'created_at';
    public $order_direction = 'desc';

    // Estadísticas
    public $estadisticas = [];
    public $visitasPorDia = [];
    public $dispositivosPopulares = [];
    public $navegadoresPopulares = [];
    public $sistemasOperativosPopulares = [];
    public $tiposVisuales = [];

    protected $queryString = [
        'zona_id',
        'mac_address',
        'fecha_inicio',
        'fecha_fin',
        'page'
    ];

    public function mount()
    {
        // Establecer fechas por defecto (últimos 30 días)
        $this->fecha_fin = now()->format('Y-m-d');
        $this->fecha_inicio = now()->subDays(30)->format('Y-m-d');

        $this->loadAnalytics();
    }

    public function updated($property)
    {
        if (in_array($property, ['zona_id', 'fecha_inicio', 'fecha_fin'])) {
            $this->resetPage();
            $this->loadAnalytics();

            // Emitir evento para actualizar gráfico
            $this->js('window.dispatchEvent(new CustomEvent("chartDataUpdated", { detail: ' . json_encode($this->visitasPorDia) . ' }))');
        }
    }

    public function loadAnalytics()
    {
        $fechaInicio = $this->fecha_inicio ? Carbon::parse($this->fecha_inicio) : Carbon::now()->subDays(30);
        $fechaFin = $this->fecha_fin ? Carbon::parse($this->fecha_fin) : Carbon::now();

        $query = HotspotMetric::query()
            ->byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin);

        // Estadísticas generales
        $totalVisitas = $query->sum('veces_entradas');
        $dispositivosUnicos = $query->distinct('mac_address')->count();
        $formulariosCompletados = $query->whereNotNull('formulario_id')->count();
        $tasaConversion = $dispositivosUnicos > 0 ? round(($formulariosCompletados / $dispositivosUnicos) * 100, 2) : 0;

        // Duración promedio
        $duracionPromedio = HotspotMetric::byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->avg('duracion_visual');

        // Clicks en botones CTA
        $clicsBoton = HotspotMetric::byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->where('clic_boton', true)
            ->count();

        // Usuarios recurrentes
        $usuariosRecurrentes = HotspotMetric::byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->where('veces_entradas', '>', 1)
            ->count();

        $this->estadisticas = [
            'total_visitas' => $totalVisitas,
            'dispositivos_unicos' => $dispositivosUnicos,
            'formularios_completados' => $formulariosCompletados,
            'tasa_conversion' => $tasaConversion,
            'duracion_promedio' => round($duracionPromedio ?? 0, 2),
            'clics_boton' => $clicsBoton,
            'usuarios_recurrentes' => $usuariosRecurrentes
        ];

        // Visitas por día (últimos 30 días)
        $this->visitasPorDia = HotspotMetric::selectRaw('DATE(created_at) as fecha, SUM(veces_entradas) as total')
            ->byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->fecha => $item->total];
            })
            ->toArray();

        // Dispositivos populares
        $this->dispositivosPopulares = HotspotMetric::selectRaw('dispositivo, COUNT(*) as total')
            ->byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->groupBy('dispositivo')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->toArray();

        // Navegadores populares
        $this->navegadoresPopulares = HotspotMetric::selectRaw('navegador, COUNT(*) as total')
            ->byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->groupBy('navegador')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->toArray();

        // Sistemas operativos populares
        $this->sistemasOperativosPopulares = HotspotMetric::selectRaw('sistema_operativo, COUNT(*) as total')
            ->byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->whereNotNull('sistema_operativo')
            ->groupBy('sistema_operativo')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->toArray();

        // Tipos visuales
        $this->tiposVisuales = HotspotMetric::selectRaw('tipo_visual, COUNT(*) as total')
            ->byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->groupBy('tipo_visual')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tipo_visual => $item->total];
            })
            ->toArray();
    }

    public function sortBy($column)
    {
        if ($this->order_by === $column) {
            $this->order_direction = $this->order_direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->order_by = $column;
            $this->order_direction = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->zona_id = '';
        $this->mac_address = '';
        $this->fecha_inicio = now()->subDays(30)->format('Y-m-d');
        $this->fecha_fin = now()->format('Y-m-d');

        $this->resetPage();
        $this->loadAnalytics();
    }

    public function exportData()
    {
        $queryParams = http_build_query([
            'zona_id' => $this->zona_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'mac_address' => $this->mac_address
        ]);

        return redirect()->to('/hotspot-metrics/export?' . $queryParams);
    }

    public function render()
    {
        $fechaInicio = $this->fecha_inicio ? Carbon::parse($this->fecha_inicio) : Carbon::now()->subDays(30);
        $fechaFin = $this->fecha_fin ? Carbon::parse($this->fecha_fin) : Carbon::now();

        // Consulta de métricas con paginación
        $metricas = HotspotMetric::with(['zona', 'formulario'])
            ->byZona($this->zona_id)
            ->byDateRange($fechaInicio, $fechaFin)
            ->byMac($this->mac_address)
            ->orderBy($this->order_by, $this->order_direction)
            ->paginate(15);

        // Obtener zonas disponibles para el usuario
        $zonas = collect();
        if (Auth::user()->hasRole('admin')) {
            $zonas = Zona::all();
        } elseif (Auth::user()->hasRole('cliente')) {
            $zonas = Auth::user()->zonas ?? collect();
        } elseif (Auth::user()->hasRole('tecnico')) {
            $zonas = Zona::all();
        }

        return view('livewire.hotspot-metrics-dashboard', [
            'metricas' => $metricas,
            'zonas' => $zonas,
        ]);
    }
}
