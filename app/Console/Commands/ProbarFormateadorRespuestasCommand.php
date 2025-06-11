<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormResponse;
use App\Models\Zona;
use App\Models\FormField;
use App\Models\FormFieldOption;

class ProbarFormateadorRespuestasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formulario:probar-formato';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el formateador de respuestas con diferentes tipos de campos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Probando el formateador de respuestas...');

        // Buscar la primera zona para pruebas o crearla
        $zona = Zona::first();

        if (!$zona) {
            $this->info('No hay zonas. Creando una zona de prueba...');
            $zona = Zona::create([
                'nombre' => 'Zona de Prueba',
                'descripcion' => 'Zona para probar funcionalidades',
                'id_personalizado' => 'test-zone',
                'lineas_disponibles' => 100,
                'tipo_registro' => 'formulario',
            ]);
        }

        $this->info("Utilizando zona: {$zona->nombre}");

        // Verificar campos existentes y crear si no existen
        $this->verificarYCrearCampos($zona);

        // Prueba 1: Respuesta con campos simples
        $this->info("\nPrueba 1: Respuesta con campos simples");
        $this->probarRespuestaSimple($zona);

        // Prueba 2: Respuesta con checkboxes
        $this->info("\nPrueba 2: Respuesta con checkboxes");
        $this->probarRespuestaCheckbox($zona);

        // Prueba 3: Respuesta con estructura anidada
        $this->info("\nPrueba 3: Respuesta con estructura anidada");
        $this->probarRespuestaAnidada($zona);

        $this->info("\nPruebas completadas exitosamente.");

        return 0;
    }

    /**
     * Verificar que existan los campos necesarios y crearlos si no
     */
    protected function verificarYCrearCampos(Zona $zona)
    {
        // Verificar si ya existen campos
        $camposExistentes = $zona->campos->count();
        $this->info("Campos existentes: $camposExistentes");

        if ($camposExistentes > 0) {
            return;
        }

        // Crear campos de prueba

        // Campo nombre (texto)
        FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'nombre',
            'etiqueta' => 'Nombre completo',
            'tipo' => 'text',
            'obligatorio' => true,
            'orden' => 1
        ]);

        // Campo email
        FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'email',
            'etiqueta' => 'Correo electrónico',
            'tipo' => 'email',
            'obligatorio' => true,
            'orden' => 2
        ]);

        // Campo género (radio)
        $genero = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'genero',
            'etiqueta' => 'Género',
            'tipo' => 'radio',
            'obligatorio' => false,
            'orden' => 3
        ]);

        // Opciones de género
        FormFieldOption::create([
            'form_field_id' => $genero->id,
            'valor' => 'masculino',
            'etiqueta' => 'Masculino',
            'orden' => 1
        ]);

        FormFieldOption::create([
            'form_field_id' => $genero->id,
            'valor' => 'femenino',
            'etiqueta' => 'Femenino',
            'orden' => 2
        ]);

        // Campo intereses (checkbox)
        $intereses = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'deportes',
            'etiqueta' => 'Deportes',
            'tipo' => 'checkbox',
            'obligatorio' => false,
            'orden' => 4
        ]);

        $musica = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'musica',
            'etiqueta' => 'Música',
            'tipo' => 'checkbox',
            'obligatorio' => false,
            'orden' => 5
        ]);

        $tecnologia = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'tecnologia',
            'etiqueta' => 'Tecnología',
            'tipo' => 'checkbox',
            'obligatorio' => false,
            'orden' => 6
        ]);

        // Campo fuente (select)
        $fuente = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'como_nos_conocio',
            'etiqueta' => '¿Cómo nos conociste?',
            'tipo' => 'select',
            'obligatorio' => false,
            'orden' => 7
        ]);

        // Opciones de fuente
        FormFieldOption::create([
            'form_field_id' => $fuente->id,
            'valor' => 'amigo',
            'etiqueta' => 'Por un amigo',
            'orden' => 1
        ]);

        FormFieldOption::create([
            'form_field_id' => $fuente->id,
            'valor' => 'busqueda',
            'etiqueta' => 'Búsqueda en internet',
            'orden' => 2
        ]);

        FormFieldOption::create([
            'form_field_id' => $fuente->id,
            'valor' => 'redes',
            'etiqueta' => 'Redes sociales',
            'orden' => 3
        ]);

        $this->info("Campos de prueba creados.");
    }

    /**
     * Probar respuesta con campos simples
     */
    protected function probarRespuestaSimple(Zona $zona)
    {
        $respuesta = FormResponse::create([
            'zona_id' => $zona->id,
            'mac_address' => $this->generarMacAleatoria(),
            'dispositivo' => 'Test Simple',
            'navegador' => 'Test Browser',
            'tiempo_activo' => 60,
            'formulario_completado' => true,
            'respuestas' => [
                'nombre' => 'Usuario de Prueba',
                'email' => 'test@example.com',
                'genero' => 'masculino',
                'como_nos_conocio' => 'amigo'
            ]
        ]);

        $this->mostrarRespuestaFormateada($respuesta);
    }

    /**
     * Probar respuesta con checkboxes
     */
    protected function probarRespuestaCheckbox(Zona $zona)
    {
        $respuesta = FormResponse::create([
            'zona_id' => $zona->id,
            'mac_address' => $this->generarMacAleatoria(),
            'dispositivo' => 'Test Checkbox',
            'navegador' => 'Test Browser',
            'tiempo_activo' => 120,
            'formulario_completado' => true,
            'respuestas' => [
                'nombre' => 'Usuario Checkbox',
                'email' => 'checkbox@example.com',
                'deportes' => '1',
                'musica' => '1',
                'tecnologia' => '0'
            ]
        ]);

        $this->mostrarRespuestaFormateada($respuesta);
    }

    /**
     * Probar respuesta con estructura anidada
     */
    protected function probarRespuestaAnidada(Zona $zona)
    {
        $respuesta = FormResponse::create([
            'zona_id' => $zona->id,
            'mac_address' => $this->generarMacAleatoria(),
            'dispositivo' => 'Test Anidado',
            'navegador' => 'Test Browser',
            'tiempo_activo' => 180,
            'formulario_completado' => true,
            'respuestas' => [
                'nombre' => 'Usuario Anidado',
                'email' => 'anidado@example.com',
                'intereses' => [
                    'deportes' => '1',
                    'musica' => '1',
                    'tecnologia' => '0'
                ],
                'como_nos_conocio' => 'redes'
            ]
        ]);

        $this->mostrarRespuestaFormateada($respuesta);
    }

    /**
     * Mostrar respuesta formateada
     */
    protected function mostrarRespuestaFormateada(FormResponse $respuesta)
    {
        $this->info("Respuesta ID: {$respuesta->id}");
        $this->info("MAC: {$respuesta->mac_address}");

        $this->line("\nJSON original:");
        $this->line(json_encode($respuesta->respuestas, JSON_PRETTY_PRINT));

        $this->line("\nRespuestas formateadas:");
        $formateadas = $respuesta->respuestas_formateadas;

        $headers = ['Campo', 'Valor'];
        $rows = [];

        foreach ($formateadas as $campo) {
            $rows[] = [
                $campo['etiqueta'],
                $campo['valor']
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Generar MAC aleatoria
     */
    protected function generarMacAleatoria()
    {
        $mac = [];
        for ($i = 0; $i < 6; $i++) {
            $mac[] = sprintf('%02X', mt_rand(0, 255));
        }

        return implode(':', $mac);
    }
}
