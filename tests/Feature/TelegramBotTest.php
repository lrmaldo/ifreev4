<?php

namespace Tests\Feature;

use App\Livewire\Admin\Telegram\Index as PaginaTelegram;
use App\Livewire\Admin\Zonas\Index as ListadoZonas;
use App\Models\TelegramChat;
use App\Models\User;
use App\Models\Zona;
use App\Services\TelegramVinculoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Telegram\Bot\Api;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'secreto-de-prueba-1234567890';

    private User $admin;
    private Zona $expo;
    private Zona $otra;
    /** Mensajes que el bot "envió" */
    private array $enviados = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegram.webhook_secret' => self::SECRETO, 'telegram.bots.ifree.token' => '123:abc']);

        $api = Mockery::mock(Api::class);
        $api->shouldReceive('sendMessage')->andReturnUsing(function ($datos) {
            $this->enviados[] = $datos;
            return new \Telegram\Bot\Objects\Message([]);
        });
        $api->shouldReceive('answerCallbackQuery')->andReturnTrue();
        $this->app->instance(Api::class, $api);

        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $datos = ['user_id' => $this->admin->id, 'tipo_registro' => 'formulario', 'tipo_autenticacion_mikrotik' => 'sin_autenticacion', 'segundos' => 15];
        $this->expo = Zona::create(['nombre' => 'Expo MX-ISP'] + $datos);
        $this->otra = Zona::create(['nombre' => 'Hotel Privado'] + $datos);
    }

    private function mensaje(string $texto, int $chatId = 555, ?string $secreto = self::SECRETO)
    {
        return $this->postJson('/telegram/webhook', [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => $chatId, 'type' => 'private', 'first_name' => 'Ana'],
                'from' => ['id' => $chatId, 'first_name' => 'Ana'],
                'text' => $texto,
            ],
        ], $secreto ? ['X-Telegram-Bot-Api-Secret-Token' => $secreto] : []);
    }

    private function ultimoTexto(): string
    {
        return end($this->enviados)['text'] ?? '';
    }

    public function test_el_webhook_rechaza_peticiones_sin_el_secreto()
    {
        $this->mensaje('/start', secreto: null)->assertForbidden();
        $this->mensaje('/start', secreto: 'otro-secreto-cualquiera-xx')->assertForbidden();

        config(['telegram.webhook_secret' => '']); // sin configurar: rechaza todo
        $this->mensaje('/start')->assertForbidden();

        $this->assertSame([], $this->enviados);
        $this->assertSame(0, TelegramChat::count());
    }

    public function test_registrar_con_el_id_de_zona_ya_no_suscribe()
    {
        $this->mensaje('/start')->assertOk();
        $this->mensaje('/registrar ' . $this->expo->id)->assertOk();

        $this->assertStringContainsString('código de vinculación', $this->ultimoTexto());
        $this->assertSame(0, TelegramChat::first()->zonas()->count());
    }

    public function test_registrar_con_codigo_liga_el_chat_una_sola_vez()
    {
        $codigo = app(TelegramVinculoService::class)->generarCodigo($this->expo);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $codigo);

        $this->mensaje('/start')->assertOk();
        $this->mensaje('/registrar ' . strtolower($codigo))->assertOk(); // sin importar mayúsculas

        $this->assertStringContainsString('Expo MX-ISP', $this->ultimoTexto());
        $this->assertSame([$this->expo->id], TelegramChat::first()->zonas()->pluck('zonas.id')->all());

        // Un solo uso: otro chat con el mismo código no entra
        $this->mensaje('/start', 777);
        $this->mensaje('/registrar ' . $codigo, 777);
        $this->assertStringContainsString('no válido o vencido', $this->ultimoTexto());
        $this->assertSame(0, TelegramChat::where('chat_id', '777')->first()->zonas()->count());
    }

    public function test_el_codigo_vence_y_los_intentos_se_limitan()
    {
        $codigo = app(TelegramVinculoService::class)->generarCodigo($this->expo);
        $this->travel(31)->minutes();

        $this->mensaje('/start');
        $this->mensaje('/registrar ' . $codigo);
        $this->assertStringContainsString('no válido o vencido', $this->ultimoTexto());

        foreach (range(1, 4) as $i) {
            $this->mensaje('/registrar AAAA-BBB' . $i);
        }
        $nuevo = app(TelegramVinculoService::class)->generarCodigo($this->expo);
        $this->mensaje('/registrar ' . $nuevo);
        $this->assertStringContainsString('Demasiados intentos', $this->ultimoTexto());
        $this->assertSame(0, TelegramChat::first()->zonas()->count());
    }

    public function test_zonas_estado_y_detalle_solo_muestran_lo_del_chat()
    {
        $this->mensaje('/start');
        TelegramChat::first()->zonas()->attach($this->expo->id);

        $this->mensaje('/zonas');
        $this->assertStringContainsString('Expo MX-ISP', $this->ultimoTexto());
        $this->assertStringNotContainsString('Hotel Privado', $this->ultimoTexto());

        $this->mensaje('/detalle ' . $this->otra->id);
        $this->assertStringContainsString('no está ligada a este chat', $this->ultimoTexto());

        $this->mensaje('/estado');
        $this->assertStringNotContainsString('Zonas totales', $this->ultimoTexto());
    }

    public function test_el_boton_viejo_de_zona_no_suscribe()
    {
        $this->mensaje('/start');

        $this->postJson('/telegram/webhook', [
            'update_id' => 2,
            'callback_query' => ['id' => 'cb1', 'data' => 'zona:' . $this->otra->id, 'message' => ['chat' => ['id' => 555]]],
        ], ['X-Telegram-Bot-Api-Secret-Token' => self::SECRETO])->assertOk();

        $this->assertSame(0, TelegramChat::first()->zonas()->count());
    }

    public function test_el_codigo_se_genera_desde_el_menu_de_la_zona()
    {
        Livewire::actingAs($this->admin)->test(ListadoZonas::class)
            ->call('generarCodigoTelegram', $this->expo->id)
            ->assertSee('Vincular Telegram · Expo MX-ISP')
            ->assertSee('/registrar ');

        $this->assertNotNull($this->expo->fresh()->telegram_codigo);
        $this->assertArrayNotHasKey('telegram_codigo', $this->expo->fresh()->toArray());
    }

    public function test_la_pagina_de_telegram_muestra_datos_reales()
    {
        $chat = TelegramChat::create(['chat_id' => '-100999', 'nombre' => 'Equipo Expo', 'tipo' => 'supergroup', 'activo' => true]);
        $chat->zonas()->attach($this->expo->id);

        $this->actingAs($this->admin)->get('/admin/telegram')
            ->assertOk()
            ->assertSee('Equipo Expo')
            ->assertSee('1 chats activos')
            ->assertSee('1 zonas con avisos')
            ->assertDontSee('123:abc');

        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'ifree_bot', 'first_name' => 'i-Free']]),
            'api.telegram.org/*/getWebhookInfo' => Http::response(['ok' => true, 'result' => ['url' => config('telegram.bots.ifree.webhook_url'), 'pending_update_count' => 0]]),
        ]);

        Livewire::actingAs($this->admin)->test(PaginaTelegram::class)
            ->call('revisarConexion')
            ->assertSee('@ifree_bot')
            ->assertSee('Apunta a este servidor')
            ->set('zonaParaAgregar.' . $chat->id, (string) $this->otra->id)
            ->call('agregarZona', $chat->id)
            ->call('quitarZona', $chat->id, $this->expo->id)
            ->call('alternarActivo', $chat->id);

        $chat->refresh();
        $this->assertSame([$this->otra->id], $chat->zonas()->pluck('zonas.id')->all());
        $this->assertFalse($chat->activo);
    }

    public function test_enviar_notificacion_solo_para_admin()
    {
        $cliente = User::factory()->create();

        $this->actingAs($cliente)->postJson('/telegram/enviar-notificacion', ['zona_id' => $this->expo->id, 'titulo' => 'x', 'mensaje' => 'y'])
            ->assertForbidden();
    }
}
