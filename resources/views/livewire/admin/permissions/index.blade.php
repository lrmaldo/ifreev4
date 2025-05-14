<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component {
    use WithPagination;
    
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;
    
    // Modal state
    public $showModal = false;
    public $permissionId = null;
    public $confirmingPermissionDeletion = false;
    
    // Form fields
    public $name = '';
    public $assignedRoles = []; // Para mostrar qué roles tienen asignado este permiso
    
    public function rules() {
        return [
            'name' => 'required|string|max:255|unique:permissions,name,' . $this->permissionId,
        ];
    }
    
    public function mount() {
        // Verificar que el usuario tenga el rol admin
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }
    }
    
    public function updatingSearch() {
        $this->resetPage();
    }
    
    public function sortBy($field) {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function openCreateModal() {
        $this->reset(['permissionId', 'name', 'assignedRoles']);
        $this->showModal = true;
    }
    
    public function openEditModal($permissionId) {
        $this->permissionId = $permissionId;
        
        $permission = Permission::findOrFail($permissionId);
        $this->name = $permission->name;
        
        // Obtener los roles que tienen este permiso
        $this->assignedRoles = Role::whereHas('permissions', function($query) use ($permissionId) {
            $query->where('id', $permissionId);
        })->pluck('name')->toArray();
        
        $this->showModal = true;
    }
    
    public function save() {
        $this->validate();
        
        if ($this->permissionId) {
            $permission = Permission::findOrFail($this->permissionId);
            $permission->update([
                'name' => $this->name,
            ]);
        } else {
            Permission::create([
                'name' => $this->name,
            ]);
        }
        
        $this->reset(['showModal', 'permissionId', 'name', 'assignedRoles']);
        $this->dispatch('permission-saved');
    }
    
    public function confirmDelete($permissionId) {
        $this->confirmingPermissionDeletion = $permissionId;
    }
    
    public function deletePermission() {
        $permission = Permission::findOrFail($this->confirmingPermissionDeletion);
        
        // Verificar que no sea un permiso esencial
        $essentialPermissions = ['create users', 'edit users', 'delete users', 'view users'];
        if (in_array($permission->name, $essentialPermissions)) {
            session()->flash('error', 'No se puede eliminar un permiso esencial del sistema.');
        } else {
            $permission->delete();
            $this->dispatch('permission-deleted');
        }
        
        $this->confirmingPermissionDeletion = false;
    }
    
    public function cancelDelete() {
        $this->confirmingPermissionDeletion = false;
    }
    
    public function getPermissionsProperty() {
        return Permission::where('name', 'like', "%{$this->search}%")
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function getGroupedPermissionsProperty() {
        $permissions = $this->permissions;
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $parts = explode(' ', $permission->name, 2);
            $action = $parts[0] ?? 'otros';
            
            if (!isset($grouped[$action])) {
                $grouped[$action] = [];
            }
            
            $grouped[$action][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'target' => count($parts) > 1 ? $parts[1] : '',
            ];
        }
        
        ksort($grouped);
        
        return $grouped;
    }
}
?>

<div>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold leading-6 text-gray-900">Permisos</h1>
                <p class="mt-2 text-sm text-gray-700">Listado de permisos del sistema</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button type="button" 
                        wire:click="openCreateModal"
                        class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Agregar Permiso
                </button>
            </div>
        </div>
        
        <div class="mt-8 flow-root">
            <!-- Filtros -->
            <div class="mb-4">
                <div class="relative mt-2 rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input wire:model.live="search" type="text" name="search" id="search" class="w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Buscar permisos...">
                </div>
            </div>
            
            <!-- Vista agrupada por tipo de permiso -->
            @if(count($this->groupedPermissions) > 0)
                <div class="mt-8 space-y-6">
                    @foreach($this->groupedPermissions as $action => $permissions)
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 capitalize">{{ $action }}</h3>
                            <div class="mt-3 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Permiso</th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                                <span class="sr-only">Acciones</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($permissions as $permission)
                                            <tr>
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                                    {{ $permission['name'] }}
                                                </td>
                                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                    <button wire:click="openEditModal({{ $permission['id'] }})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                        Editar
                                                    </button>
                                                    @if(!in_array($permission['name'], ['create users', 'edit users', 'delete users', 'view users']))
                                                        <button wire:click="confirmDelete({{ $permission['id'] }})" class="text-red-600 hover:text-red-900">
                                                            Eliminar
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 bg-white rounded-lg shadow">
                    <p class="text-gray-500">No se encontraron permisos.</p>
                </div>
            @endif
            
            <div class="mt-4">
                {{ $this->permissions->links() }}
            </div>
        </div>
    </div>
    
    <!-- Modal para crear/editar permiso -->
    <x-dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ $permissionId ? 'Editar Permiso' : 'Crear Permiso' }}
        </x-slot>
        
        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="name" value="Nombre del Permiso" />
                    <x-input id="name" wire:model="name" class="block mt-1 w-full" type="text" />
                    <x-input-error for="name" class="mt-1" />
                    <p class="mt-1 text-sm text-gray-500">
                        Recomendación: Use un formato como "acción objeto" (ej. "crear usuarios", "ver estadísticas").
                    </p>
                </div>
                
                @if($permissionId && count($assignedRoles) > 0)
                    <div>
                        <x-label value="Roles con este permiso" />
                        <div class="mt-2 space-x-1 space-y-1">
                            @foreach($assignedRoles as $role)
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                    {{ $role }}
                                </span>
                            @endforeach
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Para modificar estas asignaciones, edite los roles correspondientes.
                        </p>
                    </div>
                @endif
            </div>
        </x-slot>
        
        <x-slot name="footer">
            <div class="flex justify-end space-x-2">
                <x-secondary-button wire:click="$set('showModal', false)">
                    Cancelar
                </x-secondary-button>
                
                <x-button wire:click="save">
                    {{ $permissionId ? 'Actualizar' : 'Crear' }}
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
    
    <!-- Confirmación de eliminación -->
    <x-confirmation-modal wire:model="confirmingPermissionDeletion">
        <x-slot name="title">
            Eliminar Permiso
        </x-slot>
        
        <x-slot name="content">
            ¿Estás seguro de que deseas eliminar este permiso? Esta acción eliminará el permiso de todos los roles que lo tienen asignado.
        </x-slot>
        
        <x-slot name="footer">
            <div class="flex justify-end space-x-2">
                <x-secondary-button wire:click="cancelDelete">
                    Cancelar
                </x-secondary-button>
                
                <x-danger-button wire:click="deletePermission">
                    Eliminar
                </x-danger-button>
            </div>
        </x-slot>
    </x-confirmation-modal>
</div>
