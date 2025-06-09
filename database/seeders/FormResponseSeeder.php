<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FormResponse;
use App\Models\Zona;

class FormResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener algunas zonas existentes
        $zonas = Zona::take(3)->get();

        if ($zonas->isEmpty()) {
            $this->command->info('No hay zonas disponibles. Crea algunas zonas primero.');
            return;
        }

        $dispositivos = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Android 12; Mobile; rv:109.0) Gecko/111.0 Firefox/111.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];

        $navegadores = ['Chrome', 'Safari', 'Firefox', 'Edge'];

        foreach ($zonas as $zona) {
            // Crear 5-15 respuestas por zona
            $numRespuestas = rand(5, 15);

            for ($i = 0; $i < $numRespuestas; $i++) {
                $macAddress = sprintf(
                    '%02X:%02X:%02X:%02X:%02X:%02X',
                    rand(0, 255), rand(0, 255), rand(0, 255),
                    rand(0, 255), rand(0, 255), rand(0, 255)
                );

                $respuestasSample = [
                    1 => fake()->name(),
                    2 => fake()->email(),
                    3 => fake()->phoneNumber(),
                    4 => fake()->randomElement(['18-25', '26-35', '36-45', '46-55', '55+']),
                    5 => fake()->randomElement(['Masculino', 'Femenino', 'Otro']),
                    6 => fake()->company()
                ];

                FormResponse::create([
                    'zona_id' => $zona->id,
                    'mac_address' => $macAddress,
                    'dispositivo' => fake()->randomElement($dispositivos),
                    'navegador' => fake()->randomElement($navegadores),
                    'tiempo_activo' => rand(30, 1800), // 30 segundos a 30 minutos
                    'formulario_completado' => rand(0, 10) > 2, // 80% completados
                    'respuestas' => $respuestasSample,
                    'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                    'updated_at' => fake()->dateTimeBetween('-30 days', 'now')
                ]);
            }
        }

        $this->command->info('Respuestas de formulario creadas exitosamente.');
    }
}
