<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RespaldoLoginMikrotikTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        config(['ifree.portal_url' => 'https://v3.i-free.com.mx', 'ifree.respaldo_timeout' => 6]);
    }

    private function loginHtml(array $datos): string
    {
        $zona = Zona::create(array_merge([
            'nombre' => 'Expo', 'user_id' => $this->admin->id, 'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15,
        ], $datos));

        return $this->actingAs($this->admin)->get("/admin/zonas/download/{$zona->id}/login")
            ->assertOk()
            ->assertDownload('login.html')
            ->streamedContent();
    }

    public function test_el_login_html_prueba_el_servidor_y_conecta_directo_si_no_responde()
    {
        $html = $this->loginHtml(['id_personalizado' => 'expo2026']);

        $this->assertStringContainsString('action="https://v3.i-free.com.mx/login_formulario/expo2026"', $html);
        $this->assertStringContainsString("var PING = 'https://v3.i-free.com.mx/portal/ping';", $html);
        $this->assertStringContainsString('var TIMEOUT = 6000;', $html);
        $this->assertStringContainsString('var TRIAL = true;', $html);
        $this->assertStringContainsString('username=T-$(mac-esc)', $html);
        // Variables del MikroTik intactas
        $this->assertStringContainsString('value="$(link-login-only)"', $html);
        $this->assertStringContainsString('value="$(mac)"', $html);
        // El comentario de Blade no viaja al router
        $this->assertStringNotContainsString('{{', $html);
        $this->assertStringNotContainsString('Generado por i-Free', $html);
    }

    public function test_zona_con_usuario_usa_el_login_del_mikrotik_como_respaldo()
    {
        $html = $this->loginHtml(['tipo_autenticacion_mikrotik' => 'usuario_password']);

        $this->assertStringContainsString('var TRIAL = false;', $html);
        $this->assertStringContainsString('<form name="login" action="$(link-login-only)" method="post">', $html);
    }

    public function test_la_url_del_portal_se_puede_configurar()
    {
        config(['ifree.portal_url' => 'https://portal.ejemplo.test']);

        $html = $this->loginHtml([]);

        $this->assertStringContainsString("var PING = 'https://portal.ejemplo.test/portal/ping';", $html);
    }

    public function test_el_ping_responde_con_cors_y_sin_sesion()
    {
        $respuesta = $this->get('/portal/ping');

        $respuesta->assertOk()
            ->assertJson(['ok' => true])
            ->assertHeader('Access-Control-Allow-Origin', '*');
        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
        // Sin el grupo "web": no abre sesión (con 1,000 asistentes serían 1,000 filas en sessions)
        $this->assertEmpty($respuesta->headers->getCookies());
    }
}
