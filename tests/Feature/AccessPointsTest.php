<?php

namespace Tests\Feature;

use App\Livewire\Admin\Aps\Index as AdminAps;
use App\Livewire\Evento\PantallaEnVivo;
use App\Models\AccessPoint;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\User;
use App\Models\Zona;
use App\Services\AccessPointsService;
use App\Services\AlertasEventoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessPointsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Zona $zona;

    /** Lo que "responde" el AP en cada lectura; una excepción simula que no contesta */
    private array|RuntimeException $respuesta;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->zona = Zona::create([
            'nombre' => 'Expo MX-ISP', 'user_id' => $this->admin->id, 'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15,
        ]);

        $this->respuesta = $this->lectura(2, ['AA:AA:AA:AA:AA:01', 'AA:AA:AA:AA:AA:02']);
        $this->partialMock(AccessPointsService::class, function ($mock) {
            $mock->shouldReceive('leer')->andReturnUsing(fn () => $this->respuesta instanceof RuntimeException ? throw $this->respuesta : $this->respuesta);
        });
    }

    private function lectura(int $clientes, array $macs = [], int $entrada = 1000, int $salida = 500, int $uptime = 600): array
    {
        return ['modelo' => 'EAP660 HD', 'uptime_seg' => $uptime, 'clientes' => $clientes, 'macs' => $macs,
            'contadores' => ['entrada' => $entrada, 'salida' => $salida]];
    }

    private function crearAp(array $datos = []): AccessPoint
    {
        return AccessPoint::create($datos + ['nombre' => 'GYM', 'host' => '192.168.241.108', 'puerto' => 16146, 'community' => 'public', 'zona_id' => $this->zona->id]);
    }

    public function test_velocidades_entre_lecturas_con_vuelta_del_contador_y_reinicio()
    {
        $servicio = new AccessPointsService;
        $antes = ['entrada' => 4294967000, 'salida' => 1000, 'uptime' => 600, 't' => 1000];

        // 60 s después: la entrada dio la vuelta (296 + 704 bytes), la salida subió 7500 bytes
        [$bajada, $subida] = $servicio->velocidades($antes, ['entrada' => 704, 'salida' => 8500], 660, 1060);
        $this->assertSame((int) round(1000 * 8 / 60), $bajada);
        $this->assertSame(1000, $subida);

        // Si el AP se reinició, no hay velocidad válida
        $this->assertSame([null, null], $servicio->velocidades($antes, ['entrada' => 10, 'salida' => 10], 30, 1060));
        // Sin lectura anterior tampoco
        $this->assertSame([null, null], $servicio->velocidades(null, ['entrada' => 10, 'salida' => 10], 30, 1060));
    }

    public function test_el_sondeo_guarda_la_lectura_y_el_historial()
    {
        $ap = $this->crearAp();

        $this->artisan('aps:sondear')->expectsOutput('GYM: 2 clientes')->assertSuccessful();

        $ap->refresh();
        $this->assertTrue($ap->en_linea);
        $this->assertSame(2, $ap->clientes);
        $this->assertSame(['AA:AA:AA:AA:AA:01', 'AA:AA:AA:AA:AA:02'], $ap->macs);
        $this->assertSame(1, $ap->lecturas()->count());

        // Segunda lectura un minuto después: ya calcula velocidad
        $this->travel(60)->seconds();
        $this->respuesta = $this->lectura(3, [], 1000 + 75000, 500 + 15000, 660);
        $this->artisan('aps:sondear')->assertSuccessful();

        $ap->refresh();
        $this->assertSame(10000, $ap->bajada_bps);
        $this->assertSame(2000, $ap->subida_bps);
    }

    public function test_un_ap_que_no_responde_queda_fuera_de_linea_y_los_pausados_no_se_leen()
    {
        $ap = $this->crearAp();
        $pausado = $this->crearAp(['nombre' => 'Bodega', 'activo' => false]);
        $this->respuesta = new RuntimeException('No responde (revisa el AP, el NAT del MikroTik o la community).');

        $this->artisan('aps:sondear')->assertSuccessful();

        $this->assertFalse($ap->refresh()->en_linea);
        $this->assertStringContainsString('No responde', $ap->ultimo_error);
        $this->assertNull($pausado->refresh()->ultimo_sondeo_at);
    }

    public function test_alerta_cuando_el_ap_no_responde_dos_veces_y_cuando_vuelve()
    {
        config(['ifree.portal_url' => 'https://portal.test']);
        Http::fake(['portal.test/*' => Http::response(['ok' => true])]);
        $ap = $this->crearAp();
        $alertas = app(AlertasEventoService::class);
        $this->respuesta = new RuntimeException('No responde.');

        app(AccessPointsService::class)->sondear($ap);
        $this->assertSame([], $alertas->revisar());

        app(AccessPointsService::class)->sondear($ap);
        $mensajes = $alertas->revisar();
        $this->assertCount(1, $mensajes);
        $this->assertStringContainsString('AP GYM no responde', $mensajes[0]);

        $this->respuesta = $this->lectura(4);
        app(AccessPointsService::class)->sondear($ap);
        $mensajes = $alertas->revisar();
        $this->assertCount(1, $mensajes);
        $this->assertStringContainsString('volvió a responder (4 clientes)', $mensajes[0]);
    }

    public function test_alerta_cuando_un_ap_pasa_el_limite_de_clientes()
    {
        config(['ifree.portal_url' => 'https://portal.test', 'ifree.alertas.ap_max_clientes' => 50]);
        Http::fake(['portal.test/*' => Http::response(['ok' => true])]);
        $ap = $this->crearAp();
        $this->respuesta = $this->lectura(60);

        app(AccessPointsService::class)->sondear($ap);
        app(AlertasEventoService::class)->revisar();
        app(AccessPointsService::class)->sondear($ap);

        $this->assertStringContainsString('AP GYM con 60 clientes', implode("\n", app(AlertasEventoService::class)->revisar()));
    }

    public function test_cruce_de_macs_con_el_portal()
    {
        $ap = $this->crearAp();
        app(AccessPointsService::class)->sondear($ap);
        HotspotMetric::create(['zona_id' => $this->zona->id, 'mac_address' => 'AA:AA:AA:AA:AA:01', 'dispositivo' => 'x', 'navegador' => 'x', 'tipo_visual' => 'formulario', 'veces_entradas' => 1]);
        HotspotMetric::create(['zona_id' => $this->zona->id, 'mac_address' => 'BB:BB:BB:BB:BB:BB', 'dispositivo' => 'x', 'navegador' => 'x', 'tipo_visual' => 'formulario', 'veces_entradas' => 1]);
        FormResponse::create(['zona_id' => $this->zona->id, 'mac_address' => 'AA:AA:AA:AA:AA:01', 'respuestas' => ['nombre' => 'Ana'], 'formulario_completado' => true]);

        $this->assertSame(['portal' => 1, 'registrados' => 1], app(AccessPointsService::class)->cruceConPortal($ap->refresh()));
    }

    public function test_admin_agrega_un_ap_y_ve_sus_clientes()
    {
        Livewire::actingAs($this->admin)->test(AdminAps::class)
            ->call('nuevo')
            ->set('nombre', 'Entrada Cirion')
            ->set('host', '192.168.241.108')
            ->set('puerto', 16133)
            ->set('zona_id', $this->zona->id)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Entrada Cirion')
            ->assertSee('En línea');

        $this->assertSame(2, AccessPoint::firstWhere('nombre', 'Entrada Cirion')->clientes);
    }

    public function test_validacion_del_host_y_acceso_solo_admin()
    {
        Livewire::actingAs($this->admin)->test(AdminAps::class)
            ->call('nuevo')
            ->set('nombre', 'X')
            ->set('host', '1.2.3.4; rm -rf /')
            ->call('guardar')
            ->assertHasErrors(['host']);

        $this->actingAs(User::factory()->create())->get(route('admin.aps.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.aps.index'))->assertOk();
    }

    public function test_la_pantalla_en_vivo_muestra_clientes_por_ap()
    {
        $token = str_repeat('a', 48);
        $this->zona->forceFill(['pantalla_token' => $token])->save();
        app(AccessPointsService::class)->sondear($this->crearAp());

        Livewire::test(PantallaEnVivo::class, ['token' => $token])
            ->assertSee('Clientes WiFi por access point', false)
            ->assertSee('GYM');
    }
}
