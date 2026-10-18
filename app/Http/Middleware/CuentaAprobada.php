<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Una cuenta sin rol todavía no ha sido aprobada por Sattlink: solo ve la página
 * de "cuenta en revisión". Antes el registro público daba el rol cliente al instante.
 */
class CuentaAprobada
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->roles()->doesntExist()) {
            return redirect()->route('cuenta.pendiente');
        }

        return $next($request);
    }
}
