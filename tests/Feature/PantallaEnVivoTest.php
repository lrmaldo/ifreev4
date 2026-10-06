<?php

namespace Tests\Feature;

use App\Livewire\Admin\Zonas\Index as AdminZonas;
use App\Livewire\Evento\PantallaEnVivo;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\RifaGanador;
use App\Models\User;
use App\Models\Zona;
use App\Services\EventoEnVivoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PantallaEnVivoTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUV';

    private function crearZona(): Zona
    {
        $zona = Zona::create([
            'nombre' => 'Expo MX-ISP',
            'user_id' => User::factory()->create()->id,
            'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
        ]);
        $zona->forceFill(['pantalla_token' => self::TOKEN])->save();
        $zona->campos()->create(['campo' => 'nombre', 'etiqueta' => 'Nombre completo', 'tipo' => 'text', 'obligatorio' => true, 'orden' => 1]);
        $zona->campos()->create(['campo' => 'celular', 'etiqueta' => 'Celular', 'tipo' => 'tel', 'obligatorio' => true, 'orden' => 2]);

        return $zona;
    }

    private function registro(Zona $zona, string $nombre, ?string $telefono): FormResponse
    {
        return FormResponse::create([
            'zona_id' => $zona->id,
            'mac_address' => uniqid('mac'),
            'respuestas' => array_filter(['nombre' => $nombre, 'celular' => $telefono]),
        ]);
    }

    private function metrica(Zona $zona, string $so, $creada = null): HotspotMetric
    {
        $m = HotspotMetric::create(['zona_id' => $zona->id, 'mac_address' => uniqid('m'), 'dispositivo' => 'x', 'navegador' => 'x', 'sistema_operativo' => $so, 'tipo_visual' => 'carrusel']);
        if ($creada) {
            $m->forceFill(['created_at' => $creada, 'updated_at' => $creada])->saveQuietly();
        }

        return $m;
    }

    public function test_la_pantalla_abre_con_el_link_secreto_y_no_sin_el()
    {
        $this->crearZona();

        $this->get('/pantalla/' . self::TOKEN)->assertOk()->assertSee('Expo MX-ISP')->assertSee('Red WiFi oficial');
        $this->get('/pantalla/' . str_repeat('x', 48))->assertNotFound();
    }

    public function test_la_pantalla_muestra_la_marca_del_evento_y_el_carrusel_de_socios()
    {
        $this->crearZona();
        $socios = json_decode(file_get_contents(public_path('img/evento/socios/socios.json')), true);

        $respuesta = $this->get('/pantalla/' . self::TOKEN)->assertOk()
            ->assertSee('logo-wispmx.webp')
            ->assertSee('pev-marca-mxisp')
            ->assertSee('Socios WISPMX')
            ->assertSee('alt="Sattlink"', false);

        // Cada logo del manifiesto existe en disco y aparece dos veces (cinta infinita)
        foreach ($socios as $socio) {
            $this->assertFileExists(public_path('img/evento/socios/' . $socio['archivo']));
            $this->assertEquals(2, substr_count($respuesta->getContent(), 'img/evento/socios/' . $socio['archivo']));
        }
    }

    public function test_estadisticas_en_vivo()
    {
        $this->travelTo(now()->setTime(12, 30));
        $zona = $this->crearZona();
        $this->metrica($zona, 'Android 14');
        $this->metrica($zona, 'iOS 18');
        $this->metrica($zona, 'Android 13', now()->setTime(10, 15));
        $this->metrica($zona, 'Windows', now()->setTime(10, 40));
        $this->registro($zona, 'Ana García', '5512345678');

        $stats = app(EventoEnVivoService::class)->estadisticas($zona);

        $this->assertEquals(2, $stats['conectados']); // los de las 10:xx ya no cuentan como activos
        $this->assertEquals(4, $stats['dispositivosHoy']);
        $this->assertEquals(1, $stats['registrosHoy']);
        $this->assertEquals(['android' => 2, 'iphone' => 1, 'otros' => 1], $stats['plataformas']);
        $this->assertEquals([
            ['hora' => '10:00', 'total' => 2],
            ['hora' => '11:00', 'total' => 0],
            ['hora' => '12:00', 'total' => 2],
        ], $stats['llegadasPorHora']);
        $this->assertEquals('Ana G.', $stats['ultimosRegistros'][0]['nombre']);
    }

    public function test_la_pantalla_nunca_muestra_telefonos_completos()
    {
        $zona = $this->crearZona();
        $this->registro($zona, 'Ana García López', '55 1234 5678');

        $this->get('/pantalla/' . self::TOKEN)
            ->assertOk()
            ->assertSee('Ana G.')
            ->assertDontSee('García')
            ->assertDontSee('5678');
    }

    public function test_la_rifa_solo_incluye_registros_con_telefono_y_no_repite_ganadores()
    {
        $zona = $this->crearZona();
        $conTel = $this->registro($zona, 'Luis Pérez', '+52 (55) 8765-4321');
        $this->registro($zona, 'Sin Teléfono', null);
        $this->registro($zona, 'Tel Corto', '12');

        $componente = Livewire::test(PantallaEnVivo::class, ['token' => self::TOKEN]);
        $this->assertEquals(1, $componente->instance()->contarParticipantes());

        $resultado = $componente->instance()->sortear('Router WiFi 6');
        $this->assertEquals(['nombre' => 'Luis P.', 'telefono_final' => '4321', 'premio' => 'Router WiFi 6'], $resultado['ganador']);
        $this->assertDatabaseHas('rifa_ganadores', ['form_response_id' => $conTel->id, 'telefono_final' => '4321']);

        // Ya ganó: no hay más participantes
        $this->assertNull($componente->instance()->sortear()['ganador']);
        $this->assertEquals(1, RifaGanador::count());
    }

    public function test_regenerar_el_link_desactiva_las_pantallas_abiertas()
    {
        $zona = $this->crearZona();
        $componente = Livewire::test(PantallaEnVivo::class, ['token' => self::TOKEN]);

        $zona->forceFill(['pantalla_token' => str_repeat('n', 48)])->save();

        $componente->call('actualizar')->assertForbidden();
    }

    public function test_el_admin_genera_el_link_de_la_pantalla()
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $zona = $this->crearZona();
        $anterior = $zona->pantalla_token;

        Livewire::actingAs($admin)->test(AdminZonas::class)->call('generarLinkPantalla', $zona->id);

        $nuevo = $zona->fresh()->pantalla_token;
        $this->assertNotEquals($anterior, $nuevo);
        $this->assertEquals(48, strlen($nuevo));
    }

    public function test_un_usuario_no_genera_links_de_zonas_ajenas()
    {
        $zona = $this->crearZona();

        Livewire::actingAs(User::factory()->create())
            ->test(AdminZonas::class)
            ->call('generarLinkPantalla', $zona->id)
            ->assertForbidden();
    }

    public function test_nombre_corto()
    {
        $s = new EventoEnVivoService();

        $this->assertEquals('Ana G.', $s->nombreCorto('ana garcía'));
        $this->assertEquals('Ana G.', $s->nombreCorto('ANA GARCÍA LÓPEZ'));
        $this->assertEquals('Ana G.', $s->nombreCorto('Ana María García López'));
        $this->assertEquals('Luis', $s->nombreCorto('  luis  '));
        $this->assertEquals('Invitado', $s->nombreCorto(''));
        $this->assertEquals('Invitado', $s->nombreCorto(null));
    }
}
