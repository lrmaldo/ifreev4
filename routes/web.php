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
        Route::get('/users', App\Livewire\Admin\Users\Index::class)->name('admin.users.index');
        Route::get('/roles', App\Livewire\Admin\Roles\Index::class)->name('admin.roles.index');
        Route::get('/permissions', App\Livewire\Admin\Permissions\Index::class)->name('admin.permissions.index');
        Route::get('/zonas', App\Livewire\Admin\Zonas\Index::class)->name('admin.zonas.index');
        Route::get('/zonas/download/{zonaId}/{fileType}', function ($zonaId, $fileType) {
            return app()->call([app()->make(App\Livewire\Admin\Zonas\Index::class), 'downloadMikrotikFile'], ['zonaId' => $zonaId, 'fileType' => $fileType]);
        })->name('admin.zonas.download');
    });

    // Rutas para clientes y admins (acceso a zonas)
    Route::middleware(['role:cliente|admin'])->group(function () {
        Route::get('/zonas', App\Livewire\Admin\Zonas\Index::class)->name('cliente.zonas.index');
        Route::get('/zonas/download/{zonaId}/{fileType}', function ($zonaId, $fileType) {
            return app()->call([app()->make(App\Livewire\Admin\Zonas\Index::class), 'downloadMikrotikFile'], ['zonaId' => $zonaId, 'fileType' => $fileType]);
        })->name('cliente.zonas.download');
    });
});

require __DIR__.'/auth.php';
