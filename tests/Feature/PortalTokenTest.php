<?php

namespace Tests\Feature;

use App\Models\Campana;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\User;
use App\Models\Zona;
use App\Services\PortalToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalTokenTest extends TestCase
{
    use RefreshDatabase;

    private const MAC = 'AA:BB:CC:DD:EE:10';

    private function crearZona(): Zona
    {
        return Zona::create([
            'nombre' => 'Zona Token',
            'user_id' => User::factory()->create()->id,
            'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
        ]);
    }

    private function datosMetrica(Zona $zona, string $mac = self::MAC): array
    {
        return ['zona_id' => $zona->id, 'mac_address' => $mac, 'duracion_visual' => 5, 'tipo_visual' => 'carrusel'];
    }

    public function test_el_token_valido_se_acepta_y_uno_alterado_no()
    {
        $token = PortalToken::generar(7, self::MAC);

        $this->assertEquals(['zona_id' => 7, 'mac_address' => self::MAC], PortalToken::validar($token));
        $this->assertNull(PortalToken::validar($token . 'x'));
        $this->assertNull(PortalToken::validar('basura'));
        $this->assertNull(PortalToken::validar(null));
    }

    public function test_el_token_expirado_no_se_acepta()
    {
        $token = PortalToken::generar(7, self::MAC, 1);

        $this->travel(2)->minutes();

        $this->assertNull(PortalToken::validar($token));
    }

    public function test_el_portal_entrega_un_token_para_su_zona_y_mac()
    {
        $zona = $this->crearZona();

        $html = $this->post('/login_formulario/' . $zona->id, ['mac' => self::MAC])->assertOk()->getContent();

        preg_match('/window\.PORTAL_TOKEN = "([^"]+)"/', $html, $match);
        $this->assertEquals(['zona_id' => $zona->id, 'mac_address' => self::MAC], PortalToken::validar($match[1] ?? null));
    }

    public function test_endpoints_publicos_rechazan_peticiones_sin_token()
    {
        $zona = $this->crearZona();

        foreach (['/hotspot-metrics/update', '/hotspot-metrics/track', '/zona/formulario/responder', '/form-responses'] as $ruta) {
            $this->postJson($ruta, $this->datosMetrica($zona))->assertForbidden();
        }

        $this->assertDatabaseCount('hotspot_metrics', 0);
        $this->assertDatabaseCount('form_responses', 0);
    }

    public function test_actualizar_metrica_funciona_con_token_valido()
    {
        $zona = $this->crearZona();

        $this->postJson('/hotspot-metrics/update', $this->datosMetrica($zona), [
            'X-Portal-Token' => PortalToken::generar($zona->id, self::MAC),
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(5, HotspotMetric::where('mac_address', self::MAC)->value('duracion_visual'));
    }

    public function test_rechaza_token_de_otra_zona_o_de_otra_mac()
    {
        $zona = $this->crearZona();
        $otraZona = $this->crearZona();

        $this->postJson('/hotspot-metrics/update', $this->datosMetrica($zona), [
            'X-Portal-Token' => PortalToken::generar($otraZona->id, self::MAC),
        ])->assertForbidden();

        $this->postJson('/hotspot-metrics/update', $this->datosMetrica($zona, 'FF:FF:FF:FF:FF:FF'), [
            'X-Portal-Token' => PortalToken::generar($zona->id, self::MAC),
        ])->assertForbidden();

        $this->assertDatabaseCount('hotspot_metrics', 0);
    }

    public function test_el_formulario_usa_la_zona_y_mac_del_token()
    {
        $zona = $this->crearZona();

        // Aunque el cuerpo no mande zona ni MAC, se toman del token
        $this->postJson('/zona/formulario/responder', ['respuestas' => ['nombre' => 'Ana'], 'acepta_privacidad' => 1], [
            'X-Portal-Token' => PortalToken::generar($zona->id, self::MAC),
        ])->assertOk()->assertJson(['success' => true]);

        $respuesta = FormResponse::first();
        $this->assertEquals($zona->id, $respuesta->zona_id);
        $this->assertEquals(self::MAC, $respuesta->mac_address);
        $this->assertNotNull($respuesta->acepto_privacidad_at);
    }

    public function test_el_formulario_exige_aceptar_el_aviso_de_privacidad()
    {
        $zona = $this->crearZona();

        $this->postJson('/zona/formulario/responder', ['respuestas' => ['nombre' => 'Ana']], [
            'X-Portal-Token' => PortalToken::generar($zona->id, self::MAC),
        ])->assertStatus(422);

        $this->assertDatabaseCount('form_responses', 0);
    }

    public function test_el_portal_con_formulario_muestra_el_aviso_de_privacidad()
    {
        $zona = $this->crearZona();
        $zona->campos()->create(['campo' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'obligatorio' => true, 'orden' => 1]);

        $this->post('/login_formulario/' . $zona->id, ['mac' => self::MAC])
            ->assertOk()
            ->assertSee('id="acepta_privacidad" required', false)
            ->assertSee('Aviso de privacidad simplificado');
    }

    public function test_el_portal_no_escribe_en_la_tabla_de_sesiones()
    {
        config(['session.driver' => 'database']);
        app('session')->forgetDrivers();
        $this->app->forgetInstance('session.store');

        $zona = $this->crearZona();
        $zona->campanas()->attach(Campana::create([
            'titulo' => 'Promo',
            'fecha_inicio' => now()->subDay()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'visible' => true,
            'siempre_visible' => true,
            'tipo' => 'imagen',
            'archivo_path' => 'campanas/promo.jpg',
        ])->id);

        $this->post('/login_formulario/' . $zona->id, ['mac' => self::MAC])->assertOk()->assertSee('promo.jpg');

        $this->assertEquals(0, DB::table('sessions')->count());
    }
}
