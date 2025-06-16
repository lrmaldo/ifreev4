<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Event;
use App\Events\HotspotMetricCreated;
use App\Listeners\SendTelegramNotification;
use App\Listeners\SendTelegramFormMetricNotification;

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

        // Registrar eventos y listeners
        Event::listen(
            HotspotMetricCreated::class,
            SendTelegramNotification::class
        );

        // Registrar listener para notificaciones de métricas con formulario
        Event::listen(
            HotspotMetricCreated::class,
            SendTelegramFormMetricNotification::class
        );
    }
}
