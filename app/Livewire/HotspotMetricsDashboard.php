<?php

namespace App\Livewire;

use App\Models\HotspotMetric;
use App\Models\Zona;
use App\Services\MetricasHotspotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Página de Métricas: resumen del periodo, gráficas y lista de dispositivos.
 * Un cliente solo ve sus propias zonas; admin y técnico ven todas.
 */
class HotspotMetricsDashboard extends Component
{
    use WithPagination;

    public const PERIODOS = ['hoy' => 'Hoy', '7' => '7 días', '30' => '30 días', '90' => '90 días', 'rango' => 'Fechas'];

    #[Url(as: 'zona', except: '')]
    public string $zona_id = '';

    #[Url(except: '30')]
    public string $periodo = '30';

    #[Url(except: '')]
    public string $desde = '';

    #[Url(except: '')]
    public string $hasta = '';

    #[Url(as: 'mac', except: '')]
    public string $mac_address = '';

    public string $order_by = 'updated_at';
    public string $order_direction = 'desc';

    public function mount(): void
    {
        if (!array_key_exists($this->periodo, self::PERIODOS)) {
            $this->periodo = '30';
        }
    }

    /** Al elegir "Fechas" se parte de los últimos 30 días; los demás periodos no usan desde/hasta. */
    public function updatedPeriodo(): void
    {
        if ($this->periodo === 'rango') {
            $this->desde = $this->desde ?: now()->subDays(29)->toDateString();
            $this->hasta = $this->hasta ?: now()->toDateString();
        } else {
            $this->desde = '';
            $this->hasta = '';
        }
    }

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['zona_id', 'periodo', 'desde', 'hasta', 'mac_address'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $columna): void
    {
        if (!in_array($columna, ['updated_at', 'created_at', 'veces_entradas'], true)) {
            return;
        }

        if ($this->order_by === $columna) {
            $this->order_direction = $this->order_direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->order_by = $columna;
            $this->order_direction = 'desc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['zona_id', 'mac_address', 'periodo', 'desde', 'hasta']);
        $this->resetPage();
    }

    /**
     * Inicio y fin del periodo elegido (el rango libre se limita a un año).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function rango(): array
    {
        if ($this->periodo === 'hoy') {
            return [now()->startOfDay(), now()->endOfDay()];
        }

        if ($this->periodo === 'rango') {
            try {
                $desde = Carbon::parse($this->desde ?: now()->subDays(29)->toDateString())->startOfDay();
                $hasta = Carbon::parse($this->hasta ?: now()->toDateString())->endOfDay();
            } catch (\Throwable) {
                $desde = now()->subDays(29)->startOfDay();
                $hasta = now()->endOfDay();
            }
            if ($desde->gt($hasta)) {
                [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
            }
            if ($desde->diffInDays($hasta) > 366) {
                $desde = $hasta->copy()->subDays(365)->startOfDay();
            }

            return [$desde, $hasta];
        }

        return [now()->subDays(((int) $this->periodo) - 1)->startOfDay(), now()->endOfDay()];
    }

    public function render(MetricasHotspotService $servicio)
    {
        $user = Auth::user();
        $permitidas = $servicio->zonasPermitidas($user);

        $zonas = Zona::query()
            ->when($permitidas !== null, fn ($q) => $q->whereIn('id', $permitidas))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        // Una zona ajena (o inexistente) en la URL no debe filtrar datos de nadie
        if ($this->zona_id !== '' && !$zonas->contains('id', (int) $this->zona_id)) {
            $this->zona_id = '';
        }

        $zonaIds = $this->zona_id !== '' ? [(int) $this->zona_id] : $permitidas;
        [$desde, $hasta] = $this->rango();

        $resumen = $servicio->resumen($zonaIds, $desde, $hasta);
        $porDia = $servicio->porDia($zonaIds, $desde, $hasta);

        $dispositivos = $servicio->dispositivos($zonaIds)
            ->with('zona:id,nombre')
            ->whereBetween('updated_at', [$desde, $hasta])
            ->byMac($this->mac_address)
            ->orderBy($this->order_by, $this->order_direction)
            ->paginate(15);

        return view('livewire.hotspot-metrics-dashboard', [
            'zonas' => $zonas,
            'zonaActual' => $this->zona_id !== '' ? $zonas->firstWhere('id', (int) $this->zona_id) : null,
            'desdeFecha' => $desde,
            'hastaFecha' => $hasta,
            'resumen' => $resumen,
            'variacion' => $servicio->variacion($zonaIds, $desde, $hasta, $resumen),
            'porDia' => $porDia,
            'maxDia' => max(1, collect($porDia)->max('visitas'), collect($porDia)->max('nuevos')),
            'plataformas' => $servicio->plataformas($zonaIds, $desde, $hasta),
            'topDispositivos' => $servicio->top($zonaIds, $desde, $hasta, 'dispositivo'),
            'topNavegadores' => $servicio->top($zonaIds, $desde, $hasta, 'navegador'),
            'rankingZonas' => $this->zona_id === '' && $zonas->count() > 1 ? $servicio->rankingZonas($zonaIds, $desde, $hasta) : [],
            'dispositivos' => $dispositivos,
            'periodos' => self::PERIODOS,
        ]);
    }
}
