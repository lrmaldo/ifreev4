<?php

namespace App\Http\Middleware;

use App\Services\PortalToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige el token del portal cautivo (header X-Portal-Token) en los endpoints públicos
 * y fija zona_id y mac_address con los valores firmados.
 */
class VerificarTokenPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $datos = PortalToken::validar($request->header('X-Portal-Token') ?? $request->input('portal_token'));

        if (!$datos) {
            return $this->rechazar($request, 'Token de portal inválido o expirado');
        }

        if ($request->filled('zona_id') && (int) $request->input('zona_id') !== $datos['zona_id']) {
            return $this->rechazar($request, 'La zona no coincide con el token');
        }

        if ($request->filled('mac_address') && $request->input('mac_address') !== $datos['mac_address']) {
            return $this->rechazar($request, 'La MAC no coincide con el token');
        }

        $request->merge($datos);

        return $next($request);
    }

    private function rechazar(Request $request, string $motivo): Response
    {
        Log::warning("Portal: petición rechazada ({$motivo})", [
            'ruta' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return response()->json(['success' => false, 'message' => $motivo], 403);
    }
}
