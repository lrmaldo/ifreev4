<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       app()['cache']->forget('spatie.permission.cache');

         // Crear permisos
        $permissions = [
            // Zonas
            'crear zonas',
            'editar zonas',
            'eliminar zonas',
            // Estadísticas y publicidad
            'ver estadísticas',
            'ver publicidad',
            'administrar hotspot',
            // Métricas de Hotspot
            'ver metricas hotspot',
            'gestionar metricas hotspot',
            // Usuarios
            'ver usuarios',
            'crear usuarios',
            'editar usuarios',
            // Roles
            'crear roles',
            'editar roles',
            'eliminar roles',
            'ver roles',
            // Permisos
            'crear permisos',
            'editar permisos',
            'eliminar permisos',
            'ver permisos',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Crear roles y asignar permisos
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $cliente = Role::firstOrCreate(['name' => 'cliente']);
        $cliente->givePermissionTo(['ver publicidad', 'ver metricas hotspot']);

        $tecnico = Role::firstOrCreate(['name' => 'tecnico']);
        $tecnico->givePermissionTo(['ver estadísticas', 'administrar hotspot', 'ver metricas hotspot', 'gestionar metricas hotspot']);

        // Crear usuario admin
        $user = User::firstOrCreate(
            ['email' => 'admin@ifree.com'],
            [
                'name' => 'Administrador iFree',
                'password' => Hash::make('password123') // cambia esto luego
            ]
        );
                $user->assignRole($admin);

    }
}
