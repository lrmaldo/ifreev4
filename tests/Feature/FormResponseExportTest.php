<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormResponseExportTest extends TestCase
{
    use RefreshDatabase;

    private function crearZona(User $dueno): Zona
    {
        return Zona::create([
            'nombre' => 'Zona de ' . $dueno->name,
            'user_id' => $dueno->id,
            'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
        ]);
    }

    public function test_el_dueno_puede_exportar_las_respuestas_de_su_zona()
    {
        $dueno = User::factory()->create();
        $zona = $this->crearZona($dueno);

        $this->actingAs($dueno)
            ->get(route('form-responses.export', $zona))
            ->assertOk();
    }

    public function test_un_usuario_no_puede_exportar_respuestas_de_una_zona_ajena()
    {
        $zona = $this->crearZona(User::factory()->create());
        $otro = User::factory()->create();

        $this->actingAs($otro)
            ->get(route('form-responses.export', $zona))
            ->assertForbidden();
    }

    public function test_el_admin_puede_exportar_cualquier_zona()
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $zona = $this->crearZona(User::factory()->create());

        $this->actingAs($admin)
            ->get(route('form-responses.export', $zona))
            ->assertOk();
    }

    public function test_un_invitado_es_redirigido_al_login()
    {
        $zona = $this->crearZona(User::factory()->create());

        $this->get(route('form-responses.export', $zona))
            ->assertRedirect(route('login'));
    }
}
