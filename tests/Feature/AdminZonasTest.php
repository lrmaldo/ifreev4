<?php

namespace Tests\Feature;

use App\Livewire\Admin\Zonas\Form as FormularioZona;
use App\Livewire\Admin\Zonas\Index as ListadoZonas;
use App\Models\Campana;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminZonasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create(['name' => 'Leo Admin']);
        $this->admin->assignRole('admin');
    }

    private function crearZona(array $datos = []): Zona
    {
        return Zona::create(array_merge([
            'nombre' => 'Zona Centro',
            'user_id' => $this->admin->id,
            'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
        ], $datos));
    }

    public function test_el_listado_muestra_cada_zona_con_su_estado()
    {
        $sinNada = $this->crearZona(['nombre' => 'Zona sin nada', 'id_personalizado' => 'zona-x']);
        $completa = $this->crearZona(['nombre' => 'Zona completa', 'portal_tema' => 'evento']);
        $completa->campos()->create(['campo' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'obligatorio' => true, 'orden' => 1]);
        $completa->campanas()->attach(Campana::create([
            'titulo' => 'Promo', 'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addDay()->toDateString(),
            'visible' => true, 'siempre_visible' => true, 'tipo' => 'imagen', 'archivo_path' => 'campanas/p.jpg',
        ])->id);

        $this->actingAs($this->admin)->get('/admin/zonas')
            ->assertOk()
            ->assertSee('Zona sin nada')
            ->assertSee('ID zona-x')
            ->assertSee('Sin campos: agregar')
            ->assertSee('Sin asignar')
            ->assertSee('1 campo')
            ->assertSee('1 asignada')
            ->assertSee('Evento')
            ->assertSee(route('admin.zonas.editar', ['zonaId' => $sinNada->id]))
            ->assertSee(route('admin.zonas.crear'));
    }

    public function test_la_busqueda_filtra_por_nombre_o_id()
    {
        $this->crearZona(['nombre' => 'Expo', 'id_personalizado' => 'expo-mx']);
        $this->crearZona(['nombre' => 'Tienda']);

        Livewire::actingAs($this->admin)->test(ListadoZonas::class)
            ->set('search', 'expo-mx')
            ->assertSee('Expo')
            ->assertDontSee('Tienda');
    }

    public function test_crear_zona_desde_la_pagina()
    {
        $this->actingAs($this->admin)->get('/admin/zonas/crear')->assertOk()->assertSee('Nueva zona')->assertSee('Crear zona');

        Livewire::actingAs($this->admin)->test(FormularioZona::class)
            ->set('zona.nombre', 'Expo MX-ISP')
            ->set('zona.id_personalizado', 'expo-2026')
            ->set('zona.tipo_registro', 'formulario')
            ->set('zona.tipo_autenticacion_mikrotik', 'sin_autenticacion')
            ->set('zona.portal_tema', 'evento')
            ->set('zona.portal_marca_evento', true)
            ->set('zona.telegram_resumen_minutos', '10')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.zonas.index'));

        $zona = Zona::where('id_personalizado', 'expo-2026')->firstOrFail();
        $this->assertEquals($this->admin->id, $zona->user_id);
        $this->assertEquals('evento', $zona->portal_tema);
        $this->assertTrue($zona->portal_marca_evento);
        $this->assertEquals(10, $zona->telegram_resumen_minutos);
    }

    public function test_editar_zona_carga_sus_datos_y_los_guarda()
    {
        $zona = $this->crearZona(['nombre' => 'Original', 'telegram_resumen_minutos' => 15]);

        $this->actingAs($this->admin)->get("/admin/zonas/{$zona->id}/editar")->assertOk()->assertSee('Original')->assertSee('Guardar cambios');

        Livewire::actingAs($this->admin)->test(FormularioZona::class, ['zonaId' => $zona->id])
            ->assertSet('zona.nombre', 'Original')
            ->assertSet('zona.telegram_resumen_minutos', 15)
            ->set('zona.nombre', 'Renombrada')
            ->set('zona.telegram_resumen_minutos', '')
            ->call('save')
            ->assertHasNoErrors();

        $zona->refresh();
        $this->assertEquals('Renombrada', $zona->nombre);
        $this->assertNull($zona->telegram_resumen_minutos);
    }

    public function test_los_logos_del_evento_no_se_guardan_con_el_tema_clasico()
    {
        $zona = $this->crearZona(['portal_tema' => 'evento', 'portal_marca_evento' => true]);

        Livewire::actingAs($this->admin)->test(FormularioZona::class, ['zonaId' => $zona->id])
            ->set('zona.portal_tema', 'clasico')
            ->call('save');

        $this->assertFalse($zona->fresh()->portal_marca_evento);
    }

    public function test_validaciones_del_formulario()
    {
        $this->crearZona(['id_personalizado' => 'ocupado']);

        Livewire::actingAs($this->admin)->test(FormularioZona::class)
            ->set('zona.nombre', '')
            ->set('zona.id_personalizado', 'ocupado')
            ->call('save')
            ->assertHasErrors(['zona.nombre' => 'required', 'zona.id_personalizado' => 'unique']);

        Livewire::actingAs($this->admin)->test(FormularioZona::class)
            ->set('zona.nombre', 'X')
            ->set('zona.id_personalizado', 'con espacios/raros')
            ->call('save')
            ->assertHasErrors(['zona.id_personalizado' => 'regex']);
    }

    public function test_un_usuario_sin_rol_admin_no_entra_a_las_paginas_de_zonas()
    {
        $zona = $this->crearZona();
        $cliente = User::factory()->create();

        $this->actingAs($cliente)->get('/admin/zonas/crear')->assertForbidden();
        $this->actingAs($cliente)->get("/admin/zonas/{$zona->id}/editar")->assertForbidden();
    }

    public function test_la_descarga_solo_acepta_archivos_conocidos()
    {
        $zona = $this->crearZona(['id_personalizado' => 'expo']);

        $this->actingAs($this->admin)->get("/admin/zonas/download/{$zona->id}/login")
            ->assertOk()
            ->assertDownload('login.html');
        $this->actingAs($this->admin)->get("/admin/zonas/download/{$zona->id}/otro")->assertNotFound();
    }

    public function test_eliminar_zona_con_confirmacion()
    {
        $zona = $this->crearZona(['nombre' => 'Para borrar']);

        Livewire::actingAs($this->admin)->test(ListadoZonas::class)
            ->call('confirmZonaDeletion', $zona->id)
            ->assertSee('¿Eliminar esta zona?')
            ->call('deleteZona')
            ->assertSee('Zona "Para borrar" eliminada.');

        $this->assertModelMissing($zona);
    }
}
