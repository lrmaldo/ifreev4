<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

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
        Route::get('/zonas', function() {
            return view('zonas');
        })->name('admin.zonas.index');
        Route::get('/zonas/download/{zonaId}/{fileType}', function ($zonaId, $fileType) {
            return app()->call([app()->make(App\Livewire\Admin\Zonas\Index::class), 'downloadMikrotikFile'], ['zonaId' => $zonaId, 'fileType' => $fileType]);
        })->name('admin.zonas.download');
        Route::get('/forms', function() {
            return view('forms');
        })->name('admin.forms.index');
        Route::get('/clientes', function() {
            return view('clientes');
        })->name('admin.clientes.index');
    });

    // Rutas para clientes y admins (acceso a zonas)
    Route::middleware(['role:cliente|admin'])->group(function () {
        Route::get('/zonas', function() {
            return view('zonas');
        })->name('cliente.zonas.index');
        Route::get('/zonas/download/{zonaId}/{fileType}', function ($zonaId, $fileType) {
            return app()->call([app()->make(App\Livewire\Admin\Zonas\Index::class), 'downloadMikrotikFile'], ['zonaId' => $zonaId, 'fileType' => $fileType]);
        })->name('cliente.zonas.download');
    });
});

require __DIR__.'/auth.php';
