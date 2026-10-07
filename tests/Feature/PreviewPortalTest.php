<?php

namespace Tests\Feature;

use App\Livewire\Admin\Zonas\Index as AdminZonas;
use App\Models\Campana;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PreviewPortalTest extends TestCase
{
    use RefreshDatabase;

    private function crearZona(array $datos = []): Zona
    {
        $zona = Zona::create(array_merge([
            'nombre' => 'EXPO MX-ISP 2026',
            'user_id' => User::factory()->create()->id,
            'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
        ], $datos));
        $zona->campos()->create(['campo' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'obligatorio' => true, 'orden' => 1]);

        return $zona;
    }

    private function campana(Zona $zona, string $tipo, string $archivo): void
    {
        $zona->campanas()->attach(Campana::create([
            'titulo' => "Promo {$tipo}", 'fecha_inicio' => now()->subDay()->toDateString(), 'fecha_fin' => now()->addDay()->toDateString(),
            'visible' => true, 'siempre_visible' => true, 'tipo' => $tipo, 'archivo_path' => $archivo,
        ])->id);
    }

    public function test_la_preview_usa_el_portal_real_con_el_tema_de_la_zona()
    {
        $zona = $this->crearZona(['portal_tema' => 'evento', 'portal_marca_evento' => true, 'portal_rifa' => true, 'portal_mensaje' => 'Bienvenido WiFi']);

        $this->get("/zonas/{$zona->id}/preview")
            ->assertOk()
            ->assertSee('class="tema-evento"', false)
            ->assertSee('class="marca-evento-mxisp"', false)
            ->assertSee('Bienvenido WiFi')
            ->assertSee('Regístrate y participa en la rifa')
            ->assertSee('Vista previa · no se guarda ningún dato')
            ->assertSee('window.PORTAL_PREVIEW = true', false)
            ->assertSee(route('cliente.zona.preview.conectado', ['id' => $zona->id]), false);
    }

    public function test_la_preview_no_registra_metricas_ni_entrega_token()
    {
        $zona = $this->crearZona();
        // Aunque la MAC de prueba ya tenga respuesta, la preview siempre enseña el formulario
        FormResponse::create(['zona_id' => $zona->id, 'mac_address' => '00:11:22:33:44:55', 'respuestas' => ['nombre' => 'X']]);

        $this->get("/zonas/{$zona->id}/preview")
            ->assertOk()
            ->assertSee('window.PORTAL_TOKEN = ""', false)
            ->assertSee('id="portal-form"', false)
            ->assertCookieMissing('ultimo_tipo_zona_' . $zona->id);

        $this->assertDatabaseCount('hotspot_metrics', 0);
        $this->assertEquals(1, FormResponse::count());
    }

    public function test_las_previews_de_video_e_imagen_fuerzan_el_tipo()
    {
        $zona = $this->crearZona(['seleccion_campanas' => 'aleatorio']);
        $this->campana($zona, 'video', 'campanas/promo.mp4');
        $this->campana($zona, 'imagen', 'campanas/promo.jpg');

        foreach (range(1, 3) as $i) {
            $this->get("/zonas/{$zona->id}/preview/video")->assertOk()->assertSee('promo.mp4')->assertDontSee('promo.jpg');
            $this->get("/zonas/{$zona->id}/preview/carrusel")->assertOk()->assertSee('promo.jpg')->assertDontSee('promo.mp4');
        }
    }

    public function test_la_pantalla_final_acepta_el_post_del_portal_sin_csrf()
    {
        $zona = $this->crearZona(['portal_tema' => 'evento']);

        $this->post("/zonas/{$zona->id}/preview/conectado", ['username' => 'T-00:11:22:33:44:55'])
            ->assertOk()
            ->assertSee('¡Listo, ya tienes internet!');
        $this->get("/zonas/{$zona->id}/preview/conectado?dst=https%3A%2F%2Fwww.google.com%2F")->assertOk();
    }

    public function test_la_casilla_de_logos_aparece_aunque_otra_zona_del_listado_sea_clasica()
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $evento = $this->crearZona(['portal_tema' => 'evento']);
        $this->crearZona(['nombre' => 'Otra zona clásica']);

        Livewire::actingAs($admin)->test(AdminZonas::class)
            ->call('openModal', true, $evento->id)
            ->assertSee('id="portal_marca_evento"', false)
            ->set('zona.portal_tema', 'clasico')
            ->assertDontSee('id="portal_marca_evento"', false);
    }
}
