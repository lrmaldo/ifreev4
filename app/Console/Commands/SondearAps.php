<?php

namespace App\Console\Commands;

use App\Models\AccessPoint;
use App\Services\AccessPointsService;
use Illuminate\Console\Command;

class SondearAps extends Command
{
    protected $signature = 'aps:sondear';

    protected $description = 'Consulta por SNMP los access points activos y guarda clientes y tráfico';

    public function handle(AccessPointsService $servicio): int
    {
        foreach (AccessPoint::where('activo', true)->get() as $ap) {
            $servicio->sondear($ap);

            $this->line($ap->en_linea
                ? "{$ap->nombre}: {$ap->clientes} clientes"
                : "{$ap->nombre}: {$ap->ultimo_error}");
        }

        // Una vez por hora se borra el historial viejo
        if (now()->minute === 0) {
            $servicio->borrarHistorialViejo();
        }

        return self::SUCCESS;
    }
}
