<?php

namespace Database\Seeders;

use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Zona;
use Illuminate\Database\Seeder;

class FormFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener una zona existente o crear una de prueba si no existe ninguna
        $zona = Zona::first();        if (!$zona) {
            $this->command->info('No se encontró ninguna zona, creando una zona de prueba...');

            try {
                // Intentemos usar un método alternativo de creación
                $zona = new Zona();
                $zona->nombre = 'Zona de Prueba';
                $zona->user_id = 1; // Asegúrate de que este usuario exista
                $zona->tipo_registro = 'formulario';
                $zona->segundos = 15;
                $zona->login_sin_registro = true;
                $zona->tipo_autenticacion_mikrotik = 'pin';
                $zona->save();

                // Refrescar para asegurar que tenemos el ID
                $zona->refresh();

                $this->command->info('Zona creada con ID: ' . $zona->id);
            } catch (\Exception $e) {
                $this->command->error('Error al crear la zona: ' . $e->getMessage());
                return;
            }
        }

        $this->command->info('Creando campos de formulario para la zona: ' . $zona->nombre);

        // Crear campos de formulario
        $campos = [
            [
                'campo' => 'nombre',
                'etiqueta' => 'Nombre',
                'tipo' => 'text',
                'obligatorio' => true,
                'orden' => 1
            ],
            [
                'campo' => 'email',
                'etiqueta' => 'Correo Electrónico',
                'tipo' => 'email',
                'obligatorio' => true,
                'orden' => 2
            ],
            [
                'campo' => 'telefono',
                'etiqueta' => 'Teléfono',
                'tipo' => 'tel',
                'obligatorio' => false,
                'orden' => 3
            ],
            [
                'campo' => 'edad',
                'etiqueta' => 'Edad',
                'tipo' => 'number',
                'obligatorio' => true,
                'orden' => 4
            ],
            [
                'campo' => 'genero',
                'etiqueta' => 'Género',
                'tipo' => 'select',
                'obligatorio' => true,
                'orden' => 5
            ],
            [
                'campo' => 'intereses',
                'etiqueta' => 'Intereses',
                'tipo' => 'checkbox',
                'obligatorio' => false,
                'orden' => 6
            ],
            [
                'campo' => 'como_nos_conocio',
                'etiqueta' => '¿Cómo nos conoció?',
                'tipo' => 'radio',
                'obligatorio' => false,
                'orden' => 7
            ],
            [
                'campo' => 'acepta_terminos',
                'etiqueta' => 'Acepto los términos y condiciones',
                'tipo' => 'checkbox',
                'obligatorio' => true,
                'orden' => 8
            ]
        ];

        foreach ($campos as $campo) {
            // Verificar si la zona tiene un ID
            if ($zona->id) {
                $campoCreado = FormField::create(array_merge($campo, ['zona_id' => $zona->id]));
                $this->command->info('Campo creado: ' . $campoCreado->etiqueta);
            } else {
                $this->command->error('Error: La zona no tiene un ID válido');
                return;
            }

            // Agregar opciones para los campos que las necesiten
            if ($campoCreado->tipo === 'select' && $campoCreado->campo === 'genero') {
                $opciones = [
                    ['valor' => 'masculino', 'etiqueta' => 'Masculino', 'orden' => 1],
                    ['valor' => 'femenino', 'etiqueta' => 'Femenino', 'orden' => 2],
                    ['valor' => 'no_especificado', 'etiqueta' => 'Prefiero no especificar', 'orden' => 3]
                ];

                foreach ($opciones as $opcion) {
                    FormFieldOption::create(array_merge($opcion, ['form_field_id' => $campoCreado->id]));
                }

                $this->command->info('Opciones agregadas para el campo: ' . $campoCreado->etiqueta);
            }

            if ($campoCreado->tipo === 'checkbox' && $campoCreado->campo === 'intereses') {
                $opciones = [
                    ['valor' => 'deportes', 'etiqueta' => 'Deportes', 'orden' => 1],
                    ['valor' => 'tecnologia', 'etiqueta' => 'Tecnología', 'orden' => 2],
                    ['valor' => 'musica', 'etiqueta' => 'Música', 'orden' => 3],
                    ['valor' => 'viajes', 'etiqueta' => 'Viajes', 'orden' => 4],
                    ['valor' => 'literatura', 'etiqueta' => 'Literatura', 'orden' => 5]
                ];

                foreach ($opciones as $opcion) {
                    FormFieldOption::create(array_merge($opcion, ['form_field_id' => $campoCreado->id]));
                }

                $this->command->info('Opciones agregadas para el campo: ' . $campoCreado->etiqueta);
            }

            if ($campoCreado->tipo === 'radio' && $campoCreado->campo === 'como_nos_conocio') {
                $opciones = [
                    ['valor' => 'amigo', 'etiqueta' => 'Por un amigo', 'orden' => 1],
                    ['valor' => 'redes_sociales', 'etiqueta' => 'Redes sociales', 'orden' => 2],
                    ['valor' => 'busqueda', 'etiqueta' => 'Buscador web', 'orden' => 3],
                    ['valor' => 'publicidad', 'etiqueta' => 'Publicidad', 'orden' => 4],
                    ['valor' => 'otro', 'etiqueta' => 'Otro', 'orden' => 5]
                ];

                foreach ($opciones as $opcion) {
                    FormFieldOption::create(array_merge($opcion, ['form_field_id' => $campoCreado->id]));
                }

                $this->command->info('Opciones agregadas para el campo: ' . $campoCreado->etiqueta);
            }
        }

        $this->command->info('Seeder de campos de formulario completado.');
    }
}
