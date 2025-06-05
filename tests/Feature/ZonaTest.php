<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Zona;

class ZonaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba que podemos crear una zona con tipo_autenticacion_mikrotik 'sin_autenticacion'.
     */
    public function test_puede_crear_zona_sin_autenticacion()
    {
        // Crear un usuario para asociar con la zona
        $user = User::factory()->create();

        // Crear una zona con tipo_autenticacion_mikrotik = 'sin_autenticacion'
        $zona = Zona::create([
            'nombre' => 'Zona de Prueba',
            'user_id' => $user->id,
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'tipo_registro' => 'sin_registro',
            'segundos' => 30,
            'login_sin_registro' => true,
            'seleccion_campanas' => 'aleatorio',
            'tiempo_visualizacion' => 20
        ]);

        // Refrescar el modelo desde la base de datos
        $zona->refresh();

        // Verificar que el valor de tipo_autenticacion_mikrotik sea 'sin_autenticacion'
        $this->assertEquals('sin_autenticacion', $zona->tipo_autenticacion_mikrotik);

        // Verificar que el getter para requireAutenticacionMikrotik devuelva false
        $this->assertFalse($zona->requiere_autenticacion_mikrotik);
    }
}
