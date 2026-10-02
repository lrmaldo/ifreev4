<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Los listeners de app/Listeners se registran solos (event discovery de Laravel).
        // No registrarlos aquí también: cada notificación se encolaría dos veces.

        // Límites del portal cautivo por dispositivo (IP + MAC), no solo por IP:
        // todos los clientes de un hotspot salen por la misma IP pública del MikroTik.
        RateLimiter::for('portal', fn (Request $request) => Limit::perMinute(20)->by('portal|' . $this->dispositivo($request)));
        RateLimiter::for('portal-api', fn (Request $request) => Limit::perMinute(60)->by('portal-api|' . $this->dispositivo($request)));
    }

    private function dispositivo(Request $request): string
    {
        $mac = $request->input('mac') ?: $request->input('mac_address');

        if (!$mac && $request->header('X-Portal-Token')) {
            $mac = sha1($request->header('X-Portal-Token'));
        }

        return $request->ip() . '|' . ($mac ?: 'sin-mac');
    }
}
