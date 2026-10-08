<?php

namespace App\Services;

use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Cálculos de métricas del hotspot para la página de Métricas y el Dashboard.
 *
 * Cada fila de hotspot_metrics es un dispositivo (MAC) en una zona: created_at es la
 * primera vez que se vio y updated_at la última. Las entradas al portal por día salen
 * de metrica_detalles (tipo_evento = vista) y los registros de form_responses.
 *
 * $zonaIds = null significa "todas las zonas"; un arreglo limita a esas zonas.
 */
class MetricasHotspotService
{
    /**
     * Zonas cuyas métricas puede ver el usuario: null = todas (admin y técnico).
     */
    public function zonasPermitidas(User $user): ?array
    {
        if ($user->hasAnyRole(['admin', 'tecnico'])) {
            return null;
        }

        return Zona::where('user_id', $user->id)->pluck('id')->all();
    }

    public function puedeVerZona(User $user, $zonaId): bool
    {
        $permitidas = $this->zonasPermitidas($user);

        return $permitidas === null || in_array((int) $zonaId, array_map('intval', $permitidas), true);
    }

    public function resumen(?array $zonaIds, Carbon $desde, Carbon $hasta): array
    {
        $nuevos = $this->dispositivos($zonaIds)->whereBetween('created_at', [$desde, $hasta])->count();
        $activos = $this->dispositivos($zonaIds)->whereBetween('updated_at', [$desde, $hasta]);
        $totalActivos = (clone $activos)->count();
        $recurrentes = (clone $activos)->where('veces_entradas', '>', 1)->count();

        $eventos = $this->eventos($zonaIds)->whereBetween('fecha_hora', [$desde, $hasta]);
        $visitas = (clone $eventos)->where('tipo_evento', 'vista')->count();
        $clics = (clone $eventos)->where('tipo_evento', 'clic')->count();

        $registros = $this->registros($zonaIds)->whereBetween('created_at', [$desde, $hasta])->count();

        return [
            'visitas' => $visitas,
            'nuevos' => $nuevos,
            'activos' => $totalActivos,
            'recurrentes' => $recurrentes,
            'registros' => $registros,
            'clics' => $clics,
            'conversion' => $nuevos > 0 ? round($registros / $nuevos * 100, 1) : 0,
            'ctr' => $visitas > 0 ? round($clics / $visitas * 100, 1) : 0,
            'duracion_promedio' => (int) round((clone $activos)->avg('duracion_visual') ?? 0),
        ];
    }

    /**
     * Cambio porcentual de visitas, dispositivos nuevos y registros contra el periodo
     * anterior de la misma duración. null cuando el periodo anterior fue 0.
     */
    public function variacion(?array $zonaIds, Carbon $desde, Carbon $hasta, array $actual): array
    {
        $segundos = $desde->diffInSeconds($hasta);
        $antesHasta = $desde->copy()->subSecond();
        $antesDesde = $antesHasta->copy()->subSeconds($segundos);

        $anterior = [
            'visitas' => $this->eventos($zonaIds)->where('tipo_evento', 'vista')->whereBetween('fecha_hora', [$antesDesde, $antesHasta])->count(),
            'nuevos' => $this->dispositivos($zonaIds)->whereBetween('created_at', [$antesDesde, $antesHasta])->count(),
            'registros' => $this->registros($zonaIds)->whereBetween('created_at', [$antesDesde, $antesHasta])->count(),
        ];

        return collect($anterior)->map(fn ($antes, $clave) => $antes > 0
            ? (int) round((($actual[$clave] ?? 0) - $antes) / $antes * 100)
            : null)->all();
    }

    /**
     * Visitas, dispositivos nuevos y registros por día, con los días sin datos en cero.
     *
     * @return array<int, array{fecha: string, visitas: int, nuevos: int, registros: int}>
     */
    public function porDia(?array $zonaIds, Carbon $desde, Carbon $hasta): array
    {
        $visitas = $this->contarPorDia($this->eventos($zonaIds)->where('tipo_evento', 'vista'), 'fecha_hora', $desde, $hasta);
        $nuevos = $this->contarPorDia($this->dispositivos($zonaIds), 'created_at', $desde, $hasta);
        $registros = $this->contarPorDia($this->registros($zonaIds), 'created_at', $desde, $hasta);

        $dias = [];
        foreach (CarbonPeriod::create($desde->copy()->startOfDay(), '1 day', $hasta->copy()->startOfDay()) as $dia) {
            $clave = $dia->toDateString();
            $dias[] = [
                'fecha' => $clave,
                'visitas' => (int) ($visitas[$clave] ?? 0),
                'nuevos' => (int) ($nuevos[$clave] ?? 0),
                'registros' => (int) ($registros[$clave] ?? 0),
            ];
        }

        return $dias;
    }

