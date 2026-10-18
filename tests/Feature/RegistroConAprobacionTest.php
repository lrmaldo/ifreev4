<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\Index as ListadoUsuarios;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistroConAprobacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'cliente']);
        config(['telegram.bots.ifree.token' => '123:abc', 'ifree.alertas.chats' => '-100777']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    private function registrarse(): User
    {
        $this->withoutDefer();
        Livewire::test(Register::class)
            ->set('name', 'Pedro Curioso')
            ->set('email', 'pedro@ejemplo.test')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors();

        return User::where('email', 'pedro@ejemplo.test')->firstOrFail();
    }

    public function test_el_registro_publico_no_da_acceso_a_la_plataforma()
    {
        $pedro = $this->registrarse();

        $this->assertFalse($pedro->roles()->exists()); // antes recibía "cliente" al instante

        $this->actingAs($pedro)->get('/dashboard')->assertRedirect(route('cuenta.pendiente'));
        $this->actingAs($pedro)->get('/cuenta/pendiente')->assertOk()->assertSee('Tu cuenta está en revisión');
        $this->actingAs($pedro)->get('/zonas')->assertForbidden();
        $this->actingAs($pedro)->get('/campanas')->assertForbidden();
        $this->actingAs($pedro)->get('/admin/zonas')->assertForbidden();
    }

    public function test_avisa_a_sattlink_por_telegram()
    {
        $this->registrarse();

        Http::assertSent(fn (Request $r) => $r['chat_id'] === '-100777'
            && str_contains($r['text'], 'Cuenta nueva por aprobar')
            && str_contains($r['text'], 'pedro@ejemplo.test'));
    }

    public function test_el_admin_la_aprueba_como_cliente()
    {
        $pedro = $this->registrarse();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)->test(ListadoUsuarios::class)
            ->set('filtroRol', 'sin_rol')
            ->assertSee('Pedro Curioso')
            ->assertSee('Aprobar como cliente')
            ->call('aprobar', $pedro->id)
            ->assertSee('Pedro Curioso ya puede entrar como cliente.');

        $this->assertTrue($pedro->fresh()->hasRole('cliente'));
        $this->actingAs($pedro->fresh())->get('/dashboard')->assertOk();
        $this->actingAs($pedro->fresh())->get('/cuenta/pendiente')->assertRedirect(route('dashboard'));
    }

    public function test_la_pagina_de_prueba_publica_ya_no_existe()
    {
        $this->get('/test-dashboard')->assertNotFound();
    }
}
