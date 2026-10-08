<?php

namespace Tests\Feature;

use App\Livewire\Admin\Campanas\Form as FormularioCampana;
use App\Livewire\Admin\Campanas\Index as ListadoCampanas;
use App\Models\Campana;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCampanasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function campana(array $datos = []): Campana
    {
        return Campana::create(array_merge([
            'titulo' => 'Promo',
            'fecha_inicio' => now()->subDays(2)->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'visible' => true,
            'siempre_visible' => false,
            'prioridad' => 10,
            'tipo' => 'imagen',
            'archivo_path' => 'campanas/imagenes/promo.jpg',
        ], $datos));
    }

    private function zona(string $nombre = 'Zona'): Zona
    {
        return Zona::create(['nombre' => $nombre, 'user_id' => $this->admin->id, 'tipo_registro' => 'sin_registro', 'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15]);
    }

    public function test_el_estado_de_cada_campana()
    {
        $this->assertEquals('activa', $this->campana()->estado['clave']);
        $this->assertEquals('activa', $this->campana(['siempre_visible' => true, 'fecha_fin' => now()->subYear()->toDateString()])->estado['clave']);
        $this->assertEquals('programada', $this->campana(['fecha_inicio' => now()->addDays(3)->toDateString()])->estado['clave']);
        $this->assertEquals('vencida', $this->campana(['fecha_fin' => now()->subDay()->toDateString()])->estado['clave']);
        $this->assertEquals('oculta', $this->campana(['visible' => false])->estado['clave']);
        $this->assertStringContainsString('solo Lun, Vie', $this->campana(['dias_visibles' => ['1', '5']])->estado['detalle']);
    }

    public function test_el_listado_muestra_estado_cliente_y_zonas()
    {
        $clienteId = DB::table('clientes')->insertGetId(['razon social' => 'Konecta SA de CV', 'created_at' => now(), 'updated_at' => now()]);
        $conZona = $this->campana(['titulo' => 'Con zona', 'cliente_id' => $clienteId]);
        $conZona->zonas()->attach($this->zona()->id);
        $this->campana(['titulo' => 'Vieja', 'fecha_fin' => now()->subDay()->toDateString()]);

        $this->actingAs($this->admin)->get('/admin/campanas')
            ->assertOk()
            ->assertSee('Con zona')
            ->assertSee('Konecta SA de CV') // antes salía vacío por la columna "razon social"
            ->assertSee('Vencida')
            ->assertSee('Ninguna')
            ->assertSee(route('admin.campanas.editar', ['campanaId' => $conZona->id]));
    }

    public function test_los_filtros_se_combinan_bien()
    {
        $this->campana(['titulo' => 'Expo imagen']);
        $this->campana(['titulo' => 'Expo video', 'tipo' => 'video', 'archivo_path' => 'campanas/videos/v.mp4']);
        $this->campana(['titulo' => 'Expo futura', 'fecha_inicio' => now()->addWeek()->toDateString()]);
        $this->campana(['titulo' => 'Otra', 'visible' => false]);

        // La búsqueda ya no se "come" el filtro de tipo
        Livewire::actingAs($this->admin)->test(ListadoCampanas::class)
            ->set('search', 'Expo')
            ->set('filtroTipo', 'video')
            ->assertSee('Expo video')
            ->assertDontSee('Expo imagen');

        Livewire::actingAs($this->admin)->test(ListadoCampanas::class)
            ->set('filtroEstado', 'programadas')
            ->assertSee('Expo futura')
            ->assertDontSee('Expo imagen');

        Livewire::actingAs($this->admin)->test(ListadoCampanas::class)
            ->set('filtroEstado', 'ocultas')
            ->assertSee('Otra')
            ->assertDontSee('Expo imagen')
            ->call('limpiarFiltros')
            ->assertSee('Expo imagen');
    }

    public function test_crear_campana_con_imagen_y_zonas()
    {
        $a = $this->zona('A');
        $b = $this->zona('B');

        $this->actingAs($this->admin)->get('/admin/campanas/crear')->assertOk()->assertSee('Nueva campaña');

        Livewire::actingAs($this->admin)->test(FormularioCampana::class)
            ->set('titulo', 'Konecta')
            ->set('archivo', UploadedFile::fake()->image('konecta.jpg', 1200, 675))
            ->set('dias_visibles', ['1', '3'])
            ->set('prioridad', 3)
            ->set('zonas_ids', [(string) $a->id, (string) $b->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.campanas.index'));

        $campana = Campana::where('titulo', 'Konecta')->firstOrFail();
        Storage::disk('public')->assertExists($campana->archivo_path);
        $this->assertStringStartsWith('campanas/imagenes/', $campana->archivo_path);
        $this->assertSame(['1', '3'], $campana->dias_visibles); // texto: es lo que busca scopeActivas
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $campana->zonas()->pluck('zonas.id')->all());
    }

    public function test_crear_sin_archivo_no_se_permite()
    {
        Livewire::actingAs($this->admin)->test(FormularioCampana::class)
            ->set('titulo', 'Sin archivo')
            ->call('save')
            ->assertHasErrors(['archivo' => 'required']);
    }

    public function test_editar_conserva_el_archivo_y_quita_zonas()
    {
        Storage::disk('public')->put('campanas/imagenes/promo.jpg', 'x');
        $campana = $this->campana();
        $campana->zonas()->attach($this->zona()->id);

        Livewire::actingAs($this->admin)->test(FormularioCampana::class, ['campanaId' => $campana->id])
            ->assertSet('titulo', 'Promo')
            ->set('titulo', 'Promo editada')
            ->set('siempre_visible', true)
            ->call('seleccionarZonas', false)
            ->call('save')
            ->assertHasNoErrors();

        $campana->refresh();
        $this->assertEquals('Promo editada', $campana->titulo);
        $this->assertEquals('campanas/imagenes/promo.jpg', $campana->archivo_path);
        $this->assertTrue($campana->siempre_visible);
        $this->assertNull($campana->dias_visibles);
        $this->assertEquals(0, $campana->zonas()->count());
    }

    public function test_cambiar_de_imagen_a_video_exige_archivo_nuevo()
    {
        $campana = $this->campana();

        Livewire::actingAs($this->admin)->test(FormularioCampana::class, ['campanaId' => $campana->id])
            ->set('tipo', 'video')
            ->call('save')
            ->assertHasErrors(['archivo' => 'required']);

        $this->assertEquals('imagen', $campana->fresh()->tipo);
    }

    public function test_pausar_y_eliminar_desde_el_listado()
    {
        Storage::disk('public')->put('campanas/imagenes/promo.jpg', 'x');
        $campana = $this->campana();

        Livewire::actingAs($this->admin)->test(ListadoCampanas::class)
            ->call('toggleVisibility', $campana->id)
            ->assertSee('Pausada')
            ->call('confirmarEliminar', $campana->id)
            ->assertSee('¿Eliminar esta campaña?')
            ->call('eliminar')
            ->assertSee('Campaña "Promo" eliminada.');

        $this->assertModelMissing($campana);
        Storage::disk('public')->assertMissing('campanas/imagenes/promo.jpg');
    }

    public function test_el_diagnostico_de_archivos_funciona()
    {
        // Antes fallaba siempre: usaba una clase LogFacade que nunca se importó
        Livewire::actingAs($this->admin)->test(ListadoCampanas::class)
            ->call('ejecutarDiagnostico')
            ->assertSee('Diagnóstico:');

        Storage::disk('public')->assertExists('campanas/imagenes');
        Storage::disk('public')->assertExists('campanas/videos');
    }

    public function test_un_usuario_sin_rol_admin_no_entra()
    {
        $campana = $this->campana();
        $otro = User::factory()->create();

        $this->actingAs($otro)->get('/admin/campanas/crear')->assertForbidden();
        $this->actingAs($otro)->get("/admin/campanas/{$campana->id}/editar")->assertForbidden();
    }
}