    /**
     * Dispositivos activos en el periodo por plataforma.
     */
    public function plataformas(?array $zonaIds, Carbon $desde, Carbon $hasta): array
    {
        $activos = $this->dispositivos($zonaIds)->whereBetween('updated_at', [$desde, $hasta]);
        $total = (clone $activos)->count();

        $android = (clone $activos)->where('sistema_operativo', 'like', 'Android%')->count();
        $ios = (clone $activos)->where(fn ($q) => $q->where('sistema_operativo', 'like', 'iOS%')->orWhere('sistema_operativo', 'like', 'iPadOS%'))->count();
        $windows = (clone $activos)->where('sistema_operativo', 'like', 'Windows%')->count();
        $mac = (clone $activos)->where(fn ($q) => $q->where('sistema_operativo', 'like', 'Mac%')->orWhere('sistema_operativo', 'like', 'OS X%'))->count();

        return array_filter([
            'Android' => $android,
            'iPhone / iPad' => $ios,
            'Windows' => $windows,
            'Mac' => $mac,
            'Otros' => max(0, $total - $android - $ios - $windows - $mac),
        ]);
    }

    /**
     * Los valores más comunes de una columna (dispositivo, navegador) entre los activos.
     */
    public function top(?array $zonaIds, Carbon $desde, Carbon $hasta, string $columna, int $limite = 5): array
    {
        abort_unless(in_array($columna, ['dispositivo', 'navegador', 'sistema_operativo'], true), 500);

        return $this->dispositivos($zonaIds)
            ->whereBetween('updated_at', [$desde, $hasta])
            ->whereNotNull($columna)
            ->where($columna, '!=', '')
            ->select($columna . ' as nombre', DB::raw('COUNT(*) as total'))
            ->groupBy($columna)
            ->orderByDesc('total')
            ->limit($limite)
            ->get()
            ->map(fn ($fila) => ['nombre' => $fila->nombre, 'total' => (int) $fila->total])
            ->all();
    }

    /**
     * Zonas con más visitas en el periodo.
     *
     * @return array<int, array{id: int, nombre: string, visitas: int, registros: int}>
     */
    public function rankingZonas(?array $zonaIds, Carbon $desde, Carbon $hasta, int $limite = 6): array
    {
        $visitas = MetricaDetalle::query()
            ->join('hotspot_metrics', 'hotspot_metrics.id', '=', 'metrica_detalles.metrica_id')
            ->where('metrica_detalles.tipo_evento', 'vista')
            ->whereBetween('metrica_detalles.fecha_hora', [$desde, $hasta])
            ->when($zonaIds !== null, fn ($q) => $q->whereIn('hotspot_metrics.zona_id', $zonaIds))
            ->selectRaw('hotspot_metrics.zona_id as zona_id, COUNT(*) as total')
            ->groupBy('hotspot_metrics.zona_id')
            ->orderByDesc('total')
            ->limit($limite)
            ->pluck('total', 'zona_id');

        if ($visitas->isEmpty()) {
            return [];
        }

        $registros = $this->registros(array_keys($visitas->all()))
            ->whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('zona_id, COUNT(*) as total')
            ->groupBy('zona_id')
            ->pluck('total', 'zona_id');

        $nombres = Zona::whereIn('id', $visitas->keys())->pluck('nombre', 'id');

        return $visitas->map(fn ($total, $zonaId) => [
            'id' => (int) $zonaId,
            'nombre' => $nombres[$zonaId] ?? "Zona #{$zonaId}",
            'visitas' => (int) $total,
            'registros' => (int) ($registros[$zonaId] ?? 0),
        ])->values()->all();
    }

    public function dispositivos(?array $zonaIds): Builder
    {
        return HotspotMetric::query()->when($zonaIds !== null, fn ($q) => $q->whereIn('zona_id', $zonaIds));
    }

    public function registros(?array $zonaIds): Builder
    {
        return FormResponse::query()->when($zonaIds !== null, fn ($q) => $q->whereIn('zona_id', $zonaIds));
    }

    public function eventos(?array $zonaIds): Builder
    {
        return MetricaDetalle::query()->when($zonaIds !== null, fn ($q) => $q->whereIn(
            'metrica_id',
            HotspotMetric::select('id')->whereIn('zona_id', $zonaIds)
        ));
    }

    private function contarPorDia(Builder $query, string $columna, Carbon $desde, Carbon $hasta): array
    {
        return $query
            ->whereBetween($columna, [$desde, $hasta])
            ->selectRaw("DATE({$columna}) as dia, COUNT(*) as total")
            ->groupBy('dia')
            ->pluck('total', 'dia')
            ->all();
    }
}
