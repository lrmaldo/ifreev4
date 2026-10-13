<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Lo consulta el login.html del MikroTik antes de mandar al asistente al portal:
 * si no responde a tiempo, el login.html lo conecta directo (respaldo).
 *
 * Revisa la base de datos porque sin ella el portal tampoco funciona.
 * Va sin el grupo "web" (no crea sesión) y con CORS abierto, porque la
 * petición sale de la página del hotspot, que es otro origen.
 */
class PortalPingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
            $ok = true;
        } catch (\Throwable $e) {
            report($e);
            $ok = false;
        }

        return response()
            ->json(['ok' => $ok], $ok ? 200 : 503)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-store');
    }
}
