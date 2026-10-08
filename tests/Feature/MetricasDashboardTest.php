<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\HotspotMetricsDashboard;
use App\Models\Campana;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Models\User;
use App\Models\Zona;
use App\Services\MetricasHotspotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MetricasDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cliente;
    private Zona $zonaCliente;
    private Zona $zonaAjena;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'ver metricas hotspot']);
        Permission::create(['name' => 'gestionar metricas hotspot']);
        Role::create(['name' => 'admin'])->givePermissionTo(['ver metricas hotspot', 'gestionar metricas hotspot']);
        Role::create(['name' => 'cliente'])->givePermissionTo('ver metricas hotspot');
        Role::create(['name' => 'tecnico']);

        $this->admin = User::factory()->create(['name' => 'Leo Admin']);
        $this->admin->assignRole('admin');
        $this->cliente = User::factory()->create(['name' => 'Rafa Cliente']);
        $this->cliente->assignRole('cliente');

        $this->zonaCliente = $this->zona('Cafe Rafa', $this->cliente);
        $this->zonaAjena = $this->zona('Hotel Ajeno', $this->admin);
    }

    private function zona(string $nombre, User $dueno): Zona
    {
        return Zona::create(['nombre' => $nombre, 'user_id' => $dueno->id, 'tipo_registro' => 'formulario', 'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15]);
    }

    private function visita(Zona $zona, string $mac, string $dispositivo, $cuando = null, int $vistas = 1): HotspotMetric
    {
        $cuando ??= now();
        $metrica = HotspotMetric::create([
            'zona_id' => $zona->id,
            'mac_address' => $mac,
            'dispositivo' => $dispositivo,
            'navegador' => 'Chrome 120',
            'sistema_operativo' => 'Android 14',
            'tipo_visual' => 'formulario',
            'duracion_visual' => 10,
            'veces_entradas' => $vistas,
        ]);
        $metrica->forceFill(['created_at' => $cuando, 'updated_at' => $cuando])->saveQuietly();

        for ($i = 0; $i < $vistas; $i++) {
            MetricaDetalle::create(['metrica_id' => $metrica->id, 'tipo_evento' => 'vista', 'contenido' => 'formulario', 'fecha_hora' => $cuando]);
        }

        return $metrica;
    }

    private function registro(Zona $zona, $cuando = null): void
    {
        $r = FormResponse::create(['zona_id' => $zona->id, 'mac_address' => 'AA', 'respuestas' => ['nombre' => 'X'], 'formulario_completado' => true]);
        $r->forceFill(['created_at' => $cuando ?? now()])->saveQuietly();
    }

    public function test_el_resumen_cuenta_visitas_nuevos_y_registros()
    {
        $this->visita($this->zonaCliente, 'AA:01', 'Moto G', now(), 3);
        $this->visita($this->zonaCliente, 'AA:02', 'iPhone', now());
        $this->visita($this->zonaAjena, 'BB:01', 'Galaxy', now()->subDays(40)); // fuera del periodo
        $this->registro($this->zonaCliente);

        $servicio = app(MetricasHotspotService::class);
        $resumen = $servicio->resumen(null, now()->subDays(6)->startOfDay(), now()->endOfDay());

        $this->assertSame(4, $resumen['visitas']);
        $this->assertSame(2, $resumen['nuevos']);
        $this->assertSame(1, $resumen['recurrentes']);
        $this->assertSame(1, $resumen['registros']);
        $this->assertEquals(50, $resumen['conversion']);

        $porDia = $servicio->porDia(null, now()->subDays(6)->startOfDay(), now()->endOfDay());
        $this->assertCount(7, $porDia);
        $this->assertSame(4, end($porDia)['visitas']);

        $ranking = $servicio->rankingZonas(null, now()->subDays(6)->startOfDay(), now()->endOfDay());
        $this->assertSame('Cafe Rafa', $ranking[0]['nombre']);
        $this->assertSame(1, $ranking[0]['registros']);
    }

    public function test_un_cliente_solo_ve_las_metricas_de_sus_zonas()
    {
        $this->visita($this->zonaCliente, 'AA:01', 'Moto Propio');
        $ajena = $this->visita($this->zonaAjena, 'BB:01', 'Galaxy Ajeno');

        $this->actingAs($this->cliente)->get('/hotspot-metrics')
            ->assertOk()
            ->assertSee('Moto Propio')
            ->assertDontSee('Galaxy Ajeno')
            ->assertDontSee('Hotel Ajeno');

        // Pedir la zona ajena por la URL no le muestra nada de ella
        Livewire::actingAs($this->cliente)->withQueryParams(['zona' => $this->zonaAjena->id])
            ->test(HotspotMetricsDashboard::class)
            ->assertSet('zona_id', '')
            ->assertDontSee('Galaxy Ajeno');

        $this->actingAs($this->cliente)->get("/hotspot-metrics/{$ajena->id}/detalles")->assertForbidden();
        $this->actingAs($this->cliente)->getJson('/hotspot-metrics/analytics?zona_id=' . $this->zonaAjena->id)->assertForbidden();
        $this->actingAs($this->cliente)->getJson('/hotspot-metrics/analytics')
            ->assertOk()
            ->assertJsonPath('resumen.nuevos', 1);
    }

    public function test_exportar_respeta_las_zonas_del_cliente()
    {
        $this->cliente->givePermissionTo('gestionar metricas hotspot');
        $this->visita($this->zonaCliente, 'AA:01', 'Moto Propio');
        $this->visita($this->zonaAjena, 'BB:01', 'Galaxy Ajeno');

        $csv = $this->actingAs($this->cliente)->get('/hotspot-metrics/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('Moto Propio', $csv);
        $this->assertStringNotContainsString('Galaxy Ajeno', $csv);

        $this->actingAs($this->cliente)->get('/hotspot-metrics/export?zona_id=' . $this->zonaAjena->id)->assertForbidden();
    }

    public function test_la_pagina_de_metricas_filtra_por_periodo_y_zona()
    {
        $this->visita($this->zonaCliente, 'AA:01', 'Moto Hoy');
        $this->visita($this->zonaAjena, 'BB:01', 'Galaxy Viejo', now()->subDays(20));

        $this->actingAs($this->admin)->get('/admin/hotspot-metrics')->assertOk()->assertSee('Zonas con más visitas');

        Livewire::actingAs($this->admin)->test(HotspotMetricsDashboard::class)
            ->assertSee('Moto Hoy')
            ->assertSee('Galaxy Viejo')
            ->set('periodo', '7')
            ->assertSee('Moto Hoy')
            ->assertDontSee('Galaxy Viejo')
            ->set('periodo', '30')
            ->set('zona_id', (string) $this->zonaAjena->id)
            ->assertSee('Galaxy Viejo')
            ->assertDontSee('Moto Hoy')
            ->call('sortBy', 'mac_address; drop table users') // columnas no permitidas se ignoran
            ->assertSet('order_by', 'updated_at')
            ->set('periodo', 'rango')
            ->set('desde', now()->toDateString())
            ->set('hasta', now()->subDays(25)->toDateString()) // al revés: se acomoda
            ->assertSee('Galaxy Viejo');
    }

    public function test_dashboard_del_admin_muestra_actividad_y_campanas_por_vencer()
    {
        $this->visita($this->zonaCliente, 'AA:01', 'Moto', now(), 2);
        $this->registro($this->zonaCliente);
        Campana::create(['titulo' => 'Promo Expo', 'fecha_inicio' => now()->subWeek()->toDateString(), 'fecha_fin' => now()->addDays(2)->toDateString(), 'visible' => true, 'siempre_visible' => false, 'prioridad' => 1, 'tipo' => 'imagen', 'archivo_path' => 'x.jpg']);

        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Leo')
            ->assertSee('Visitas hoy')
            ->assertSee('Cafe Rafa')
            ->assertSee('Promo Expo')
            ->assertSee('En 2 días')
            ->assertSee(route('admin.users.index'));

        Livewire::actingAs($this->admin)->test(Dashboard::class)
            ->assertViewHas('resumenHoy', fn ($r) => $r['visitas'] === 2 && $r['registros'] === 1)
            ->assertViewHas('conectados', 1);
    }

    public function test_dashboard_del_cliente_solo_muestra_lo_suyo()
    {
        $this->visita($this->zonaCliente, 'AA:01', 'Moto');
        $this->visita($this->zonaAjena, 'BB:01', 'Galaxy', now(), 5);

        $this->actingAs($this->cliente)->get('/dashboard')
            ->assertOk()
            ->assertSee('Mis zonas')
            ->assertSee('Cafe Rafa')
            ->assertDontSee('Hotel Ajeno')
            ->assertDontSee(route('admin.users.index'));

        Livewire::actingAs($this->cliente)->test(Dashboard::class)
            ->assertViewHas('resumenHoy', fn ($r) => $r['visitas'] === 1);
    }

    public function test_cliente_sin_zonas_ve_la_bienvenida()
    {
        $nuevo = User::factory()->create();
        $nuevo->assignRole('cliente');

        $this->actingAs($nuevo)->get('/dashboard')->assertOk()->assertSee('Bienvenido a i-Free');
    }
}
