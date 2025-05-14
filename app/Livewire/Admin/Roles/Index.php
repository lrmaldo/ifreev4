<?php

namespace App\Livewire\Admin\Roles;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

#[Layout('components.layouts.admin-dashboard')]
#[Title('Administración de Roles')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Modal state
    public $showRoleModal = false;
    public $roleId = null;
    public $confirmingRoleDeletion = false;

    // Form fields
    public $name = '';
    public $selectedPermissions = []; // Array de IDs de permisos



    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'array',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openRoleModal($roleId = null)
    {
        $this->resetValidation();
        $this->roleId = $roleId;

        if ($roleId) {
            $role = Role::with('permissions')->findOrFail($roleId);
            $this->name = $role->name;
            // Aseguramos que esto sea un array de IDs
            $this->selectedPermissions = $role->permissions->pluck('id')->toArray();
        } else {
            $this->name = '';
            $this->selectedPermissions = [];
        }

        $this->showRoleModal = true;
        $this->dispatch('showRoleModal');
    }

    public function closeRoleModal()
    {
        $this->showRoleModal = false;
        $this->dispatch('hideRoleModal');
    }

    public function saveRole()
    {
        $this->validate();

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            $role->update([
                'name' => $this->name,
            ]);
        } else {
            $role = Role::create([
                'name' => $this->name,
            ]);
        }

        // Convertir IDs de permisos a nombres de permisos
        if (!empty($this->selectedPermissions)) {
            $permissionNames = Permission::whereIn('id', $this->selectedPermissions)
                ->pluck('name')
                ->toArray();
            $role->syncPermissions($permissionNames);
        } else {
            $role->syncPermissions([]);
        }

        $this->showRoleModal = false;
        $this->dispatch('hideRoleModal');
        $this->dispatch('role-saved');
    }

    public function confirmRoleDeletion($roleId)
    {
        $this->confirmingRoleDeletion = $roleId;
        $this->dispatch('showDeleteModal');
    }

    public function deleteRole()
    {
        $role = Role::findOrFail($this->confirmingRoleDeletion);
        // Verificar que no sea un rol esencial
        if (in_array($role->name, ['admin', 'super-admin'])) {
            session()->flash('error', 'No se puede eliminar un rol esencial del sistema.');
        } else {
            $role->delete();
            $this->dispatch('role-deleted');
        }

        $this->confirmingRoleDeletion = false;
        $this->dispatch('hideDeleteModal');
    }

    public function cancelDelete()
    {
        $this->confirmingRoleDeletion = false;
        $this->dispatch('hideDeleteModal');
    }

    public function render()
    {
        $roles = Role::where('name', 'like', '%' . $this->search . '%')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $permissions = Permission::orderBy('name')->get();

        return view('livewire.admin.roles.index-simple', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
