<?php

namespace App\Support;

/**
 * Logos de socios para el carrusel (pantalla en vivo y portal cautivo).
 *
 * Lee public/img/evento/socios/socios.json: [{"nombre": "...", "archivo": "..."}].
 * Las versiones "mini" (public/img/evento/socios/mini/*.webp, ~5 KB c/u) son para el
 * portal: se descargan en cada celular antes de autenticarse, por el enlace del evento.
 */
class SociosEvento
{
    public const CARPETA = 'img/evento/socios';

    /**
     * @return array<int, array{nombre: string, url: string}>
     */
    public static function lista(bool $mini = false): array
    {
        $manifiesto = public_path(self::CARPETA . '/socios.json');
        $lista = file_exists($manifiesto) ? json_decode(file_get_contents($manifiesto), true) : null;

        return collect(is_array($lista) ? $lista : [])
            ->filter(fn ($s) => !empty($s['archivo']))
            ->map(function ($s) use ($mini) {
                $archivo = basename($s['archivo']);
                $ruta = self::CARPETA . '/' . $archivo;

                if ($mini) {
                    $rutaMini = self::CARPETA . '/mini/' . pathinfo($archivo, PATHINFO_FILENAME) . '.webp';
                    $ruta = file_exists(public_path($rutaMini)) ? $rutaMini : $ruta;
                }

                return file_exists(public_path($ruta)) ? [
                    'nombre' => $s['nombre'] ?? pathinfo($archivo, PATHINFO_FILENAME),
                    'url' => asset($ruta),
                ] : null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
