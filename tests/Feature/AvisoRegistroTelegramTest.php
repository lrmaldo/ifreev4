<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\HotspotMetric;
use App\Models\TelegramChat;
use App\Models\User;
use App\Models\Zona;
use App\Services\PortalToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AvisoRegistroTelegramTest extends TestCase
{
    use RefreshDatabase;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegram.bots.ifree.token' => '123:abc']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->zona = Zona::create([
            'nombre' => 'InterTB', 'user_id' => User::factory()->create()->id, 'tipo_registro' => 'formulario',
            'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15,
        ]);
        FormField::create(['zona_id' => $this->zona->id, 'campo' => 'empresa', 'etiqueta' => 'Empresa', 'tipo' => 'text', 'obligatorio' => true, 'orden' => 1]);

        $chat = TelegramChat::create(['chat_id' => '-640379024', 'nombre' => 'i-free Avisos', 'tipo' => 'group', 'activo' => true]);
        $chat->zonas()->attach($this->zona->id);
    }

    private function registrarse(string $mac = 'AA:BB:CC:DD:EE:01')
    {
        return $this->withoutDefer()->postJson('/zona/formulario/responder', [
            'respuestas' => ['empresa' => 'Tacos <López>'],
            'acepta_privacidad' => 1,
        ], ['X-Portal-Token' => PortalToken::generar($this->zona->id, $mac)]);
    }

    public function test_un_registro_en_el_portal_avisa_al_chat_de_la_zona()
    {
        $this->registrarse()->assertSuccessful();

        Http::assertSent(function (Request $peticion) {
            return str_contains($peticion->url(), '/sendMessage')
                && $peticion['chat_id'] === '-640379024'
                && str_contains($peticion['text'], 'Nuevo registro · InterTB')
                && str_contains($peticion['text'], '<b>Empresa:</b> Tacos &lt;López&gt;') // escapado para el HTML de Telegram
                && str_contains($peticion['text'], 'Registros hoy: <b>1</b>');
        });
    }

    public function test_en_modo_resumen_no_avisa_al_instante()
    {
        $this->zona->update(['telegram_resumen_minutos' => 10]);

        $this->registrarse()->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_un_chat_pausado_no_recibe_avisos()
    {
        TelegramChat::first()->update(['activo' => false]);

        $this->registrarse()->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_un_dispositivo_nuevo_ya_no_encola_trabajos()
    {
        HotspotMetric::registrarMetrica(['zona_id' => $this->zona->id, 'mac_address' => 'AA:01', 'dispositivo' => 'x', 'navegador' => 'x', 'tipo_visual' => 'formulario']);

        // Antes cada dispositivo dejaba un trabajo en la cola que ningún worker procesaba
        $this->assertSame(0, DB::table('jobs')->count());
    }
}
