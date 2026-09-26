<?php

namespace Tests\Feature;

use App\Models\Campana;
use App\Models\User;
use App\Models\Zona;
use App\Services\SeleccionCampanaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeleccionCampanaServiceTest extends TestCase
{
    use RefreshDatabase;

    private SeleccionCampanaService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new SeleccionCampanaService();
    }

    private function crearCliente(string $nombre): int
    {
        return DB::table('clientes')->insertGetId([
            'nombre_comercial' => $nombre,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearZona(?int $clienteId = null, string $modo = 'aleatorio'): Zona
    {
        $user = User::factory()->create(['cliente_id' => $clienteId]);

        return Zona::create([
            'nombre' => 'Zona ' . $user->id,
            'user_id' => $user->id,
            'tipo_registro' => 'sin_registro',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
            'seleccion_campanas' => $modo,
        ]);
    }

    private function crearCampana(array $datos = []): Campana
    {
        return Campana::create(array_merge([
            'titulo' => 'Campaña',
            'fecha_inicio' => now()->subDay()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'visible' => true,
            'siempre_visible' => true,
            'prioridad' => 10,
            'tipo' => 'imagen',
            'archivo_path' => 'campanas/archivo.jpg',
        ], $datos));
    }

    public function test_usa_solo_las_campanas_asignadas_a_la_zona()
    {
        $zona = $this->crearZona();
        $asignada = $this->crearCampana(['titulo' => 'Asignada']);
        $this->crearCampana(['titulo' => 'No asignada']);
        $zona->campanas()->attach($asignada->id);

        $activas = $this->servicio->campanasActivas($zona);

        $this->assertEquals([$asignada->id], $activas->pluck('id')->all());
    }

    public function test_excluye_campanas_asignadas_no_visibles_o_vencidas()
    {
        $zona = $this->crearZona();
        $vigente = $this->crearCampana();
        $oculta = $this->crearCampana(['visible' => false]);
        $vencida = $this->crearCampana([
            'siempre_visible' => false,
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_fin' => now()->subDays(5)->toDateString(),
        ]);
        $zona->campanas()->attach([$vigente->id, $oculta->id, $vencida->id]);

        $this->assertEquals([$vigente->id], $this->servicio->campanasActivas($zona)->pluck('id')->all());
    }

    public function test_sin_asignadas_no_muestra_campanas_de_otro_cliente()
    {
        $clienteA = $this->crearCliente('Cliente A');
        $clienteB = $this->crearCliente('Cliente B');
        $zona = $this->crearZona($clienteA);

        $propia = $this->crearCampana(['cliente_id' => $clienteA]);
        $global = $this->crearCampana(['cliente_id' => null]);
        $ajena = $this->crearCampana(['cliente_id' => $clienteB]);

        $ids = $this->servicio->campanasActivas($zona)->pluck('id')->sort()->values()->all();

        $this->assertEquals([$propia->id, $global->id], $ids);
        $this->assertNotContains($ajena->id, $ids);
    }

    public function test_el_respaldo_no_crea_asociaciones_en_la_base_de_datos()
    {
        $zona = $this->crearZona();
        $this->crearCampana();

        $this->assertCount(1, $zona->getCampanasActivas());
        $this->assertDatabaseCount('campana_zona', 0);
    }

    public function test_alternancia_aleatoria_cambia_de_tipo_respecto_a_la_visita_anterior()
    {
        $campanas = collect([
            $this->crearCampana(['tipo' => 'video', 'archivo_path' => 'v.mp4']),
            $this->crearCampana(['tipo' => 'imagen']),
        ]);

        $this->assertEquals('imagen', $this->servicio->seleccionarDe($campanas, 'aleatorio', 'video')['tipo']);
        $this->assertEquals('video', $this->servicio->seleccionarDe($campanas, 'aleatorio', 'imagen')['tipo']);
        $this->assertContains($this->servicio->seleccionarDe($campanas, 'aleatorio', null)['tipo'], ['video', 'imagen']);
    }

    public function test_modo_prioridad_elige_el_tipo_con_mejor_prioridad()
    {
        $video = $this->crearCampana(['tipo' => 'video', 'archivo_path' => 'v.mp4', 'prioridad' => 5]);
        $imagenTop = $this->crearCampana(['tipo' => 'imagen', 'prioridad' => 1, 'archivo_path' => 'a.jpg']);
        $this->crearCampana(['tipo' => 'imagen', 'prioridad' => 3, 'archivo_path' => 'b.jpg']);
        $campanas = Campana::all();

        $resultado = $this->servicio->seleccionarDe($campanas, 'prioridad', 'imagen');

        $this->assertEquals('imagen', $resultado['tipo']);
        $this->assertEquals($imagenTop->id, $resultado['campana']->id);
        $this->assertCount(1, $resultado['imagenes']);

        $video->update(['prioridad' => 0]);
        $this->assertEquals('video', $this->servicio->seleccionarDe(Campana::all(), 'prioridad', 'video')['tipo']);
    }

    public function test_modo_prioridad_alterna_cuando_hay_empate()
    {
        $campanas = collect([
            $this->crearCampana(['tipo' => 'video', 'archivo_path' => 'v.mp4', 'prioridad' => 2]),
            $this->crearCampana(['tipo' => 'imagen', 'prioridad' => 2]),
        ]);

        $this->assertEquals('imagen', $this->servicio->seleccionarDe($campanas, 'prioridad', 'video')['tipo']);
        $this->assertEquals('video', $this->servicio->seleccionarDe($campanas, 'prioridad', 'imagen')['tipo']);
    }

    public function test_modos_fijos_respetan_preferencia_y_usan_respaldo()
    {
        $video = $this->crearCampana(['tipo' => 'video', 'archivo_path' => 'v.mp4']);
        $imagen = $this->crearCampana(['tipo' => 'imagen']);
        $ambos = collect([$video, $imagen]);

        $this->assertEquals('video', $this->servicio->seleccionarDe($ambos, 'video', 'video')['tipo']);
        $this->assertEquals('imagen', $this->servicio->seleccionarDe($ambos, 'imagen', 'imagen')['tipo']);

        // Sin el tipo preferido, se usa el que haya
        $this->assertEquals('imagen', $this->servicio->seleccionarDe(collect([$imagen]), 'video')['tipo']);
        $this->assertEquals('video', $this->servicio->seleccionarDe(collect([$video]), 'imagen')['tipo']);
    }

    public function test_modo_aleatorio_con_imagenes_devuelve_todas_para_el_carrusel()
    {
        $campanas = collect([
            $this->crearCampana(['archivo_path' => 'a.jpg']),
            $this->crearCampana(['archivo_path' => 'b.jpg']),
        ]);

        $resultado = $this->servicio->seleccionarDe($campanas, 'aleatorio');

        $this->assertEquals('imagen', $resultado['tipo']);
        $this->assertCount(2, $resultado['imagenes']);
        $this->assertSame('', $resultado['videoUrl']);
    }

    public function test_sin_campanas_no_selecciona_nada()
    {
        $resultado = $this->servicio->seleccionarDe(collect(), 'aleatorio');

        $this->assertNull($resultado['tipo']);
        $this->assertNull($resultado['campana']);
        $this->assertSame([], $resultado['imagenes']);
    }
}
