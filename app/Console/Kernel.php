<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejecutar limpieza de campañas caducadas diariamente a las 01:00
        $schedule->command('campanas:limpiar-caducadas')->dailyAt('01:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\VerificarRelacionesCampanas::class,
        \App\Console\Commands\VerificarRespuestasFormularioCommand::class,
        \App\Console\Commands\CrearRespuestaEjemploCommand::class,
        \App\Console\Commands\FormatearMetricasCommand::class,
        \App\Console\Commands\ProbarFormateadorRespuestasCommand::class,
        \App\Console\Commands\CorregirRespuestasFormularioCommand::class,
    ];
}
