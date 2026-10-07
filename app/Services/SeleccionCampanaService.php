<?php

namespace App\Services;

use App\Models\Campana;
use App\Models\Zona;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Decide qué campaña (video o imágenes) se muestra en el portal cautivo de una zona.
 */
class SeleccionCampanaService
{
    /**
     * Campañas activas de la zona.
     *
     * Si la zona no tiene campañas asignadas, se usan como respaldo las campañas activas
     * del cliente dueño de la zona y las globales (cliente_id nulo). El respaldo NO se
     * guarda en campana_zona, para no mezclar campañas entre clientes.
     */
    public function campanasActivas(Zona $zona): Collection
    {
        $asignadas = $zona->campanas()->activas()->get();

        if ($asignadas->isNotEmpty()) {
            return $asignadas;
        }

        $clienteId = $zona->user?->cliente_id;

        $respaldo = Campana::activas()
            ->where(function ($query) use ($clienteId) {
                $query->whereNull('cliente_id');
                if ($clienteId) {
                    $query->orWhere('cliente_id', $clienteId);
                }
            })
            ->get();

        Log::debug("Zona {$zona->id} sin campañas asignadas; respaldo con {$respaldo->count()} campañas del cliente/globales");

        return $respaldo;
    }

    /**
     * Selecciona el contenido a mostrar.
     *
     * @param  string|null  $ultimoTipo  'video' o 'imagen' mostrado en la visita anterior
     * @return array{tipo: ?string, campana: ?Campana, videoUrl: string, imagenes: array<int, string>, titulos: array<int, string>}
     *   titulos va en el mismo orden que imagenes (vacío = campaña sin título)
     */
    public function seleccionar(Zona $zona, ?string $ultimoTipo = null): array
    {
        return $this->seleccionarDe($this->campanasActivas($zona), $zona->seleccion_campanas ?? 'aleatorio', $ultimoTipo);
    }

    /**
     * Aplica el modo de selección sobre una colección de campañas ya filtradas.
     */
    public function seleccionarDe(Collection $campanas, string $modo, ?string $ultimoTipo = null): array
    {
        $resultado = ['tipo' => null, 'campana' => null, 'videoUrl' => '', 'imagenes' => [], 'titulos' => []];

        $videos = $campanas->where('tipo', 'video')->filter(fn ($c) => !empty($c->archivo_path));
        $imagenes = $campanas->where('tipo', 'imagen')->filter(fn ($c) => !empty($c->archivo_path));

        if ($videos->isEmpty() && $imagenes->isEmpty()) {
            return $resultado;
        }

        $mostrarVideo = $this->decidirVideo($videos, $imagenes, $modo, $ultimoTipo);

        if ($mostrarVideo && $videos->isNotEmpty()) {
            $candidatos = $modo === 'prioridad'
                ? $videos->where('prioridad', $videos->min('prioridad'))
                : $videos;
            $campana = $candidatos->random();

            return [
                'tipo' => 'video',
                'campana' => $campana,
                'videoUrl' => Storage::url($campana->archivo_path),
                'imagenes' => [],
                'titulos' => [trim((string) $campana->titulo)],
            ];
        }

        // En modo prioridad el carrusel solo muestra las imágenes con la mejor prioridad
        $seleccionadas = $modo === 'prioridad'
            ? $imagenes->where('prioridad', $imagenes->min('prioridad'))
            : $imagenes;

        return [
            'tipo' => 'imagen',
            'campana' => $seleccionadas->first(),
            'videoUrl' => '',
            'imagenes' => $seleccionadas->map(fn ($c) => Storage::url($c->archivo_path))->values()->all(),
            'titulos' => $seleccionadas->map(fn ($c) => trim((string) $c->titulo))->values()->all(),
        ];
    }

    protected function decidirVideo(Collection $videos, Collection $imagenes, string $modo, ?string $ultimoTipo): bool
    {
        // Con un solo tipo disponible no hay nada que decidir
        if ($videos->isEmpty() || $imagenes->isEmpty()) {
            return $videos->isNotEmpty();
        }

        switch ($modo) {
            case 'video':
                return true;

            case 'imagen':
                return false;

            case 'prioridad':
                $mejorVideo = $videos->min('prioridad') ?? 999;
                $mejorImagen = $imagenes->min('prioridad') ?? 999;
                if ($mejorVideo != $mejorImagen) {
                    return $mejorVideo < $mejorImagen;
                }
                // Empate: alternar
                return $ultimoTipo !== 'video';

            case 'aleatorio':
                // Alternancia estricta; en la primera visita se elige al azar
                if ($ultimoTipo === 'video') {
                    return false;
                }
                if ($ultimoTipo === 'imagen') {
                    return true;
                }
                return mt_rand(0, 1) === 1;

            default:
                return $ultimoTipo !== 'video';
        }
    }
}
