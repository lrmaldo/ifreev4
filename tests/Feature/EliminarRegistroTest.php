<?php

namespace Tests\Feature;

use App\Livewire\FormResponsesList;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EliminarRegistroTest extends TestCase
{
    use RefreshDatabase;

    private Zona $zona;
    private FormResponse $basura;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'cliente']);

        $this->zona = Zona::create([
            'nombre' => 'EXPO', 'user_id' => User::factory()->create()->assignRole('cliente')->id, 'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15,
        ]);
        $this->basura = FormResponse::create(['zona_id' => $this->zona->id, 'mac_address' => 'AA:01', 'respuestas' => ['nombre' => 'Mmmm']]);
        HotspotMetric::registrarMetrica(['zona_id' => $this->zona->id, 'mac_address' => 'AA:01', 'dispositivo' => 'x', 'navegador' => 'x', 'tipo_visual' => 'formulario', 'formulario_id' => $this->basura->id]);
    }

    public function test_el_admin_borra_un_registro_basura()
    {
        $admin = User::factory()->create()->assignRole('admin');

        Livewire::actingAs($admin)->test(FormResponsesList::class, ['zona' => $this->zona])
            ->assertSee('Eliminar')
            ->call('eliminar', $this->basura->id)
            ->assertDontSee('AA:01');

        $this->assertModelMissing($this->basura);
        $this->assertNull(HotspotMetric::where('mac_address', 'AA:01')->value('formulario_id'));
    }

    public function test_el_cliente_no_puede_borrar()
    {
        Livewire::actingAs($this->zona->user)->test(FormResponsesList::class, ['zona' => $this->zona])
            ->assertDontSee('wire:confirm', false)
            ->call('eliminar', $this->basura->id)
            ->assertForbidden();

        $this->assertModelExists($this->basura);
    }
}
