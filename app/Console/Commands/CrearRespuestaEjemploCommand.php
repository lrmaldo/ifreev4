<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormResponse;
use App\Models\Zona;
use App\Models\FormField;
use App\Models\FormFieldOption;

class CrearRespuestaEjemploCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formulario:crear-ejemplo {zona_id? : ID de la zona donde crear el formulario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea una respuesta de formulario de ejemplo con múltiples tipos de campos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $zonaId = $this->argument('zona_id');

        if (!$zonaId) {
            // Buscar la primera zona disponible
            $zona = Zona::first();

            if (!$zona) {
                $this->error('No se encontraron zonas. Primero crea una zona.');
                return 1;
            }

            $zonaId = $zona->id;
        } else {
            // Verificar que la zona existe
            $zona = Zona::find($zonaId);

            if (!$zona) {
                $this->error("No se encontró la zona con ID: {$zonaId}");
                return 1;
            }
        }

        // Verificar que la zona tenga campos
        if ($zona->campos->isEmpty()) {
            // Crear campos de ejemplo si no hay
            $this->info('La zona no tiene campos. Creando campos de ejemplo...');
            $this->crearCamposEjemplo($zona);
        }

        // Crear respuesta de ejemplo
        $respuesta = FormResponse::create([
            'zona_id' => $zonaId,
            'mac_address' => $this->generarMacAleatoria(),
            'dispositivo' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'navegador' => 'Chrome 91.0.4472.124',
            'tiempo_activo' => rand(30, 300),
            'formulario_completado' => true,
            'respuestas' => $this->generarRespuestasEjemplo($zona),
        ]);

        $this->info('Respuesta de formulario de ejemplo creada con éxito:');
        $this->line('ID: ' . $respuesta->id);
        $this->line('Zona: ' . $zona->nombre);
        $this->line('MAC: ' . $respuesta->mac_address);
        $this->line('Respuestas: ' . json_encode($respuesta->respuestas, JSON_PRETTY_PRINT));

        return 0;
    }

    /**
     * Crear campos de ejemplo para el formulario
     */
    protected function crearCamposEjemplo(Zona $zona)
    {
        // Crear campo tipo texto: nombre
        $campoNombre = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'nombre',
            'etiqueta' => 'Nombre completo',
            'tipo' => 'text',
            'obligatorio' => true,
            'orden' => 1,
        ]);

        // Crear campo tipo email
        $campoEmail = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'email',
            'etiqueta' => 'Correo electrónico',
            'tipo' => 'email',
            'obligatorio' => true,
            'orden' => 2,
        ]);

        // Crear campo tipo teléfono
        $campoTelefono = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'telefono',
            'etiqueta' => 'Número de teléfono',
            'tipo' => 'tel',
            'obligatorio' => false,
            'orden' => 3,
        ]);

        // Crear campo tipo número: edad
        $campoEdad = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'edad',
            'etiqueta' => 'Edad',
            'tipo' => 'number',
            'obligatorio' => false,
            'orden' => 4,
        ]);

        // Crear campo tipo radio: género
        $campoGenero = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'genero',
            'etiqueta' => 'Género',
            'tipo' => 'radio',
            'obligatorio' => false,
            'orden' => 5,
        ]);

        // Agregar opciones al campo género
        FormFieldOption::create([
            'form_field_id' => $campoGenero->id,
            'valor' => 'masculino',
            'etiqueta' => 'Masculino',
            'orden' => 1,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoGenero->id,
            'valor' => 'femenino',
            'etiqueta' => 'Femenino',
            'orden' => 2,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoGenero->id,
            'valor' => 'otro',
            'etiqueta' => 'Otro',
            'orden' => 3,
        ]);

        // Crear campo tipo select: ¿Cómo nos conoció?
        $campoConocio = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'como_nos_conocio',
            'etiqueta' => '¿Cómo nos conociste?',
            'tipo' => 'select',
            'obligatorio' => false,
            'orden' => 6,
        ]);

        // Agregar opciones al campo ¿Cómo nos conoció?
        FormFieldOption::create([
            'form_field_id' => $campoConocio->id,
            'valor' => 'amigo',
            'etiqueta' => 'Por un amigo o familiar',
            'orden' => 1,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoConocio->id,
            'valor' => 'busqueda',
            'etiqueta' => 'A través de un buscador',
            'orden' => 2,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoConocio->id,
            'valor' => 'redes',
            'etiqueta' => 'Por redes sociales',
            'orden' => 3,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoConocio->id,
            'valor' => 'otro',
            'etiqueta' => 'Otro medio',
            'orden' => 4,
        ]);

        // Crear campo tipo checkbox: intereses
        $campoIntereses = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'intereses',
            'etiqueta' => 'Intereses',
            'tipo' => 'checkbox',
            'obligatorio' => false,
            'orden' => 7,
        ]);

        // Agregar opciones para el campo intereses
        FormFieldOption::create([
            'form_field_id' => $campoIntereses->id,
            'valor' => 'deportes',
            'etiqueta' => 'Deportes',
            'orden' => 1,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoIntereses->id,
            'valor' => 'musica',
            'etiqueta' => 'Música',
            'orden' => 2,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoIntereses->id,
            'valor' => 'tecnologia',
            'etiqueta' => 'Tecnología',
            'orden' => 3,
        ]);

        FormFieldOption::create([
            'form_field_id' => $campoIntereses->id,
            'valor' => 'lectura',
            'etiqueta' => 'Lectura',
            'orden' => 4,
        ]);

        // Crear campo tipo checkbox: acepta términos
        $campoTerminos = FormField::create([
            'zona_id' => $zona->id,
            'campo' => 'acepta_terminos',
            'etiqueta' => 'Acepto los términos y condiciones',
            'tipo' => 'checkbox',
            'obligatorio' => true,
            'orden' => 8,
        ]);

        $this->info('Se han creado 8 campos de ejemplo con sus opciones.');
    }

    /**
     * Generar respuestas de ejemplo
     */
    protected function generarRespuestasEjemplo(Zona $zona)
    {
        $respuestas = [
            'nombre' => 'Usuario de Ejemplo',
            'email' => 'ejemplo@mail.com',
            'telefono' => '9876543210',
            'edad' => '28',
            'genero' => 'masculino',
            'como_nos_conocio' => 'busqueda',
            'intereses' => [
                'deportes' => '1',
                'musica' => '1',
            ],
            'acepta_terminos' => '1',
        ];

        return $respuestas;
    }

    /**
     * Generar una dirección MAC aleatoria
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
