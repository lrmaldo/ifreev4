<?php

namespace Tests\Feature;

use App\Console\Commands\EnviarResumenTelegram;
use App\Events\HotspotMetricCreated;
use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\TelegramChat;
use App\Models\User;
use App\Models\Zona;
use App\Services\ResumenZonaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Arreglos para tráfico alto (Expo MX-ISP): límites por dispositivo y resúmenes de Telegram.
 */
class EventoCargaTest extends TestCase
{
    use RefreshDatabase;

    private function crearZona(array $datos = []): Zona
    {
        return Zona::create(array_merge([
            'nombre' => 'Expo',
            'user_id' => User::factory()->create()->id,
            'tipo_registro' => 'sin_registro',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion',
            'segundos' => 15,
        ], $datos));
    }

    public function test_muchos_dispositivos_detras_de_la_misma_ip_no_se_bloquean()
    {
        $zona = $this->crearZona();

        // 80 dispositivos distintos, todos con la misma IP pública del MikroTik
        for ($i = 0; $i < 80; $i++) {
            $mac = sprintf('AA:BB:CC:00:%02X:%02X', intdiv($i, 256), $i % 256);
            $this->post('/login_formulario/' . $zona->id, ['mac' => $mac])->assertOk();
        }

        $this->assertEquals(80, HotspotMetric::count());
    }

    public function test_un_mismo_dispositivo_si_tiene_limite()
    {
        $zona = $this->crearZona();

        for ($i = 0; $i < 20; $i++) {
            $this->post('/login_formulario/' . $zona->id, ['mac' => 'AA:BB:CC:DD:EE:FF'])->assertOk();
        }

        $this->post('/login_formulario/' . $zona->id, ['mac' => 'AA:BB:CC:DD:EE:FF'])->assertStatus(429);
    }

    public function test_una_visita_completa_desde_el_navegador_cuenta_una_sola_entrada()
    {
        $zona = $this->crearZona(['tipo_registro' => 'formulario']);
        $zona->campos()->create(['campo' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'obligatorio' => true, 'orden' => 1]);
        $mac = 'AA:BB:CC:DD:EE:20';

        $visita = function () use ($zona, $mac) {
            $html = $this->post('/login_formulario/' . $zona->id, ['mac' => $mac])->assertOk()->getContent();
            preg_match('/window\.PORTAL_TOKEN = "([^"]+)"/', $html, $m);
            $headers = ['X-Portal-Token' => $m[1]];

            // Lo que hace el JavaScript del portal en el celular
            $this->postJson('/hotspot-metrics/track', ['tipo_visual' => 'carrusel'], $headers)->assertOk();
            $this->postJson('/zona/formulario/responder', ['respuestas' => ['nombre' => 'Ana'], 'acepta_privacidad' => 1], $headers)->assertOk();
            $this->postJson('/hotspot-metrics/update', ['duracion_visual' => 15], $headers)->assertOk();
        };

        $visita();
        $this->assertEquals(1, HotspotMetric::where('mac_address', $mac)->value('veces_entradas'));

        $visita();
        $this->assertEquals(2, HotspotMetric::where('mac_address', $mac)->value('veces_entradas'));
        $this->assertEquals(1, HotspotMetric::where('mac_address', $mac)->count());
    }

    public function test_cada_metrica_tiene_un_solo_listener_de_telegram()
    {
        $this->assertCount(1, Event::getListeners(HotspotMetricCreated::class));
    }

    public function test_toca_resumen_segun_el_intervalo_de_la_zona()
    {
        $servicio = new ResumenZonaService();

        $this->assertFalse($servicio->tocaResumen($this->crearZona(), now()));
        $this->assertTrue($servicio->tocaResumen($this->crearZona(['telegram_resumen_minutos' => 10]), now()));

        $zona = $this->crearZona(['telegram_resumen_minutos' => 10]);
        $zona->forceFill(['telegram_ultimo_resumen_at' => now()->subMinutes(5)])->save();
        $this->assertFalse($servicio->tocaResumen($zona, now()));

        $zona->forceFill(['telegram_ultimo_resumen_at' => now()->subMinutes(10)])->save();
        $this->assertTrue($servicio->tocaResumen($zona, now()));
    }

    public function test_el_resumen_cuenta_nuevos_reconexiones_y_registros()
    {
        $zona = $this->crearZona(['nombre' => 'Expo <MX-ISP>']);
        $desde = now()->subMinutes(10);

        // Llegó antes de la ventana y volvió a conectarse dentro de ella
        $vieja = HotspotMetric::create(['zona_id' => $zona->id, 'mac_address' => 'M1', 'dispositivo' => 'x', 'navegador' => 'x', 'sistema_operativo' => 'iOS 18', 'tipo_visual' => 'carrusel']);
        $vieja->forceFill(['created_at' => now()->subMinutes(30), 'updated_at' => now()->subMinutes(2)])->saveQuietly();

        foreach (['M2', 'M3'] as $mac) {
            HotspotMetric::create(['zona_id' => $zona->id, 'mac_address' => $mac, 'dispositivo' => 'x', 'navegador' => 'x', 'sistema_operativo' => 'Android 14', 'tipo_visual' => 'carrusel']);
        }
        FormResponse::create(['zona_id' => $zona->id, 'mac_address' => 'M2', 'respuestas' => ['nombre' => 'Ana']]);

        $mensaje = (new ResumenZonaService())->construirMensaje($zona, $desde, now());

        $this->assertStringContainsString('Expo &lt;MX-ISP&gt;', $mensaje);
        $this->assertStringContainsString('Dispositivos nuevos: <b>2</b>', $mensaje);
        $this->assertStringContainsString('Reconexiones: <b>1</b>', $mensaje);
        $this->assertStringContainsString('Registros: <b>1</b>', $mensaje);
        $this->assertStringContainsString('Dispositivos: <b>3</b> (Android 2 · iPhone 1)', $mensaje);
    }

    public function test_sin_actividad_no_hay_resumen()
    {
        $zona = $this->crearZona();

        $this->assertNull((new ResumenZonaService())->construirMensaje($zona, now()->subMinutes(10), now()));
    }

    public function test_el_comando_envia_el_resumen_a_los_chats_de_la_zona()
    {
        $zona = $this->crearZona(['telegram_resumen_minutos' => 5]);
        $zona->telegramChats()->attach(TelegramChat::create(['chat_id' => '-100123', 'nombre' => 'Rafa', 'activo' => true])->id);
        HotspotMetric::create(['zona_id' => $zona->id, 'mac_address' => 'M1', 'dispositivo' => 'x', 'navegador' => 'x', 'tipo_visual' => 'carrusel']);

        $enviados = [];
        $this->app->instance(EnviarResumenTelegram::class, new class($enviados) extends EnviarResumenTelegram {
            public function __construct(private array &$enviados)
            {
                parent::__construct();
            }

            protected function enviar(string $chatId, string $mensaje): void
            {
                $this->enviados[] = [$chatId, $mensaje];
            }
        });

        $this->artisan('telegram:resumen-zonas')->assertSuccessful();

        $this->assertCount(1, $enviados);
        $this->assertEquals('-100123', $enviados[0][0]);
        $this->assertNotNull($zona->fresh()->telegram_ultimo_resumen_at);

        // Antes de que pase el intervalo no vuelve a enviar
        $this->artisan('telegram:resumen-zonas')->assertSuccessful();
        $this->assertCount(1, $enviados);
    }
}
