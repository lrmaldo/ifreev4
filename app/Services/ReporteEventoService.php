<?php

namespace App\Services;

use App\Models\AccessPoint;
use App\Models\AccessPointLectura;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Models\RifaGanador;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Cifras de un evento para la página pública de resultados (/casos/{slug}).
 * Solo devuelve totales: ningún nombre, teléfono, correo ni empresa sale de aquí.
 */
class ReporteEventoService
{
    /** Un estado con menos registros que esto se suma a "Otros" para no señalar a nadie. */
    public const MINIMO_POR_ESTADO = 3;

    public function generar(Zona $zona, Carbon $desde, Carbon $hasta): array
    {
        $dispositivos = HotspotMetric::where('zona_id', $zona->id)->whereBetween('updated_at', [$desde, $hasta]);
        $nuevos = HotspotMetric::where('zona_id', $zona->id)->whereBetween('created_at', [$desde, $hasta])->count();

        $eventos = MetricaDetalle::whereIn('metrica_id', HotspotMetric::where('zona_id', $zona->id)->select('id'))
            ->whereBetween('fecha_hora', [$desde, $hasta]);
        $vistas = (clone $eventos)->where('tipo_evento', 'vista')->count();
        $clics = (clone $eventos)->where('tipo_evento', 'clic')->count();

        $respuestas = FormResponse::where('zona_id', $zona->id)->whereBetween('created_at', [$desde, $hasta])
            ->get(['respuestas', 'created_at']);
        $registros = $respuestas->count();

        return [
            'registros' => $registros,
            'dispositivos' => (clone $dispositivos)->count(),
            'recurrentes' => (clone $dispositivos)->where('veces_entradas', '>', 1)->count(),
            'impresiones' => $vistas,
            'clics' => $clics,
            'conversion' => $nuevos > 0 ? (int) round(min($registros, $nuevos) / $nuevos * 100) : 0,
            'segundos_vista' => (int) round((clone $dispositivos)->avg('duracion_visual') ?? 0),
            'plataformas' => $this->plataformas($zona, $desde, $hasta),
            'por_hora' => $this->porHora($respuestas),
            'estados' => $this->estados($zona, $respuestas),
            'con_empresa' => $this->conEmpresa($zona, $respuestas),
            'campanas' => $zona->campanas()->count(),
            'rifa' => RifaGanador::where('zona_id', $zona->id)->whereBetween('created_at', [$desde, $hasta])->count(),
            'red' => $this->red($zona, $desde, $hasta),
        ];
    }

    private function plataformas(Zona $zona, Carbon $desde, Carbon $hasta): array
    {
        $servicio = app(MetricasHotspotService::class);
        $conteo = $servicio->plataformas([$zona->id], $desde, $hasta);
        $total = array_sum($conteo) ?: 1;

        return collect($conteo)
            ->map(fn ($n, $nombre) => ['nombre' => $nombre, 'total' => $n, 'pct' => (int) round($n / $total * 100)])
            ->sortByDesc('total')->values()->all();
    }

    /** Registros por hora del día (las 24 horas, para ver el pico). */
    private function porHora($respuestas): array
    {
        $horas = array_fill(0, 24, 0);
        foreach ($respuestas as $r) {
            $horas[(int) $r->created_at->format('G')]++;
        }

        // Solo el tramo con actividad, con una hora de margen
        $conDatos = array_keys(array_filter($horas));
        if (! $conDatos) {
            return [];
        }
        $inicio = max(0, min($conDatos) - 1);
        $fin = min(23, max($conDatos) + 1);

        return collect(range($inicio, $fin))->map(fn ($h) => ['hora' => $h, 'total' => $horas[$h]])->all();
    }

    /** Registros por estado de la república, si el formulario tiene un campo "Estado". */
    private function estados(Zona $zona, $respuestas): array
    {
        $campo = FormField::where('zona_id', $zona->id)
            ->whereIn('tipo', ['select', 'radio', 'text'])
            ->get()
            ->first(fn ($c) => Str::contains(Str::lower(Str::ascii($c->campo . ' ' . $c->etiqueta)), 'estado'));

        if (! $campo) {
            return [];
        }

        $conteo = $respuestas
            ->map(fn ($r) => trim((string) ($r->respuestas[$campo->campo] ?? '')))
            ->filter()
            ->countBy()
            ->sortDesc();

        $visibles = $conteo->filter(fn ($n) => $n >= self::MINIMO_POR_ESTADO);
        $otros = $conteo->diffKeys($visibles)->sum();

        return $visibles->map(fn ($n, $estado) => ['nombre' => $estado, 'total' => $n])->values()
            ->when($otros > 0, fn ($c) => $c->push(['nombre' => 'Otros', 'total' => $otros]))
            ->all();
    }

    /** Cuántos registros dejaron el nombre de su empresa (gente de la industria). */
    private function conEmpresa(Zona $zona, $respuestas): int
    {
        $campo = FormField::where('zona_id', $zona->id)->get()
            ->first(fn ($c) => Str::contains(Str::lower(Str::ascii($c->campo . ' ' . $c->etiqueta)), ['empresa', 'negocio', 'compania']));

        return $campo
            ? $respuestas->filter(fn ($r) => trim((string) ($r->respuestas[$campo->campo] ?? '')) !== '')->count()
            : 0;
    }

    /** Pico de personas conectadas a la vez y tráfico total, de las lecturas SNMP de los AP. */
    private function red(Zona $zona, Carbon $desde, Carbon $hasta): ?array
    {
        $aps = AccessPoint::where('zona_id', $zona->id)->pluck('id');
        if ($aps->isEmpty()) {
            return null;
        }

        $porMinuto = [];
        $bytes = 0;
        AccessPointLectura::whereIn('access_point_id', $aps)
            ->whereBetween('created_at', [$desde, $hasta])
            ->select(['clientes', 'bajada_bps', 'subida_bps', 'created_at'])
            ->orderBy('id')
            ->lazy(2000)
            ->each(function ($l) use (&$porMinuto, &$bytes) {
                $minuto = $l->created_at->format('Y-m-d H:i');
                $porMinuto[$minuto] = ($porMinuto[$minuto] ?? 0) + $l->clientes;
                // Una lectura por minuto: bits por segundo × 60 s ÷ 8
                $bytes += ($l->bajada_bps + $l->subida_bps) * 60 / 8;
            });

        if (! $porMinuto) {
            return null;
        }

        $pico = max($porMinuto);

        return [
            'aps' => $aps->count(),
            'pico' => $pico,
            'pico_momento' => Carbon::parse(array_search($pico, $porMinuto, true)),
            'gb' => round($bytes / 1e9, 1),
        ];
    }
}
