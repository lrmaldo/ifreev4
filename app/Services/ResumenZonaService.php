<?php

namespace App\Services;

use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\Zona;
use Carbon\CarbonInterface;

/**
 * Arma el resumen periódico de actividad de una zona para Telegram.
 */
class ResumenZonaService
{
    /**
     * ¿Ya toca mandar el resumen de esta zona?
     */
    public function tocaResumen(Zona $zona, CarbonInterface $ahora): bool
    {
        if (!$zona->telegram_resumen_minutos) {
            return false;
        }

        return !$zona->telegram_ultimo_resumen_at
            || $zona->telegram_ultimo_resumen_at->copy()->addMinutes($zona->telegram_resumen_minutos)->lte($ahora);
    }

    /**
     * Mensaje HTML con la actividad entre $desde y $hasta, o null si no hubo actividad.
     */
    public function construirMensaje(Zona $zona, CarbonInterface $desde, CarbonInterface $hasta): ?string
    {
        $nuevos = HotspotMetric::where('zona_id', $zona->id)->whereBetween('created_at', [$desde, $hasta])->count();
        $reconexiones = HotspotMetric::where('zona_id', $zona->id)
            ->where('created_at', '<', $desde)
            ->whereBetween('updated_at', [$desde, $hasta])
            ->count();
        $registros = FormResponse::where('zona_id', $zona->id)->whereBetween('created_at', [$desde, $hasta])->count();

        if ($nuevos + $reconexiones + $registros === 0) {
            return null;
        }

        $inicioDia = $hasta->copy()->startOfDay();
        $hoy = HotspotMetric::where('zona_id', $zona->id)->where('updated_at', '>=', $inicioDia);
        $dispositivosHoy = (clone $hoy)->count();
        $android = (clone $hoy)->where('sistema_operativo', 'like', 'Android%')->count();
        $ios = (clone $hoy)->where('sistema_operativo', 'like', 'iOS%')->count();
        $registrosHoy = FormResponse::where('zona_id', $zona->id)->where('created_at', '>=', $inicioDia)->count();

        $nombre = e($zona->nombre);

        return "<b>📊 {$nombre}</b> · {$desde->format('H:i')}–{$hasta->format('H:i')}\n\n"
            . "🆕 Dispositivos nuevos: <b>{$nuevos}</b>\n"
            . "🔁 Reconexiones: <b>{$reconexiones}</b>\n"
            . "📝 Registros: <b>{$registros}</b>\n\n"
            . "<b>Hoy</b>\n"
            . "📶 Dispositivos: <b>{$dispositivosHoy}</b> (Android {$android} · iPhone {$ios})\n"
            . "📝 Registros: <b>{$registrosHoy}</b>";
    }
}
