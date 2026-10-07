<?php

namespace Tests\Feature;

use App\Livewire\Admin\Zonas\Form as FormularioZona;
use App\Models\User;
use App\Models\Zona;
use App\Support\SociosEvento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PortalTemaTest extends TestCase
{
    use RefreshDatabase;

    private function crearZona(array $datos = []): Zona
    {
        $zona = Zona::create(array_merge([
            'nombre' => 'Zona Tema',
            'user_id' => User::factory()->create()->id,
            'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
        ], $datos));
        $zona->campos()->create(['campo' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'obligatorio' => true, 'orden' => 1]);

        return $zona;
    }

    private function portal(Zona $zona)
    {
        return $this->post('/login_formulario/' . $zona->id, ['mac' => 'AA:BB:CC:DD:EE:30'])->assertOk();
    }

    public function test_el_tema_clasico_queda_igual_que_antes()
    {
        $this->portal($this->crearZona())
            ->assertSee('class="tema-clasico"', false)
            ->assertSee('Zona Tema - Portal WiFi')
            ->assertDontSee('class="marca-evento-mxisp"', false)
            ->assertDontSee('class="portal-rifa"', false)
            ->assertDontSee('class="portal-socios-pista"', false)
            ->assertDontSee('content-type-indicator image-indicator', false);
    }

    public function test_el_tema_evento_muestra_marca_mensaje_rifa_y_socios()
    {
        $zona = $this->crearZona([
            'portal_tema' => 'evento',
            'portal_mensaje' => 'Bienvenido a la Expo · WiFi cortesía de i-Free',
            'portal_marca_evento' => true,
            'portal_rifa' => true,
            'portal_socios' => true,
        ]);

        $this->portal($zona)
            ->assertSee('class="tema-evento"', false)
            ->assertSee('marca-evento-mxisp')
            ->assertSee('img/evento/portal/logo-ifree.webp')
            ->assertSee('Bienvenido a la Expo · WiFi cortesía de i-Free')
            ->assertSee('Regístrate y participa en la rifa')
            ->assertSee('img/evento/socios/mini/sattlink.webp')
            ->assertDontSee('Zona Tema - Portal WiFi');
    }

    public function test_el_aviso_de_rifa_no_sale_si_ya_esta_registrado()
    {
        $zona = $this->crearZona(['portal_tema' => 'evento', 'portal_rifa' => true]);
        \App\Models\FormResponse::create(['zona_id' => $zona->id, 'mac_address' => 'AA:BB:CC:DD:EE:30', 'respuestas' => ['nombre' => 'Ana']]);

        $this->portal($zona)->assertDontSee('Regístrate y participa en la rifa');
    }

    public function test_el_portal_usa_logos_mini_y_la_pantalla_los_originales()
    {
        $mini = SociosEvento::lista(mini: true);
        $originales = SociosEvento::lista();

        $this->assertCount(49, $mini);
        $this->assertCount(49, $originales);
        foreach ($mini as $socio) {
            $this->assertStringContainsString('/socios/mini/', $socio['url']);
        }
        $this->assertStringNotContainsString('/mini/', $originales[0]['url']);

        // Todo lo que descarga el celular para el carrusel debe ser ligero
        $peso = collect(glob(public_path('img/evento/socios/mini/*.webp')))->sum(fn ($f) => filesize($f));
        $this->assertLessThan(400 * 1024, $peso);
    }

    public function test_el_admin_configura_la_apariencia_del_portal()
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $zona = $this->crearZona();

        Livewire::actingAs($admin)->test(FormularioZona::class, ['zonaId' => $zona->id])
            ->set('zona.portal_tema', 'evento')
            ->set('zona.portal_mensaje', '  Bienvenidos  ')
            ->set('zona.portal_marca_evento', true)
            ->set('zona.portal_rifa', true)
            ->set('zona.portal_socios', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.zonas.index'));

        $zona->refresh();
        $this->assertEquals('evento', $zona->portal_tema);
        $this->assertEquals('Bienvenidos', $zona->portal_mensaje);
        $this->assertTrue($zona->portal_marca_evento && $zona->portal_rifa && $zona->portal_socios);
    }

    public function test_tema_invalido_no_se_guarda()
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $zona = $this->crearZona();

        Livewire::actingAs($admin)->test(FormularioZona::class, ['zonaId' => $zona->id])
            ->set('zona.portal_tema', 'hackeado')
            ->call('save')
            ->assertHasErrors(['zona.portal_tema']);
    }
}
