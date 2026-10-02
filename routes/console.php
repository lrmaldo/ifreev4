<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Resúmenes de Telegram para zonas en modo resumen (cada zona define su intervalo).
// Requiere en el servidor: * * * * * php artisan schedule:run
Schedule::command('telegram:resumen-zonas')->everyMinute()->withoutOverlapping();
