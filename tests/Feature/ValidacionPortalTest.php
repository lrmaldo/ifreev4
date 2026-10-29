<?php

namespace Tests\Feature;

use App\Livewire\Admin\FormFieldOptions;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\User;
use App\Models\Zona;
use App\Services\PortalToken;
use App\Services\ValidacionPortal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ValidacionPortalTest extends TestCase
{
    use RefreshDatabase;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ifree.validar_correo_dns' => false]);

        $this->zona = Zona::create([
            'nombre' => 'EXPO MX-ISP 2026', 'user_id' => User::factory()->create()->id, 'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15,
        ]);
        foreach ([
            ['nombre', 'Nombre', 'text', true],
            ['empresa', 'Empresa', 'text', false],
            ['telefono', 'Teléfono', 'tel', true],
            ['correo', 'Correo', 'email', true],
        ] as $orden => [$campo, $etiqueta, $tipo, $obligatorio]) {
            FormField::create(['zona_id' => $this->zona->id, 'campo' => $campo, 'etiqueta' => $etiqueta, 'tipo' => $tipo, 'obligatorio' => $obligatorio, 'orden' => $orden]);
        }

        $estado = FormField::create(['zona_id' => $this->zona->id, 'campo' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'select', 'obligatorio' => false, 'orden' => 9]);
        Livewire::actingAs(User::factory()->create())->test(FormFieldOptions::class, ['formField' => $estado])->call('cargarEstados');
    }

    public function test_cargar_estados_agrega_los_32_sin_repetir()
    {
        $estado = FormField::where('campo', 'estado')->first();
        $this->assertSame(33, $estado->opciones()->count()); // 32 + "Fuera de México"

        Livewire::test(FormFieldOptions::class, ['formField' => $estado])
            ->call('cargarEstados')
            ->assertDispatched('notify');
        $this->assertSame(33, $estado->opciones()->count());

        $this->registrar(['estado' => 'Ciudad de México'])->assertOk();
        $this->assertSame('Ciudad de México', FormResponse::first()->respuestas['estado']);
    }

    private function registrar(array $cambios)
    {
        return $this->withoutDefer()->postJson('/zona/formulario/responder', [
            'respuestas' => array_merge([
                'nombre' => 'Ana López', 'empresa' => '', 'telefono' => '5512345678', 'correo' => 'ana@gmail.com',
            ], $cambios),
            'acepta_privacidad' => 1,
        ], ['X-Portal-Token' => PortalToken::generar($this->zona->id, 'AA:BB:CC:DD:EE:01')]);
    }

    public function test_guarda_los_datos_limpios()
    {
        $this->registrar([
            'nombre' => '  PEDRO   PÉREZ ', 'telefono' => '+52 (55) 1234-5678', 'correo' => ' Pedro.Perez@Hotmail.com',
        ])->assertOk();

        $this->assertSame([
            'nombre' => 'Pedro Pérez', 'empresa' => null, 'telefono' => '5512345678', 'correo' => 'pedro.perez@hotmail.com',
        ], FormResponse::first()->respuestas);
    }

    /** @dataProvider datosInvalidos */
    public function test_rechaza_datos_que_no_sirven(string $campo, string $valor, string $mensaje)
    {
        $this->registrar([$campo => $valor])
            ->assertStatus(422)
            ->assertJsonPath('message', $mensaje)
            ->assertJsonValidationErrors(["respuestas.{$campo}"]);

        $this->assertDatabaseCount('form_responses', 0);
    }

    public static function datosInvalidos(): array
    {
        return [
            'teléfono corto' => ['telefono', '551234', ValidacionPortal::MENSAJE_TELEFONO],
            'teléfono de relleno' => ['telefono', '5555555555', ValidacionPortal::MENSAJE_TELEFONO],
            'teléfono con 0' => ['telefono', '0123456789', ValidacionPortal::MENSAJE_TELEFONO],
            'correo sin dominio' => ['correo', 'ana@gmail', ValidacionPortal::MENSAJE_CORREO],
            'correo con error de dedo' => ['correo', 'ana@gmial.com', '¿Quisiste decir ana@gmail.com?'],
            'hotmail mal escrito' => ['correo', 'ana@hotmail.con', '¿Quisiste decir ana@hotmail.com?'],
            'solo nombre' => ['nombre', 'Ana', ValidacionPortal::MENSAJE_NOMBRE],
            'nombre con números' => ['nombre', 'Ana 123', ValidacionPortal::MENSAJE_NOMBRE],
            'nombre de relleno' => ['nombre', 'aaa aaa', ValidacionPortal::MENSAJE_NOMBRE],
            'el "Mmmm" de la Expo' => ['nombre', 'Mmmm', ValidacionPortal::MENSAJE_NOMBRE],
            'estado inventado' => ['estado', 'Narnia', 'Elige una opción de la lista en estado.'],
            'falta el teléfono' => ['telefono', '', 'Falta tu teléfono.'],
        ];
    }

    public function test_acepta_nombres_y_correos_reales()
    {
        $this->registrar(['nombre' => "José de la O'Brien-Núñez", 'correo' => 'ventas@sattlink.com.mx'])->assertOk();
        $this->registrar(['telefono' => '5215512345678'])->assertOk(); // con el viejo 521

        $this->assertSame('5512345678', FormResponse::latest('id')->first()->respuestas['telefono']);
    }

    public function test_el_dominio_que_no_existe_se_rechaza_con_dns()
    {
        config(['ifree.validar_correo_dns' => true]);
        $this->mock(ValidacionPortal::class, function ($mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()
                ->shouldReceive('dominioRecibeCorreo')->with('noexiste-ifree.mx')->andReturnFalse();
        });

        $this->registrar(['correo' => 'ana@noexiste-ifree.mx'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El dominio noexiste-ifree.mx no recibe correos. Revisa que esté bien escrito.');
    }

    public function test_el_formulario_marca_el_formato_en_el_html()
    {
        $html = $this->post('/login_formulario/' . $this->zona->id, ['mac' => 'AA:BB:CC:DD:EE:09'])->assertOk()->getContent();

        $this->assertStringContainsString('inputmode="numeric"', $html);
        $this->assertStringContainsString('pattern="[2-9][0-9]{9}"', $html);
        $this->assertStringContainsString('autocomplete="name"', $html);
        $this->assertStringContainsString('data-error="' . ValidacionPortal::MENSAJE_NOMBRE . '"', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);
    }
}
