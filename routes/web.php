<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Http\Controllers\ZonaLoginController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;


 // Rutas para previsualizar el portal cautivo de una zona
        Route::get('/zonas/{id}/preview', [\App\Http\Controllers\ZonaController::class, 'preview'])
            ->name('cliente.zona.preview');

        // Ruta para previsualizar portal cautivo con carrusel de imágenes
        Route::get('/zonas/{id}/preview/carrusel', [\App\Http\Controllers\ZonaController::class, 'previewCarrusel'])
            ->name('cliente.zona.preview.carrusel');

        // Ruta para previsualizar portal cautivo con reproducción de video
        Route::get('/zonas/{id}/preview/video', [\App\Http\Controllers\ZonaController::class, 'previewVideo'])
            ->name('cliente.zona.preview.video');

        // Ruta para previsualizar portal cautivo con contenido dinámico de campañas
        Route::get('/zonas/{id}/preview/campana', [\App\Http\Controllers\ZonaController::class, 'previewCampana'])
            ->name('cliente.zona.preview.campana');

// Ruta para manejar las solicitudes de login del Mikrotik
Route::post('/login_formulario/{id}', [ZonaLoginController::class, 'handle'])
    ->name('zona.login.mikrotik')
    ->withoutMiddleware(['web'])  // No requerimos CSRF para esta ruta ya que viene del Mikrotik
    ->middleware(['throttle:60,1']); // Protección contra abusos

// Ruta para procesar formularios del portal cautivo
Route::post('/zona/formulario/responder', [ZonaLoginController::class, 'procesarFormulario'])
    ->name('zona.formulario.responder')
    ->withoutMiddleware(['web'])
    ->middleware(['throttle:30,1']);

// Rutas para el portal cautivo
Route::get('/portal-cautivo/{zonaId}/campanas', [\App\Http\Controllers\PortalCautivoController::class, 'obtenerCampanas'])
    ->name('portal.campanas')
    ->withoutMiddleware(['web'])  // No requerimos CSRF para esta ruta ya que puede ser accedida desde el portal cautivo
    ->middleware(['throttle:60,1']); // Protección contra abusos

// Ruta de diagnóstico para probar la alternancia de campañas
Route::get('/diagnostico/alternancia/{zonaId}', function($zonaId) {
    // Solo disponible en entornos no productivos
    if (config('app.env') === 'production') {
        abort(403, 'Esta ruta solo está disponible en entornos de desarrollo y pruebas');
    }

    $zona = \App\Models\Zona::findOrFail($zonaId);

    // Datos simulados para diagnóstico
    $mikrotikData = [
        'mac' => '00:11:22:33:44:55',
        'link-login-only' => '/portal',
        'link-orig' => 'http://example.com',
        'link-orig-esc' => 'http%3A%2F%2Fexample.com'
    ];

    // Preparar información básica de métrica
    $metricaInfo = [
        'zona_id' => $zona->id,
        'mac_address' => $mikrotikData['mac'],
        'dispositivo' => 'Diagnóstico',
        'navegador' => 'Herramienta de diagnóstico',
        'tipo_visual' => 'diagnostico_alternancia',
        'tiempo_inicio' => now()
    ];

    // Usar el método existente para mostrar el portal
    $controller = new \App\Http\Controllers\ZonaLoginController();
    return $controller->mostrarPortalCautivo($zona, $mikrotikData, $metricaInfo);
})
->name('diagnostico.alternancia')
->middleware(['auth']); // Solo usuarios autenticados

// Ruta para obtener todas las campañas activas
Route::get('/portal-cautivo/{zonaId}/todas-campanas', [\App\Http\Controllers\PortalCautivoController::class, 'obtenerTodasCampanas'])
    ->name('portal.todas.campanas')
    ->withoutMiddleware(['web'])
    ->middleware(['throttle:60,1']);

// Ruta para registrar la reproducción completa de un video
Route::post('/portal-cautivo/{zonaId}/video-completado', [\App\Http\Controllers\PortalCautivoController::class, 'videoCompletado'])
    ->name('portal.video.completado')
    ->withoutMiddleware(['web'])
    ->middleware(['throttle:60,1']);

// Ruta para guardar respuestas de formularios desde el portal cautivo
Route::post('/form-responses', [\App\Http\Controllers\FormResponseController::class, 'store'])
    ->name('form-responses.store')
    ->withoutMiddleware(['web'])
    ->middleware(['throttle:60,1']);

// Ruta para registrar métricas de hotspot desde el portal cautivo
Route::post('/hotspot-metrics/track', [\App\Http\Controllers\HotspotMetricController::class, 'track'])
    ->name('hotspot-metrics.track')
    ->withoutMiddleware(['web'])
    ->middleware(['throttle:120,1']);

