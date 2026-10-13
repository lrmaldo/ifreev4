<?php

namespace Tests\Feature;

use App\Models\FormResponse;
use App\Models\HotspotMetric;
use App\Models\MetricaDetalle;
use App\Models\TelegramChat;
use App\Models\User;
use App\Models\Zona;
use App\Services\AlertasEventoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlertasEventoTest extends TestCase
{
    use RefreshDatabase;

    private Zona $zona;
    private int $estadoPortal = 200;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ifree.portal_url' => 'https://portal.test', 'ifree.alertas.chats' => '']);
        Http::fake(['portal.test/*' => fn () => Http::response(['ok' => $this->estadoPortal === 200], $this->estadoPortal)]);

        $this->zona = Zona::create([
            'nombre' => 'Expo MX-ISP', 'user_id' => User::factory()->create()->id, 'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15, 'portal_tema' => 'evento',
        ]);
    }

    private function servicio(): AlertasEventoService
    {
        return app(AlertasEventoService::class);
    }

    private function visitas(int $cuantas, $cuando, bool $nuevos = true): void
    {
        for ($i = 0; $i < $cuantas; $i++) {
            $m = HotspotMetric::create(['zona_id' => $this->zona->id, 'mac_address' => uniqid('M'), 'dispositivo' => 'x', 'navegador' => 'x', 'tipo_visual' => 'formulario', 'veces_entradas' => 1]);
            $m->forceFill(['created_at' => $nuevos ? $cuando : now()->subDays(3)])->saveQuietly();
            MetricaDetalle::create(['metrica_id' => $m->id, 'tipo_evento' => 'vista', 'contenido' => 'formulario', 'fecha_hora' => $cuando]);
        }
    }

    public function test_sin_problemas_no_avisa()
    {
        $this->assertSame([], $this->servicio()->revisar());
    }

    public function test_portal_caido_avisa_una_vez_y_luego_que_se_resolvio()
    {
        $this->estadoPortal = 503;

        $primera = $this->servicio()->revisar();
        $this->assertCount(1, $primera);
        $this->assertStringContainsString('El portal no responde', $primera[0]);
        $this->assertStringContainsString('HTTP 503', $primera[0]);

        // El minuto siguiente no repite (recuerda cada 15 min)
        $this->assertSame([], $this->servicio()->revisar());

        $this->travel(16)->minutes();
        $this->assertStringContainsString('sigue pasando', $this->servicio()->revisar()[0]);

        $this->estadoPortal = 200;
        $this->assertSame(['✅ <b>El portal volvió a responder</b>'], $this->servicio()->revisar());
        $this->assertSame([], $this->servicio()->revisar());
    }

    public function test_muchos_errores_en_cinco_minutos()
    {
        config(['ifree.alertas.errores_5min' => 3]);
        foreach (range(1, 3) as $i) {
            AlertasEventoService::contarError();
        }

        $this->assertStringContainsString('3 errores en 5 minutos', $this->servicio()->revisar()[0]);
    }

    public function test_zona_que_deja_de_recibir_visitas()
    {
        $this->visitas(25, now()->subMinutes(30), nuevos: false);

        $mensajes = $this->servicio()->revisar();
        $this->assertCount(1, $mensajes);
        $this->assertStringContainsString('Expo MX-ISP: sin visitas', $mensajes[0]);

        $this->visitas(1, now(), nuevos: false);
        $this->assertStringContainsString('vuelven a llegar visitas', $this->servicio()->revisar()[0]);
    }

    public function test_se_conectan_pero_nadie_se_registra()
    {
        $this->visitas(16, now()->subMinutes(5));

        $mensajes = $this->servicio()->revisar();
        $this->assertCount(1, $mensajes);
        $this->assertStringContainsString('nadie se registra', $mensajes[0]);

        FormResponse::create(['zona_id' => $this->zona->id, 'mac_address' => 'AA', 'respuestas' => ['nombre' => 'Ana'], 'formulario_completado' => true]);
        $this->assertStringContainsString('vuelven a llegar registros', $this->servicio()->revisar()[0]);
    }

    public function test_destinatarios_configurados_o_los_chats_de_la_zona_evento()
    {
        $chat = TelegramChat::create(['chat_id' => '-100555', 'nombre' => 'Expo', 'tipo' => 'group', 'activo' => true]);
        $chat->zonas()->attach($this->zona->id);

        $this->assertSame(['-100555'], $this->servicio()->destinatarios());

        config(['ifree.alertas.chats' => '-100111, -100222']);
        $this->assertSame(['-100111', '-100222'], $this->servicio()->destinatarios());
    }

    public function test_el_comando_corre_y_esta_programado()
    {
        $this->artisan('evento:vigilar')->assertSuccessful();
        $this->artisan('evento:vigilar --prueba')->assertFailed(); // sin chats configurados

        $this->artisan('schedule:list')->expectsOutputToContain('evento:vigilar')->assertSuccessful();
    }
}
