<?php

namespace App\Console\Commands;

use App\Models\HotspotMetric;
use Illuminate\Console\Command;

class FormatearMetricasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metricas:formatear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Formatea las métricas existentes que tienen user agent en lugar de información estructurada';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando formateo de métricas...');

        // Obtener todas las métricas que contienen strings de user agent
        $metricas = HotspotMetric::where('dispositivo', 'like', '%Mozilla/5.0%')
            ->orWhere('navegador', 'like', '%Mozilla/5.0%')
            ->get();

        $total = $metricas->count();
        $this->info("Se encontraron {$total} métricas para formatear.");

        if ($total === 0) {
            $this->info('No hay métricas que requieran formateo.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($metricas as $metrica) {
            $ua = $metrica->getRawOriginal('dispositivo');

            // Detectar dispositivo
            $dispositivo = $this->extraerInformacionDispositivo($ua);

            // Detectar navegador
            $navegador = $this->extraerInformacionNavegador($ua);

            // Detectar sistema operativo
            $sistemaOperativo = $this->extraerSistemaOperativo($ua);

            // Actualizar la métrica sin disparar los eventos
            HotspotMetric::where('id', $metrica->id)->update([
                'dispositivo' => $dispositivo,
                'navegador' => $navegador,
                'sistema_operativo' => $sistemaOperativo
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Formateo de métricas completado correctamente.');

        return 0;
    }

    /**
     * Extrae información del dispositivo desde el user agent
     */
    protected function extraerInformacionDispositivo($ua)
    {
        $dispositivo = 'Desconocido';

        // Extraer modelo de dispositivo móvil Android
        $regexModelo = '/Android[\s\d\.]+;\s([^;)]+)/i';
        preg_match($regexModelo, $ua, $modeloMatch);

        if (!empty($modeloMatch[1])) {
            $modelo = trim($modeloMatch[1]);
            $dispositivo = $modelo;

            // Detectar y formatear dispositivos Xiaomi/POCO
            if (preg_match('/(M2\d{3}|22\d{6}|21\d{6}|SM-[A-Za-z0-9]+)/', $modelo)) {
                if (stripos($ua, 'poco') !== false) {
                    $dispositivo = "POCO $modelo";
                } elseif (stripos($ua, 'redmi') !== false) {
                    $dispositivo = "Redmi $modelo";
                } elseif (stripos($ua, 'samsung') !== false || str_starts_with($modelo, 'SM-')) {
                    $dispositivo = "Samsung $modelo";
                } elseif (stripos($ua, 'xiaomi') !== false) {
                    $dispositivo = "Xiaomi $modelo";
                }
            }
        }
        // Si es iPhone/iPad
        elseif (str_contains($ua, 'iPhone')) {
            $dispositivo = 'iPhone';
        }
        elseif (str_contains($ua, 'iPad')) {
            $dispositivo = 'iPad';
        }
        // Si es un dispositivo Windows
        elseif (str_contains($ua, 'Windows')) {
            $dispositivo = 'PC Windows';
        }
        // Si es un dispositivo Mac
        elseif (str_contains($ua, 'Macintosh')) {
            $dispositivo = 'Mac';
        }

        return $dispositivo;
    }

    /**
     * Extrae información del navegador desde el user agent
     */
    protected function extraerInformacionNavegador($ua)
    {
        $navegador = 'Desconocido';
        $version = '';

        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg') && !str_contains($ua, 'OPR')) {
            $navegador = 'Chrome';
            preg_match('/Chrome\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Firefox')) {
            $navegador = 'Firefox';
            preg_match('/Firefox\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            $navegador = 'Safari';
            preg_match('/Version\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'Edg')) {
            $navegador = 'Edge';
            preg_match('/Edg\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) {
            $navegador = 'Opera';
            preg_match('/(OPR|Opera)\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[2])) $version = $match[2];
        } elseif (str_contains($ua, 'MIUI')) {
            $navegador = 'Navegador MIUI';
            preg_match('/MiuiBrowser\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        } elseif (str_contains($ua, 'SamsungBrowser')) {
            $navegador = 'Samsung Internet';
            preg_match('/SamsungBrowser\/(\d+(\.\d+)?)/', $ua, $match);
            if (!empty($match[1])) $version = $match[1];
        }

        if (!empty($version)) {
            $navegador .= ' ' . $version;
        }

        return $navegador;
    }

    /**
     * Extrae información del sistema operativo desde el user agent
     */
    protected function extraerSistemaOperativo($ua)
    {
        $sistemaOperativo = 'Desconocido';

        if (str_contains($ua, 'Android')) {
            preg_match('/Android\s([0-9\.]+)/', $ua, $match);
            $sistemaOperativo = 'Android ' . (!empty($match[1]) ? $match[1] : '');
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iPod')) {
            preg_match('/OS\s([0-9_]+)/', $ua, $match);
            $version = !empty($match[1]) ? str_replace('_', '.', $match[1]) : '';
            $sistemaOperativo = 'iOS ' . $version;
        } elseif (str_contains($ua, 'Windows')) {
            preg_match('/Windows NT\s([0-9\.]+)/', $ua, $match);
            if (!empty($match[1])) {
                // Mapeo de versiones de Windows NT a nombres comerciales
                $windowsVersions = [
                    '10.0' => 'Windows 10/11',
                    '6.3' => 'Windows 8.1',
                    '6.2' => 'Windows 8',
                    '6.1' => 'Windows 7',
                    '6.0' => 'Windows Vista',
                    '5.2' => 'Windows XP x64',
                    '5.1' => 'Windows XP',
                    '5.0' => 'Windows 2000'
                ];
                $sistemaOperativo = isset($windowsVersions[$match[1]]) ? $windowsVersions[$match[1]] : 'Windows ' . $match[1];
            } else {
                $sistemaOperativo = 'Windows';
            }
        } elseif (str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh')) {
            preg_match('/Mac OS X\s?([0-9_\.]+)?/', $ua, $match);
            $version = !empty($match[1]) ? str_replace('_', '.', $match[1]) : '';
            $sistemaOperativo = 'macOS ' . $version;
        } elseif (str_contains($ua, 'Linux')) {
            if (str_contains($ua, 'Ubuntu')) {
                $sistemaOperativo = 'Ubuntu Linux';
            } else if (str_contains($ua, 'Fedora')) {
                $sistemaOperativo = 'Fedora Linux';
            } else if (str_contains($ua, 'Debian')) {
                $sistemaOperativo = 'Debian Linux';
            } else {
                $sistemaOperativo = 'Linux';
            }
        }

        return $sistemaOperativo;
    }
}
