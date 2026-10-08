<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\Form as FormularioUsuario;
use App\Livewire\Admin\Users\Index as ListadoUsuarios;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'cliente', 'tecnico'] as $rol) {
            Role::create(['name' => $rol]);
        }
        $this->admin = User::factory()->create(['name' => 'Leo Admin']);
        $this->admin->assignRole('admin');
    }

    public function test_el_listado_muestra_roles_y_filtra_por_rol()
    {
        User::factory()->create(['name' => 'Ana Cliente'])->assignRole('cliente');
        User::factory()->create(['name' => 'Tito Tecnico'])->assignRole('tecnico');
        User::factory()->create(['name' => 'Sin Nada']);

        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('Ana Cliente')
            ->assertSee('Sin rol')
            ->assertSee(route('admin.users.crear'));

        Livewire::actingAs($this->admin)->test(ListadoUsuarios::class)
            ->set('filtroRol', 'cliente')
            ->assertSee('Ana Cliente')
            ->assertDontSee('Tito Tecnico')
            ->set('filtroRol', 'sin_rol')
            ->assertSee('Sin Nada')
            ->assertDontSee('Ana Cliente')
            ->set('filtroRol', 'rol-que-no-existe') // no debe tronar
            ->assertSee('Ana Cliente');
    }

    public function test_la_busqueda_no_se_salta_el_filtro_de_rol()
    {
        User::factory()->create(['name' => 'Expo Cliente'])->assignRole('cliente');
        User::factory()->create(['name' => 'Otro', 'email' => 'expo@tecnico.test'])->assignRole('tecnico');

        Livewire::actingAs($this->admin)->test(ListadoUsuarios::class)
            ->set('filtroRol', 'cliente')
            ->set('search', 'expo')
            ->assertSee('Expo Cliente')
            ->assertDontSee('expo@tecnico.test');
    }

    public function test_crear_usuario_con_rol_y_cliente()
    {
        $clienteId = DB::table('clientes')->insertGetId(['razon social' => 'Konecta SA', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin)->get('/admin/users/crear')->assertOk()->assertSee('Nuevo usuario');

        Livewire::actingAs($this->admin)->test(FormularioUsuario::class)
            ->set('name', 'Rafa')
            ->set('email', 'rafa@konecta.test')
            ->set('password', 'secreto123')
            ->set('roles', ['cliente'])
            ->set('cliente_id', (string) $clienteId)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $rafa = User::where('email', 'rafa@konecta.test')->firstOrFail();
        $this->assertTrue($rafa->hasRole('cliente'));
        $this->assertEquals($clienteId, $rafa->cliente_id); // antes se perdía: no está en $fillable
        $this->assertTrue(Hash::check('secreto123', $rafa->password));
    }

    public function test_editar_sin_contrasena_conserva_la_anterior()
    {
        $user = User::factory()->create(['password' => 'original123']);
        $user->assignRole('cliente');

        Livewire::actingAs($this->admin)->test(FormularioUsuario::class, ['userId' => $user->id])
            ->assertSet('roles', ['cliente'])
            ->set('name', 'Nombre nuevo')
            ->set('roles', ['tecnico'])
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals('Nombre nuevo', $user->name);
        $this->assertTrue(Hash::check('original123', $user->password));
        $this->assertEquals(['tecnico'], $user->getRoleNames()->all());
    }

    public function test_validaciones_al_crear()
    {
        Livewire::actingAs($this->admin)->test(FormularioUsuario::class)
            ->set('name', 'Repetido')
            ->set('email', $this->admin->email)
            ->set('password', 'corta')
            ->call('save')
            ->assertHasErrors(['email' => 'unique', 'password' => 'min']);

        Livewire::actingAs($this->admin)->test(FormularioUsuario::class)
            ->call('generarPassword')
            ->assertSet('password', fn ($p) => strlen($p) === 12);
    }

    public function test_el_admin_no_se_quita_su_propio_rol()
    {
        Livewire::actingAs($this->admin)->test(FormularioUsuario::class, ['userId' => $this->admin->id])
            ->set('roles', ['cliente'])
            ->call('save')
            ->assertHasErrors('roles');

        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_no_se_elimina_a_si_mismo_ni_a_quien_tiene_zonas()
    {
        $dueno = User::factory()->create(['name' => 'Dueño']);
        $zona = Zona::create(['nombre' => 'Zona X', 'user_id' => $dueno->id, 'tipo_registro' => 'sin_registro', 'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15]);

        Livewire::actingAs($this->admin)->test(ListadoUsuarios::class)
            ->call('confirmarEliminar', $dueno->id)
            ->assertSee('No se puede eliminar')
            ->call('eliminar')
            ->call('confirmarEliminar', $this->admin->id)
            ->call('eliminar');

        // Borrarlo habría borrado su zona en cascada
        $this->assertModelExists($dueno);
        $this->assertModelExists($zona);
        $this->assertModelExists($this->admin);

        $libre = User::factory()->create(['name' => 'Sin zonas']);
        Livewire::actingAs($this->admin)->test(ListadoUsuarios::class)
            ->call('confirmarEliminar', $libre->id)
            ->assertSee('¿Eliminar este usuario?')
            ->call('eliminar')
            ->assertSee('Usuario "Sin zonas" eliminado.');
        $this->assertModelMissing($libre);
    }

    public function test_un_usuario_sin_rol_admin_no_entra()
    {
        $otro = User::factory()->create();
        $otro->assignRole('cliente');

        $this->actingAs($otro)->get('/admin/users')->assertForbidden();
        $this->actingAs($otro)->get('/admin/users/crear')->assertForbidden();
        $this->actingAs($otro)->get("/admin/users/{$this->admin->id}/editar")->assertForbidden();
    }
}
