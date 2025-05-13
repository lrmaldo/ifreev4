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

        // Create permissions
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);
        Permission::create(['name' => 'view users']);

        // Create roles and assign existing permissions
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('create users');
        $role->givePermissionTo('edit users');
        $role->givePermissionTo('delete users');
        $role->givePermissionTo('view users');

         // Crear permisos
        $permissions = [
            'crear zonas',
            'editar zonas',
            'eliminar zonas',
            'ver estadísticas',
            'ver publicidad',
            'administrar hotspot',
            'ver usuarios',
            'crear usuarios',
            'editar usuarios',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Crear roles y asignar permisos
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $cliente = Role::firstOrCreate(['name' => 'cliente']);
        $cliente->givePermissionTo(['ver publicidad']);

        $tecnico = Role::firstOrCreate(['name' => 'tecnico']);
        $tecnico->givePermissionTo(['ver estadísticas', 'administrar hotspot']);

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
