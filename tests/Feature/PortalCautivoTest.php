<?php

namespace Tests\Feature;

use App\Models\Campana;
use App\Models\HotspotMetric;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalCautivoTest extends TestCase
{
    use RefreshDatabase;

    private function crearZona(string $modo = 'aleatorio'): Zona
    {
        return Zona::create([
            'nombre' => 'Zona Portal',
            'user_id' => User::factory()->create()->id,
            'tipo_registro' => 'sin_registro',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
            'seleccion_campanas' => $modo,
        ]);
    }

    private function crearCampana(string $tipo, string $archivo): Campana
    {
        return Campana::create([
            'titulo' => "Campaña {$tipo}",
            'fecha_inicio' => now()->subDay()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'visible' => true,
            'siempre_visible' => true,
            'tipo' => $tipo,
            'archivo_path' => $archivo,
        ]);
    }

    public function test_el_portal_alterna_entre_video_e_imagen_segun_la_cookie()
    {
        $zona = $this->crearZona();
        $zona->campanas()->attach([
            $this->crearCampana('video', 'campanas/promo.mp4')->id,
            $this->crearCampana('imagen', 'campanas/promo.jpg')->id,
        ]);
        $cookie = 'ultimo_tipo_zona_' . $zona->id;

        $this->withUnencryptedCookie($cookie, 'video')
            ->post('/login_formulario/' . $zona->id, ['mac' => 'AA:BB:CC:DD:EE:01'])
            ->assertOk()
            ->assertSee('promo.jpg')
            ->assertCookie($cookie, 'imagen', false);

        $this->withUnencryptedCookie($cookie, 'imagen')
            ->post('/login_formulario/' . $zona->id, ['mac' => 'AA:BB:CC:DD:EE:01'])
            ->assertOk()
            ->assertSee('promo.mp4')
            ->assertCookie($cookie, 'video', false);
    }

    public function test_cada_visita_cuenta_una_sola_entrada()
    {
        $zona = $this->crearZona();
        $mac = 'AA:BB:CC:DD:EE:03';

        $this->post('/login_formulario/' . $zona->id, ['mac' => $mac])->assertOk();
        $this->assertEquals(1, HotspotMetric::where('mac_address', $mac)->value('veces_entradas'));

        $this->post('/login_formulario/' . $zona->id, ['mac' => $mac])->assertOk();
        $this->assertEquals(1, HotspotMetric::where('mac_address', $mac)->count());
        $this->assertEquals(2, HotspotMetric::where('mac_address', $mac)->value('veces_entradas'));
    }

    public function test_la_metrica_guarda_el_tipo_de_contenido_mostrado()
    {
        $zona = $this->crearZona('imagen');
        $zona->campanas()->attach($this->crearCampana('imagen', 'campanas/promo.jpg')->id);
        $mac = 'AA:BB:CC:DD:EE:04';

        $this->post('/login_formulario/' . $zona->id, ['mac' => $mac])->assertOk();
        $this->assertEquals('carrusel', HotspotMetric::where('mac_address', $mac)->value('tipo_visual'));

        $zona->campanas()->sync([$this->crearCampana('video', 'campanas/promo.mp4')->id]);
        $this->post('/login_formulario/' . $zona->id, ['mac' => $mac])->assertOk();
        $this->assertEquals('video', HotspotMetric::where('mac_address', $mac)->value('tipo_visual'));
    }

    public function test_la_ruta_de_diagnostico_de_alternancia_funciona()
    {
        $zona = $this->crearZona();

        $this->actingAs(User::factory()->create())
            ->get(route('diagnostico.alternancia', $zona->id))
            ->assertOk();
    }

    public function test_el_portal_no_asocia_campanas_ajenas_a_la_zona()
    {
        $zona = $this->crearZona();
        $this->crearCampana('imagen', 'campanas/global.jpg');

        $this->post('/login_formulario/' . $zona->id, ['mac' => 'AA:BB:CC:DD:EE:02'])
            ->assertOk();

        $this->assertDatabaseCount('campana_zona', 0);
    }
}