// Ruta para actualizar métricas desde el frontend
Route::post('/hotspot-metrics/update', [\App\Http\Controllers\ZonaLoginController::class, 'actualizarMetrica'])
    ->name('hotspot-metrics.update')
    ->withoutMiddleware(['web'])
    ->middleware(['throttle:120,1']);

// Rutas para el registro de usuarios en zonas WiFi
Route::get('/zona/{zonaId}/registro/formulario', function($zonaId) {
    $zona = \App\Models\Zona::findOrFail($zonaId);
    $mikrotikData = session('mikrotik_data', []);
    return view('auth.mikrotik.formulario-registro', compact('zona', 'mikrotikData'));
})->name('zona.registro.formulario');

Route::get('/zona/{zonaId}/registro/redes', function($zonaId) {
    $zona = \App\Models\Zona::findOrFail($zonaId);
    $mikrotikData = session('mikrotik_data', []);
    return view('auth.mikrotik.redes-sociales', compact('zona', 'mikrotikData'));
})->name('zona.registro.redes');

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Rutas para administración de usuarios, roles y permisos
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('/users', function() {
            return view('users');
        })->name('admin.users.index');
        Route::get('/roles', function() {
            return view('roles');
        })->name('admin.roles.index');
        Route::get('/permissions', function() {
            return view('permissions');
        })->name('admin.permissions.index');
        Route::get('/campanas', function() {
            return view('campanas');
        })->name('admin.campanas.index');
        Route::get('/zonas', function() {
            return view('zonas');
        })->name('admin.zonas.index');
        Route::get('/zonas/download/{zonaId}/{fileType}', function ($zonaId, $fileType) {
            return app()->call([app()->make(App\Livewire\Admin\Zonas\Index::class), 'downloadMikrotikFile'], ['zonaId' => $zonaId, 'fileType' => $fileType]);
        })->name('admin.zonas.download');
        Route::get('/forms', function() {
            return view('forms');
        })->name('admin.forms.index');

        // Rutas para administración de campos de formulario
        Route::get('/zonas/{zonaId}/form-fields', \App\Livewire\Admin\AdminFormFields::class)
            ->name('admin.zone.form-fields');

        // Ruta para configuración de campañas de una zona
        Route::get('/zonas/{zonaId}/configuracion-campanas', \App\Livewire\Admin\Zonas\ConfiguracionCampanas::class)
            ->name('admin.zonas.configuracion-campanas');

        // Ruta para administrar opciones de un campo de formulario
        Route::get('/form-fields/{formField}/options', \App\Livewire\Admin\FormFieldOptions::class)
            ->name('admin.form-fields.options');

        // Rutas para ver respuestas de formularios (admin ve todas las zonas)
        Route::get('/zonas/{zonaId}/form-responses', [\App\Http\Controllers\FormResponseController::class, 'index'])
            ->name('admin.zona.form-responses');

        // Rutas para métricas de hotspot (solo admin)
        Route::middleware(['permission:ver metricas hotspot'])->group(function () {
            Route::get('/hotspot-metrics', [\App\Http\Controllers\HotspotMetricController::class, 'index'])
                ->name('admin.hotspot-metrics.index');
            Route::get('/hotspot-metrics/analytics', [\App\Http\Controllers\HotspotMetricController::class, 'analytics'])
                ->name('admin.hotspot-metrics.analytics');
            Route::get('/hotspot-metrics/{id}/detalles', [\App\Http\Controllers\HotspotMetricController::class, 'detalles'])
                ->name('admin.hotspot-metrics.detalles');
        });
        Route::get('/hotspot-metrics/export', [\App\Http\Controllers\HotspotMetricController::class, 'export'])
            ->middleware(['permission:gestionar metricas hotspot'])
            ->name('admin.hotspot-metrics.export');

        Route::get('/clientes', function() {
            return view('clientes');
        })->name('admin.clientes.index');

        // Ruta para gestión de Telegram
        Route::get('/telegram', function() {
            return view('admin.telegram');
        })->name('admin.telegram.index');
    });    // Rutas para clientes y admins (acceso a zonas)
    Route::middleware(['role:cliente|admin'])->group(function () {
        Route::get('/zonas', function() {
            return view('zonas');
        })->name('cliente.zonas.index');
        Route::get('/zonas/download/{zonaId}/{fileType}', function ($zonaId, $fileType) {
            return app()->call([app()->make(App\Livewire\Admin\Zonas\Index::class), 'downloadMikrotikFile'], ['zonaId' => $zonaId, 'fileType' => $fileType]);
        })->name('cliente.zonas.download');

        // Ruta para campañas de cliente
        Route::get('/campanas', function() {
            return view('cliente.campanas');
        })->name('cliente.campanas.index');

        // Ruta para configuración de campañas de zona (cliente)
        Route::get('/zonas/{zonaId}/configuracion-campanas', function($zonaId) {
            return view('cliente.configuracion-campanas', ['zonaId' => $zonaId]);
        })->name('cliente.zonas.configuracion-campanas');

        // Ruta para ver el formulario dinámico de una zona
        Route::get('/zonas/{zonaId}/formulario', \App\Livewire\FormularioDinamico::class)
            ->name('cliente.zona.formulario');



        // Rutas para ver respuestas de formularios (cliente y admin ven solo sus zonas)
        Route::get('/zonas/{zonaId}/form-responses', [\App\Http\Controllers\FormResponseController::class, 'index'])
            ->name('cliente.zona.form-responses');

        // Rutas para métricas de hotspot (clientes y técnicos)
        Route::middleware(['permission:ver metricas hotspot'])->group(function () {
            Route::get('/hotspot-metrics', [\App\Http\Controllers\HotspotMetricController::class, 'index'])
                ->name('hotspot-metrics.index');
            Route::get('/hotspot-metrics/analytics', [\App\Http\Controllers\HotspotMetricController::class, 'analytics'])
                ->name('hotspot-metrics.analytics');
            Route::get('/hotspot-metrics/{id}/detalles', [\App\Http\Controllers\HotspotMetricController::class, 'detalles'])
                ->name('hotspot-metrics.detalles');
        });
        Route::get('/hotspot-metrics/export', [\App\Http\Controllers\HotspotMetricController::class, 'export'])
            ->middleware(['permission:gestionar metricas hotspot'])
            ->name('hotspot-metrics.export');
    });
    Route::get('/zonas/{zona}/form-responses/export', [\App\Http\Controllers\FormResponseExportController::class, 'export'])
            ->name('form-responses.export');

});

