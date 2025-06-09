<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HotspotMetric;
use App\Models\Zona;
use App\Models\FormResponse;
use Carbon\Carbon;

class HotspotMetricsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener zonas existentes
        $zonas = Zona::all();

        if ($zonas->isEmpty()) {
            $this->command->warn('No hay zonas disponibles. Ejecuta el seeder de zonas primero.');
            return;
        }

        // Obtener formularios existentes (pueden ser null)
        $formularios = FormResponse::all();

        $dispositivos = ['iPhone', 'Samsung Galaxy', 'Xiaomi', 'Huawei', 'iPad', 'MacBook', 'Windows PC', 'Android Phone'];
        $navegadores = ['Chrome 120.0', 'Safari 17.1', 'Firefox 119.0', 'Edge 119.0', 'Opera 104.0'];
        $tiposVisuales = ['formulario', 'carrusel', 'video'];

        $this->command->info('Generando métricas de hotspot...');

        // Generar métricas para los últimos 60 días
        for ($i = 60; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i);

            // Generar entre 5 y 25 métricas por día
            $metricasPorDia = rand(5, 25);

            for ($j = 0; $j < $metricasPorDia; $j++) {
                $zona = $zonas->random();

                // Generar MAC address realista
                $mac = sprintf(
                    '%02x:%02x:%02x:%02x:%02x:%02x',
                    rand(0, 255), rand(0, 255), rand(0, 255),
                    rand(0, 255), rand(0, 255), rand(0, 255)
                );

                // 70% de probabilidad de que sea un dispositivo móvil
                $esMobile = rand(1, 100) <= 70;
                $dispositivo = $esMobile ?
                    collect(['iPhone', 'Samsung Galaxy', 'Xiaomi', 'Huawei', 'Android Phone'])->random() :
                    collect(['iPad', 'MacBook', 'Windows PC'])->random();

                // El navegador depende del dispositivo
                if (str_contains($dispositivo, 'iPhone') || str_contains($dispositivo, 'iPad') || str_contains($dispositivo, 'Mac')) {
                    $navegador = collect(['Safari 17.1', 'Chrome 120.0'])->random();
                } else {
                    $navegador = collect(['Chrome 120.0', 'Firefox 119.0', 'Edge 119.0'])->random();
                }

                $tipoVisual = collect($tiposVisuales)->random();

                // Duración basada en el tipo visual
                $duracion = match($tipoVisual) {
                    'formulario' => rand(30, 180), // 30s a 3min
                    'carrusel' => rand(15, 90),    // 15s a 1.5min
                    'video' => rand(60, 300),      // 1min a 5min
                };

                // 40% de probabilidad de click en botón CTA
                $clicBoton = rand(1, 100) <= 40;

                // 25% de probabilidad de completar formulario
                $completaFormulario = rand(1, 100) <= 25;
                $formularioId = ($completaFormulario && $formularios->isNotEmpty()) ?
                    $formularios->random()->id : null;

                // Usuarios recurrentes (10% de probabilidad)
                $vecesEntradas = rand(1, 100) <= 10 ? rand(2, 5) : 1;

                HotspotMetric::create([
                    'zona_id' => $zona->id,
                    'mac_address' => $mac,
                    'formulario_id' => $formularioId,
                    'dispositivo' => $dispositivo,
                    'navegador' => $navegador,
                    'tipo_visual' => $tipoVisual,
                    'duracion_visual' => $duracion,
                    'clic_boton' => $clicBoton,
                    'veces_entradas' => $vecesEntradas,
                    'created_at' => $fecha->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                    'updated_at' => $fecha->copy()->addHours(rand(8, 22))->addMinutes(rand(0, 59)),
                ]);
            }
        }

        $this->command->info('✅ Métricas de hotspot generadas exitosamente.');
        $this->command->info('📊 Total de métricas creadas: ' . HotspotMetric::count());
    }
}
