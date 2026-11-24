<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\SolicitudDemo;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CasoEventoTest extends TestCase
{
    use RefreshDatabase;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ifree.validar_correo_dns' => false,
            'telegram.bots.ifree.token' => '123:abc',
            'ifree.alertas.chats' => '-100777',
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->zona = Zona::create([
            'nombre' => 'EXPO MX-ISP 2026', 'user_id' => User::factory()->create()->id, 'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15,
        ]);
        config(['ifree.casos.expo-mx-isp-2026.zona_id' => $this->zona->id]);

        foreach (['nombre' => 'text', 'empresa' => 'text', 'telefono' => 'tel', 'estado' => 'select'] as $campo => $tipo) {
            FormField::create(['zona_id' => $this->zona->id, 'campo' => $campo, 'etiqueta' => ucfirst($campo), 'tipo' => $tipo, 'obligatorio' => false, 'orden' => 0]);
        }

        $dia = Carbon::parse('2026-10-30 13:15:00');
        $registros = [
            ['Ana López', 'Wisp Norte', 'Nuevo León'], ['Beto Ruiz', 'Wisp Norte', 'Nuevo León'], ['Caro Díaz', '', 'Nuevo León'],
            ['Dani Paz', 'Red Sur', 'Ciudad de México'], ['Eva Sol', '', 'Ciudad de México'], ['Fer Gil', 'Fibra MX', 'Ciudad de México'],
            ['Gus Rey', 'Tacos Yucatán', 'Yucatán'], // estado con un solo registro: no debe salir con su nombre
        ];
        foreach ($registros as $i => [$nombre, $empresa, $estado]) {
            $this->travelTo($dia->copy()->addMinutes($i * 7));
            $r = FormResponse::create(['zona_id' => $this->zona->id, 'mac_address' => "AA:0{$i}", 'respuestas' => [
                'nombre' => $nombre, 'empresa' => $empresa, 'telefono' => '55123456' . $i . $i, 'estado' => $estado,
            ]]);
            HotspotMetric::create(['zona_id' => $this->zona->id, 'mac_address' => "AA:0{$i}", 'formulario_id' => $r->id, 'dispositivo' => 'x', 'navegador' => 'x', 'sistema_operativo' => $i % 2 ? 'iOS 18' : 'Android 15', 'tipo_visual' => 'carrusel', 'veces_entradas' => 1]);
        }
        $this->travelBack();

        // Fuera de las fechas del evento: no cuenta
        FormResponse::create(['zona_id' => $this->zona->id, 'mac_address' => 'ZZ', 'respuestas' => ['nombre' => 'Prueba Oficina'], 'created_at' => '2026-10-20 10:00:00']);
    }

    public function test_muestra_los_resultados_sin_datos_personales()
    {
        $html = $this->get('/casos/expo-mx-isp-2026')->assertOk()
            ->assertSee('personas se conectaron')
            ->assertSee('data-contar="7"', false)       // 7 registros del evento (no el de oficina)
            ->assertSee('Nuevo León')->assertSee('Ciudad de México')
            ->assertSee('La hora pico fue a las <b>13:00</b>', false)
            ->getContent();

        foreach (['Ana López', 'Wisp Norte', '5512345600', 'Yucatán', 'Prueba Oficina'] as $privado) {
            $this->assertStringNotContainsString($privado, $html);
        }
    }

    public function test_un_evento_que_no_existe_da_404()
    {
        $this->get('/casos/otro-evento')->assertNotFound();
    }

    public function test_solicitar_demo_guarda_y_avisa_por_telegram()
    {
        $this->withoutDefer();
        $this->post('/casos/expo-mx-isp-2026/demo', [
            'nombre' => 'rafa gómez', 'empresa' => 'Konecta', 'telefono' => '+52 55 1234 5678',
            'correo' => 'rafa@gmail.com', 'interes' => 'isp',
        ])->assertRedirect(route('casos.show', 'expo-mx-isp-2026') . '#demo');

        $this->assertDatabaseHas('solicitudes_demo', ['nombre' => 'Rafa Gómez', 'telefono' => '5512345678', 'origen' => 'expo-mx-isp-2026']);
        Http::assertSent(fn (Request $r) => $r['chat_id'] === '-100777' && str_contains($r['text'], 'Solicitud de demo') && str_contains($r['text'], 'Konecta'));
    }

    public function test_la_solicitud_valida_como_el_portal()
    {
        $this->post('/casos/expo-mx-isp-2026/demo', ['nombre' => 'Mmmm', 'telefono' => '123', 'correo' => 'x@gmial.com'])
            ->assertSessionHasErrors(['nombre', 'telefono', 'correo', 'empresa', 'interes']);

        $this->assertDatabaseCount('solicitudes_demo', 0);
    }

    public function test_los_bots_no_dejan_solicitudes()
    {
        $this->post('/casos/expo-mx-isp-2026/demo', [
            'nombre' => 'Bot Malo', 'empresa' => 'Spam', 'telefono' => '5512345678', 'correo' => 'bot@gmail.com',
            'interes' => 'otro', 'sitio_web' => 'http://spam.test',
        ])->assertRedirect();

        $this->assertSame(0, SolicitudDemo::count());
    }
}