// Ruta de prueba para mostrar el dashboard de administración con Tailwind
Route::get('/test-dashboard', function() {
    return view('test-dashboard');
})->name('test.dashboard');

// Rutas para el webhook de Telegram
// Nota: La ruta principal del webhook es manejada por Telegraph::telegraph() más abajo en este archivo
// Se comenta esta ruta para evitar conflictos con el enrutamiento automático de Telegraph
// Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
//    ->name('telegram.webhook')
//    ->withoutMiddleware(['web', 'auth', 'verified'])
//    ->middleware(['throttle:100,1']);

// Ruta GET para diagnóstico rápido del webhook
Route::get('/telegram/webhook/check', function() {
    return response()->json([
        'status' => 'active',
        'message' => 'El endpoint del webhook está configurado correctamente',
        'timestamp' => now()->toIso8601String(),
        'telegram_bot_enabled' => (bool)config('app.telegram_bot_enabled', env('TELEGRAM_BOT_ENABLED')),
        'handler' => config('telegraph.webhook.handler'),
        'env' => app()->environment(),
        'debug' => config('app.debug'),
    ]);
})->name('telegram.webhook.check');

// Ruta POST para probar manualmente el webhook (solo en entorno local)
if (app()->environment() != 'production') {
    Route::post('/telegram/webhook/test', function(\Illuminate\Http\Request $request) {
        // Crear una simpleupdate de prueba para el bot
        $testUpdate = [
            'update_id' => rand(1000000, 9999999),
            'message' => [
                'message_id' => rand(1000, 9999),
                'from' => [
                    'id' => 12345,
                    'first_name' => 'Test',
                    'username' => 'test_user',
                    'is_bot' => false
                ],
                'chat' => [
                    'id' => 12345,
                    'first_name' => 'Test Chat',
                    'type' => 'private'
                ],
                'date' => time(),
                'text' => '/start'
            ]
        ];

        // Registrar la acción
        \Illuminate\Support\Facades\Log::info('Prueba manual de webhook iniciada');

        return response()->json([
            'status' => 'received',
            'message' => 'Solicitud de prueba recibida',
            'timestamp' => now()->toIso8601String(),
            'update_simulated' => true,
            'note' => 'Las pruebas deben realizarse con el webhook registrado correctamente'
        ]);
    })->name('telegram.webhook.test')
      ->withoutMiddleware(['web', 'auth', 'verified', 'throttle']);
}

// Rutas para Telegram (nueva implementación con Telegram Bot SDK)
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])
    ->name('telegram.webhook')
    ->withoutMiddleware(['web'])  // No requerimos CSRF para el webhook
    ->middleware(['throttle:60,1']); // Protección contra abusos

Route::post('/telegram/enviar-notificacion', [TelegramController::class, 'enviarNotificacion'])
    ->name('telegram.notificacion')
    ->middleware(['auth:sanctum']);

require __DIR__.'/auth.php';
