<?php

namespace App\Livewire;

use App\Models\Campana;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Zona;
use App\Services\EventoEnVivoService;
use App\Services\MetricasHotspotService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Inicio del panel: actividad de hoy, últimos 14 días y accesos rápidos.
 * Admin y técnico ven todas las zonas; un cliente solo las suyas.
 */
class Dashboard extends Component
{
    public function render(MetricasHotspotService $servicio)
    {
        $user = Auth::user();
        $esAdmin = $user->hasRole('admin');
        $zonaIds = $servicio->zonasPermitidas($user);

        $hoy = [now()->startOfDay(), now()->endOfDay()];
        $catorce = [now()->subDays(13)->startOfDay(), now()->endOfDay()];
        $semana = [now()->subDays(6)->startOfDay(), now()->endOfDay()];

        $resumenHoy = $servicio->resumen($zonaIds, ...$hoy);
        // Hoy va a medias: se compara contra ayer hasta la misma hora
        $resumenAyer = $servicio->resumen($zonaIds, now()->subDay()->startOfDay(), now()->subDay());
        $porDia = $servicio->porDia($zonaIds, ...$catorce);

        $zonasQuery = Zona::query()->when($zonaIds !== null, fn ($q) => $q->whereIn('id', $zonaIds));

        $campanas = Campana::query()->when($zonaIds !== null, fn ($q) => $q->whereHas('zonas', fn ($z) => $z->whereIn('zonas.id', $zonaIds)));

        return view('livewire.dashboard', [
            'user' => $user,
            'esAdmin' => $esAdmin,
            'esCliente' => $zonaIds !== null,
            'resumenHoy' => $resumenHoy,
            'variacionHoy' => collect(['visitas', 'registros', 'nuevos'])->mapWithKeys(fn ($k) => [$k => $resumenAyer[$k] > 0
                ? (int) round(($resumenHoy[$k] - $resumenAyer[$k]) / $resumenAyer[$k] * 100)
                : null])->all(),
            'conectados' => $servicio->dispositivos($zonaIds)->where('updated_at', '>=', now()->subMinutes(EventoEnVivoService::MINUTOS_ACTIVO))->count(),
            'porDia' => $porDia,
            'maxDia' => max(1, collect($porDia)->max('visitas'), collect($porDia)->max('nuevos')),
            'totalesSemana' => [
                'visitas' => array_sum(array_column(array_slice($porDia, -7), 'visitas')),
                'registros' => array_sum(array_column(array_slice($porDia, -7), 'registros')),
            ],
            'rankingZonas' => $servicio->rankingZonas($zonaIds, ...[...$semana, 5]),
            'totalZonas' => (clone $zonasQuery)->count(),
            'zonasCliente' => $zonaIds !== null ? (clone $zonasQuery)->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'tipo_registro']) : collect(),
            'campanasActivas' => (clone $campanas)->activas()->count(),
            'campanasPorVencer' => (clone $campanas)
                ->where('visible', true)
                ->where('siempre_visible', false)
                ->whereBetween('fecha_fin', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->orderBy('fecha_fin')
                ->limit(5)
                ->get(['id', 'titulo', 'fecha_fin', 'tipo']),
            'totalUsuarios' => $esAdmin ? User::count() : null,
            'totalClientes' => $esAdmin ? Cliente::count() : null,
        ]);
    }
}
