<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Http\Controllers\ZonaLoginController;
use Illuminate\Support\Facades\Route;

// Ruta para manejar las solicitudes de login del Mikrotik
Route::post('/login_formulario/{id}', [ZonaLoginController::class, 'handle'])
    ->name('zona.login.mikrotik')
    ->withoutMiddleware(['web'])  // No requerimos CSRF para esta ruta ya que viene del Mikrotik
    ->middleware(['throttle:60,1']); // Protección contra abusos

// Rutas para el portal cautivo
Route::get('/portal-cautivo/{zonaId}/campanas', [\App\Http\Controllers\PortalCautivoController::class, 'obtenerCampanas'])
    ->name('portal.campanas')
    ->withoutMiddleware(['web'])  // No requerimos CSRF para esta ruta ya que puede ser accedida desde el portal cautivo
    ->middleware(['throttle:60,1']); // Protección contra abusos

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
        Route::get('/clientes', function() {
            return view('clientes');
        })->name('admin.clientes.index');
    });    // Rutas para clientes y admins (acceso a zonas)
    Route::middleware(['role:cliente|admin'])->group(function () {
        Route::get('/zonas', function() {
            return view('zonas');
        })->name('cliente.zonas.index');
        Route::get('/zonas/download/{zonaId}/{fileType}', function ($zonaId, $fileType) {
            return app()->call([app()->make(App\Livewire\Admin\Zonas\Index::class), 'downloadMikrotikFile'], ['zonaId' => $zonaId, 'fileType' => $fileType]);
        })->name('cliente.zonas.download');

        // Ruta para ver el formulario dinámico de una zona
        Route::get('/zonas/{zonaId}/formulario', \App\Livewire\FormularioDinamico::class)
            ->name('cliente.zona.formulario');

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
    });
});

require __DIR__.'/auth.php';
